<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Observers\RecordVersionObserver;
use App\Services\RecordVersionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(Request $request)
    {
        return view('users.index', [
            'users' => User::with('roles')
                ->latest()
                ->paginate($this->perPage($request))
                ->appends($request->query()),
        ]);
    }

    public function create()
    {
        return view('users.create', [
            'roles' => Role::with('permissions:id')->orderBy('label')->get(),
            'permissions' => Permission::orderBy('label')->get(),
            'permissionGroups' => config('user_access.groups', []),
            'menuGroups' => config('user_access.menu_groups', []),
            'resellers' => Customer::where('is_reseller', true)->where('status', 'active')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6'],
            'reseller_id' => ['nullable', 'exists:customers,id'],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['exists:roles,id'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['exists:permissions,id'],
            'menus' => ['nullable', 'array'],
            'menus.*' => ['string', 'in:'.implode(',', $this->menuKeys())],
            'menu_access_present' => ['sometimes', 'accepted'],
        ]);

        $hasMenuAccess = $request->boolean('menu_access_present');
        $selectedMenus = $data['menus'] ?? [];
        $selectedPermissions = $hasMenuAccess
            ? $this->permissionsWithMenuRequirements($data['permissions'] ?? [], $selectedMenus)
            : ($data['permissions'] ?? []);

        DB::transaction(function () use ($data, $hasMenuAccess, $selectedMenus, $selectedPermissions): void {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'reseller_id' => $this->validatedResellerId($data['reseller_id'] ?? null),
            ]);

            $user->roles()->sync($data['roles'] ?? []);
            $this->syncExactPermissions($user, $selectedPermissions);

            if ($hasMenuAccess) {
                $this->syncExactMenuAccess($user, $selectedMenus);
            }
        });

        return redirect()->route('users.index')->with('success', 'User created successfully.');
    }

    public function edit(User $user)
    {
        $user->load(['roles.permissions', 'permissions', 'deniedPermissions', 'menuAccesses']);

        return view('users.edit', [
            'user' => $user,
            'roles' => Role::with('permissions:id')->orderBy('label')->get(),
            'permissions' => Permission::orderBy('label')->get(),
            'permissionGroups' => config('user_access.groups', []),
            'menuGroups' => config('user_access.menu_groups', []),
            'resellers' => Customer::where('is_reseller', true)->where('status', 'active')->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, User $user, RecordVersionService $recordVersionService)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'password' => ['nullable', 'string', 'min:6'],
            'reseller_id' => ['nullable', 'exists:customers,id'],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['exists:roles,id'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['exists:permissions,id'],
            'menus' => ['nullable', 'array'],
            'menus.*' => ['string', 'in:'.implode(',', $this->menuKeys())],
            'menu_access_present' => ['sometimes', 'accepted'],
        ]);

        $hasMenuAccess = $request->boolean('menu_access_present');
        $selectedMenus = $data['menus'] ?? [];
        $selectedPermissions = $hasMenuAccess
            ? $this->permissionsWithMenuRequirements($data['permissions'] ?? [], $selectedMenus)
            : ($data['permissions'] ?? []);

        $payload = [
            'name' => $data['name'],
            'email' => $data['email'],
            'reseller_id' => $this->validatedResellerId($data['reseller_id'] ?? null),
        ];

        if (! empty($data['password'])) {
            $payload['password'] = Hash::make($data['password']);
        }

        DB::transaction(function () use ($user, $payload, $data, $recordVersionService, $hasMenuAccess, $selectedMenus, $selectedPermissions): void {
            $user = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            $oldSnapshot = $recordVersionService->snapshot($user, ['roles', 'permissions', 'deniedPermissions', 'menuAccesses']);

            if (array_key_exists('password', $payload)) {
                $oldSnapshot['login_credential_changed'] = false;
            }

            RecordVersionObserver::withoutRecording(fn () => $user->update($payload));
            $user->roles()->sync($data['roles'] ?? []);
            $this->syncExactPermissions($user, $selectedPermissions);

            if ($hasMenuAccess) {
                $this->syncExactMenuAccess($user, $selectedMenus);
            }

            $user->unsetRelation('roles');
            $user->unsetRelation('permissions');
            $user->unsetRelation('deniedPermissions');
            $user->unsetRelation('menuAccesses');
            $newSnapshot = $recordVersionService->snapshot($user->refresh(), ['roles', 'permissions', 'deniedPermissions', 'menuAccesses']);

            if (array_key_exists('password', $payload)) {
                $newSnapshot['login_credential_changed'] = true;
            }

            $recordVersionService->recordUpdate($user, $oldSnapshot, $newSnapshot, [
                'source' => 'user_edit',
                'user_email' => $user->email,
            ]);
        });

        return redirect()->route('users.index')->with('success', 'User updated successfully.');
    }

    public function destroy(Request $request, User $user)
    {
        if ($request->user()?->is($user)) {
            return back()->withErrors(['user' => 'You cannot delete your own logged-in account.']);
        }

        $anotherManagerExists = User::query()
            ->whereKeyNot($user->id)
            ->with(['deniedPermissions', 'permissions', 'roles.permissions'])
            ->get()
            ->contains(fn (User $candidate): bool => $candidate->hasPermission('manage_users'));

        if (! $anotherManagerExists) {
            return back()->withErrors(['user' => 'This is the last user who can manage users. Assign access to another user before deleting it.']);
        }

        DB::transaction(function () use ($user): void {
            DB::table('sessions')->where('user_id', $user->id)->delete();
            $user->delete();
        });

        return redirect()->route('users.index')->with('success', 'User deleted successfully.');
    }

    /**
     * Persist the form as an exact per-user access list. Unchecked permissions are
     * explicitly denied so a role cannot silently re-enable them for this user.
     *
     * @param  array<int, int|string>  $selectedPermissionIds
     */
    private function syncExactPermissions(User $user, array $selectedPermissionIds): void
    {
        $selectedIds = collect($selectedPermissionIds)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $allIds = Permission::query()->pluck('id');

        $user->permissions()->sync($selectedIds->all());
        $user->deniedPermissions()->sync($allIds->diff($selectedIds)->all());
    }

    /** @return array<int, string> */
    private function menuKeys(): array
    {
        return collect(config('user_access.menu_groups', []))
            ->flatMap(fn ($group) => array_keys($group['items'] ?? []))
            ->values()
            ->all();
    }

    /**
     * Ensure a checked menu also receives its underlying security capability.
     *
     * @param  array<int, int|string>  $selectedPermissionIds
     * @param  array<int, string>  $selectedMenuKeys
     * @return array<int, int>
     */
    private function permissionsWithMenuRequirements(array $selectedPermissionIds, array $selectedMenuKeys): array
    {
        $items = collect(config('user_access.menu_groups', []))->pluck('items')->collapse();
        $requiredNames = collect($selectedMenuKeys)
            ->map(fn (string $key) => $items->get($key)['permission'] ?? null)
            ->filter()
            ->unique();

        $requiredIds = Permission::query()->whereIn('name', $requiredNames)->pluck('id');

        return collect($selectedPermissionIds)
            ->map(fn ($id) => (int) $id)
            ->merge($requiredIds)
            ->unique()
            ->values()
            ->all();
    }

    /** @param  array<int, string>  $selectedMenuKeys */
    private function syncExactMenuAccess(User $user, array $selectedMenuKeys): void
    {
        $selected = collect($selectedMenuKeys)->unique();
        $now = now();
        $rows = collect($this->menuKeys())->map(fn (string $key): array => [
            'user_id' => $user->id,
            'menu_key' => $key,
            'allowed' => $selected->contains($key),
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();

        DB::table('user_menu_accesses')->upsert(
            $rows,
            ['user_id', 'menu_key'],
            ['allowed', 'updated_at']
        );
    }

    private function validatedResellerId(mixed $resellerId): ?int
    {
        if (! filled($resellerId)) {
            return null;
        }

        return Customer::query()->whereKey($resellerId)->where('is_reseller', true)->value('id')
            ?? abort(422, 'The selected party is not a reseller.');
    }
}
