<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\InternetPackage;
use App\Models\MikrotikImportedSecret;
use App\Models\MikrotikRouter;
use App\Models\Subscription;
use App\Services\MikrotikImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MikrotikImportController extends Controller
{
    public function importProfiles(MikrotikRouter $mikrotikRouter, MikrotikImportService $service)
    {
        $count = $service->importProfiles($mikrotikRouter);

        return back()->with('success', "Imported {$count} MikroTik PPP profiles. New profiles were added as zero-price packages.");
    }

    public function importIpPools(Request $request, MikrotikRouter $mikrotikRouter, MikrotikImportService $service)
    {
        $saveToApp = $request->boolean('save_to_app');
        $count = $service->importIpPools($mikrotikRouter, $saveToApp);

        if ($request->boolean('return_to_compare')) {
            $message = $saveToApp
                ? "Imported {$count} MikroTik IP pools to App IP Pools."
                : "Refreshed {$count} MikroTik IP pools.";

            return redirect()->to(route('mikrotik-routers.compare', $mikrotikRouter).'#ip-pools')
                ->with('success', $message);
        }

        return back()->with('success', "Imported {$count} MikroTik IP pools.");
    }

    public function importSecrets(MikrotikRouter $mikrotikRouter, MikrotikImportService $service)
    {
        $count = $service->importSecrets($mikrotikRouter);

        if (request()->boolean('return_to_compare')) {
            return back()->with('success', "Imported {$count} PPPoE secrets from {$mikrotikRouter->name}.");
        }

        return redirect()->route('mikrotik-routers.imported-secrets.index', $mikrotikRouter)
            ->with('success', "Imported {$count} PPPoE secrets from {$mikrotikRouter->name}.");
    }

    public function secrets(Request $request, MikrotikRouter $mikrotikRouter)
    {
        $sessionKey = 'per_page_default.mikrotik-imported-secrets';
        $perPage = max(1, min(500, (int) $request->query('per_page', $request->session()->get($sessionKey, 100))));
        if ($request->query('make_per_page_default') === '1') {
            $request->session()->put($sessionKey, $perPage);
        }
        $profileSessionKey = 'per_page_default.mikrotik-imported-profiles';
        $profilePerPage = max(1, min(500, (int) $request->query('profile_per_page', $request->session()->get($profileSessionKey, 25))));
        if ($request->query('make_profile_per_page_default') === '1') {
            $request->session()->put($profileSessionKey, $profilePerPage);
        }

        return view('mikrotik_routers.imported_secrets', [
            'mikrotikRouter' => $mikrotikRouter,
            'secrets' => $mikrotikRouter->importedSecrets()->with('customer')->orderBy('name')->paginate($perPage)->withQueryString(),
            'perPage' => $perPage,
            'profiles' => $mikrotikRouter->importedProfiles()->orderBy('name')->paginate($profilePerPage, ['*'], 'profile_page')->withQueryString(),
            'profilePerPage' => $profilePerPage,
            'pools' => $mikrotikRouter->importedIpPools()->orderBy('name')->get(),
        ]);
    }

    public function updateSecret(Request $request, MikrotikRouter $mikrotikRouter, MikrotikImportedSecret $mikrotikImportedSecret)
    {
        abort_unless($mikrotikImportedSecret->mikrotik_router_id === $mikrotikRouter->id, 404);
        $data = $request->validate([
            'router_comment' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'profile' => ['nullable', 'string', 'max:255'],
        ]);
        $mikrotikImportedSecret->update($data);

        return back()->with('success', 'Imported secret note updated.');
    }

    public function createParties(Request $request, MikrotikRouter $mikrotikRouter)
    {
        $data = $request->validate([
            'secret_ids' => ['required', 'array', 'min:1'],
            'secret_ids.*' => ['integer'],
            'never_suspend' => ['nullable', 'boolean'],
            'update_existing' => ['nullable', 'boolean'],
        ]);
        $secrets = $mikrotikRouter->importedSecrets()->whereIn('id', $data['secret_ids'])->get();
        $created = $updated = $skipped = 0;

        DB::transaction(function () use ($secrets, $mikrotikRouter, $data, &$created, &$updated, &$skipped): void {
            foreach ($secrets as $secret) {
                $customer = Customer::where('connection_id', $secret->name)->first();
                if ($customer && ! ($data['update_existing'] ?? false)) {
                    $skipped++;
                    continue;
                }

                $package = $this->packageFor($secret->profile, $mikrotikRouter);
                $note = $this->sourceNote($mikrotikRouter, $secret);
                $customerData = [
                    'name' => trim((string) $secret->name),
                    'phone' => $customer?->phone ?: 'Not provided',
                    'connection_id' => $secret->name,
                    'mikrotik_username' => $secret->name,
                    'mikrotik_password' => $secret->password,
                    'mikrotik_router_id' => $mikrotikRouter->id,
                    'address' => $customer?->address ?: 'Imported from MikroTik '.$mikrotikRouter->name,
                    'notes' => $this->appendNote($customer?->notes, $note),
                    'status' => $secret->disabled ? 'inactive' : 'active',
                    'is_customer' => true,
                    'is_vendor' => $customer?->is_vendor ?? false,
                    'never_suspend' => (bool) ($data['never_suspend'] ?? false),
                ];
                if ($customer) {
                    $customer->update($customerData);
                    $updated++;
                } else {
                    $customer = Customer::create($customerData);
                    $created++;
                }

                $this->syncSubscription($customer, $package, $secret->disabled);
                $secret->update(['customer_id' => $customer->id]);
            }
        });

        return back()->with('success', "Party import completed: {$created} created, {$updated} updated, {$skipped} skipped.");
    }

    private function packageFor(?string $profile, MikrotikRouter $router): ?InternetPackage
    {
        if (blank($profile)) {
            return null;
        }

        return InternetPackage::firstOrCreate(
            ['mikrotik_profile' => $profile],
            ['name' => $profile, 'speed' => 'Imported profile', 'monthly_price' => 0, 'description' => 'Automatically imported from MikroTik '.$router->name.'. Set the package price before billing.', 'status' => 'active']
        );
    }

    private function syncSubscription(Customer $customer, ?InternetPackage $package, bool $disabled): void
    {
        if (! $package) {
            return;
        }

        $subscription = $customer->subscriptions()->latest('id')->first();
        $values = ['internet_package_id' => $package->id, 'start_date' => now()->toDateString(), 'status' => $disabled ? 'inactive' : 'active'];
        if ($subscription) {
            $subscription->update($values);
        } else {
            Subscription::create(['customer_id' => $customer->id, ...$values]);
        }
    }

    private function sourceNote(MikrotikRouter $router, MikrotikImportedSecret $secret): string
    {
        return 'Imported from MikroTik: '.$router->name.' ('.$router->ip_address.':'.$router->api_port.') at '.now()->format('d/m/Y H:i:s')."\nConnection ID: {$secret->name}\nProfile: ".($secret->profile ?: 'none')."\nService: ".($secret->service ?: 'none')."\nRouter comment: ".($secret->router_comment ?: 'none');
    }

    private function appendNote(?string $old, string $new): string
    {
        return trim(($old ? rtrim($old)."\n\n" : '').$new);
    }
}
