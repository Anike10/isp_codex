<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\Organization;
use App\Services\MikrotikInactivePortalService;
use App\Support\BillingWindow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrganizationController extends Controller
{
    private const PAYMENT_NOTE_SETTING_KEY = 'invoice_payment_note';

    public function index()
    {
        return view('organizations.index', ['organizations' => Organization::orderByDesc('is_default')->orderBy('name')->get()]);
    }

    public function create()
    {
        return view('organizations.form', [
            'organization' => new Organization,
            'defaultPaymentNote' => $this->defaultPaymentNote(),
            'billingWindow' => BillingWindow::window(),
            'billingSkipDays' => BillingWindow::skipDays(),
            'billingDayOptions' => BillingWindow::dayOptions(),
        ]);
    }

    public function edit(Organization $organization)
    {
        return view('organizations.form', [
            'organization' => $organization,
            'defaultPaymentNote' => $this->defaultPaymentNote(),
            'billingWindow' => BillingWindow::window(),
            'billingSkipDays' => BillingWindow::skipDays(),
            'billingDayOptions' => BillingWindow::dayOptions(),
        ]);
    }

    public function store(Request $request, MikrotikInactivePortalService $inactivePortal)
    {
        $data = $this->validated($request);
        $billingWindow = $this->extractBillingWindow($data);
        $paymentNote = $data['payment_note'] ?? null;
        $applyInactivePortal = (bool) ($data['apply_inactive_portal'] ?? false);
        unset($data['payment_note'], $data['apply_inactive_portal']);

        if ($applyInactivePortal && blank($data['please_call_numbers'] ?? null)) {
            throw ValidationException::withMessages([
                'please_call_numbers' => 'Add at least one phone number before applying the Please Call redirect.',
            ]);
        }
        if ($applyInactivePortal && ! $data['is_default']) {
            throw ValidationException::withMessages([
                'is_default' => 'The organization must be the default organization before its Please Call page can be applied.',
            ]);
        }

        DB::transaction(function () use ($data, $paymentNote, $billingWindow) {
            if ($data['is_default']) {
                Organization::query()->update(['is_default' => false]);
            }
            Organization::create($data);
            AppSetting::setValue(self::PAYMENT_NOTE_SETTING_KEY, $paymentNote);
            $this->saveBillingWindow($billingWindow);
        });

        return $this->savedResponse($applyInactivePortal, $inactivePortal);
    }

    public function update(Request $request, Organization $organization, MikrotikInactivePortalService $inactivePortal)
    {
        $data = $this->validated($request);
        $billingWindow = $this->extractBillingWindow($data);
        $paymentNote = $data['payment_note'] ?? null;
        $applyInactivePortal = (bool) ($data['apply_inactive_portal'] ?? false);
        unset($data['payment_note'], $data['apply_inactive_portal']);

        if ($applyInactivePortal && blank($data['please_call_numbers'] ?? null)) {
            throw ValidationException::withMessages([
                'please_call_numbers' => 'Add at least one phone number before applying the Please Call redirect.',
            ]);
        }
        if ($applyInactivePortal && ! $data['is_default']) {
            throw ValidationException::withMessages([
                'is_default' => 'The organization must be the default organization before its Please Call page can be applied.',
            ]);
        }

        DB::transaction(function () use ($data, $paymentNote, $billingWindow, $organization) {
            if ($data['is_default']) {
                Organization::whereKeyNot($organization->id)->update(['is_default' => false]);
            }
            $organization->update($data);
            AppSetting::setValue(self::PAYMENT_NOTE_SETTING_KEY, $paymentNote);
            $this->saveBillingWindow($billingWindow);
        });

        return $this->savedResponse($applyInactivePortal, $inactivePortal);
    }

    public function destroy(Organization $organization)
    {
        if ($organization->is_default) {
            return back()->withErrors(['organization' => 'The default organization cannot be deleted. Make another organization default first.']);
        }

        if ($organization->printLogs()->exists()) {
            return back()->withErrors(['organization' => 'This organization has print history and cannot be deleted. Set it to inactive instead.']);
        }

        $organization->delete();

        return redirect()->route('organizations.index')->with('success', 'Organization deleted successfully.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'], 'address' => ['nullable', 'string'],
            'mobile' => ['nullable', 'string', 'max:100'], 'phone' => ['nullable', 'string', 'max:100'],
            'please_call_numbers' => ['nullable', 'string', 'max:2000', function (string $attribute, mixed $value, \Closure $fail): void {
                $numbers = collect(preg_split('/\R/u', (string) $value))->map(fn ($number) => trim((string) $number))->filter();
                if ($numbers->count() > 20) {
                    $fail('You may add up to 20 Please Call numbers.');
                }
                if ($numbers->contains(fn (string $number) => mb_strlen($number) > 50)) {
                    $fail('Each Please Call number must be 50 characters or fewer.');
                }
            }],
            'please_call_brand_name' => ['nullable', 'string', 'max:255'],
            'please_call_title' => ['nullable', 'string', 'max:100'],
            'please_call_message' => ['nullable', 'string', 'max:1000'],
            'please_call_footer' => ['nullable', 'string', 'max:500'],
            'email' => ['nullable', 'email', 'max:255'], 'website' => ['nullable', 'string', 'max:255'],
            'tax_id' => ['nullable', 'string', 'max:100'], 'logo_url' => ['nullable', 'string', 'max:255'],
            'footer_note' => ['nullable', 'string'],
            'payment_note' => ['nullable', 'string', 'max:5000'],
            'default_without_signature' => ['nullable', 'boolean'],
            'bank_name' => ['nullable', 'string', 'max:255'], 'bank_account_name' => ['nullable', 'string', 'max:255'],
            'bank_account_number' => ['nullable', 'string', 'max:100'], 'bank_branch' => ['nullable', 'string', 'max:255'],
            'bank_routing_number' => ['nullable', 'string', 'max:100'], 'show_bank_info_on_invoice' => ['nullable', 'boolean'],
            'is_default' => ['nullable', 'boolean'], 'is_active' => ['nullable', 'boolean'],
            'billing_disable_start_hour' => ['required_with:billing_disable_end_hour', 'integer', 'between:0,23'],
            'billing_disable_end_hour' => ['required_with:billing_disable_start_hour', 'integer', 'between:0,23', 'gte:billing_disable_start_hour'],
            'billing_disable_skip_days_present' => ['nullable', 'boolean'],
            'billing_disable_skip_days' => ['nullable', 'array'],
            'billing_disable_skip_days.*' => ['integer', 'between:1,7', 'distinct'],
            'apply_inactive_portal' => ['nullable', 'boolean'],
        ], [
            'billing_disable_end_hour.gte' => 'The auto-disable end hour must be the same as or later than the start hour.',
        ]);
        $data['default_without_signature'] = $request->boolean('default_without_signature');
        $data['show_organization_selector'] = true;
        $data['show_bank_info_on_invoice'] = $request->boolean('show_bank_info_on_invoice');
        $data['is_default'] = $request->boolean('is_default');
        $data['is_active'] = $request->boolean('is_active');
        $data['apply_inactive_portal'] = $request->boolean('apply_inactive_portal');
        $data['please_call_numbers'] = collect(preg_split('/\R/u', (string) ($data['please_call_numbers'] ?? '')))
            ->map(fn ($number) => trim((string) $number))
            ->filter()
            ->unique()
            ->implode(PHP_EOL) ?: null;
        if ($data['is_default']) {
            $data['is_active'] = true;
        }

        return $data;
    }

    private function savedResponse(bool $applyInactivePortal, MikrotikInactivePortalService $inactivePortal)
    {
        $response = redirect()->route('organizations.index')->with('success', 'Organization saved successfully.');

        if (! $applyInactivePortal) {
            return $response;
        }

        $summary = $inactivePortal->configureAll();
        $result = "Please Call redirect applied to {$summary['configured']} router(s)";
        if ($summary['skipped'] > 0) {
            $result .= ", skipped {$summary['skipped']}";
        }
        if ($summary['failed'] > 0) {
            $result .= ", failed {$summary['failed']}";
        }
        $result .= '.';

        if ($summary['failed'] > 0) {
            return $response->with('warning', $result.' '.implode(' | ', $summary['messages']));
        }

        return $response->with('success', 'Organization saved. '.$result);
    }

    private function defaultPaymentNote(): string
    {
        return AppSetting::value(self::PAYMENT_NOTE_SETTING_KEY, '') ?: '';
    }

    /** @return array{start: ?int, end: ?int, skip_days: ?array<int, int>}|null */
    private function extractBillingWindow(array &$data): ?array
    {
        $hasWindow = array_key_exists('billing_disable_start_hour', $data)
            || array_key_exists('billing_disable_end_hour', $data);
        $hasSkipDays = array_key_exists('billing_disable_skip_days_present', $data)
            || array_key_exists('billing_disable_skip_days', $data);

        if (! $hasWindow && ! $hasSkipDays) {
            return null;
        }

        $window = [
            'start' => $hasWindow ? (int) $data['billing_disable_start_hour'] : null,
            'end' => $hasWindow ? (int) $data['billing_disable_end_hour'] : null,
            'skip_days' => $hasSkipDays
                ? array_values(array_unique(array_map('intval', $data['billing_disable_skip_days'] ?? [])))
                : null,
        ];
        unset(
            $data['billing_disable_start_hour'],
            $data['billing_disable_end_hour'],
            $data['billing_disable_skip_days_present'],
            $data['billing_disable_skip_days']
        );

        return $window;
    }

    /** @param array{start: ?int, end: ?int, skip_days: ?array<int, int>}|null $window */
    private function saveBillingWindow(?array $window): void
    {
        if ($window === null) {
            return;
        }

        if ($window['start'] !== null && $window['end'] !== null) {
            AppSetting::setValue(BillingWindow::START_KEY, (string) $window['start']);
            AppSetting::setValue(BillingWindow::END_KEY, (string) $window['end']);
        }

        if ($window['skip_days'] !== null) {
            AppSetting::setValue(BillingWindow::SKIP_DAYS_KEY, json_encode($window['skip_days']));
        }
    }
}
