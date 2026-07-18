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
            'roles' => Role::orderBy('label')->get(),
            'permissions' => Permission::orderBy('label')->get(),
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
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'reseller_id' => $this->validatedResellerId($data['reseller_id'] ?? null),
        ]);

        $user->roles()->sync($data['roles'] ?? []);
        $user->permissions()->sync($data['permissions'] ?? []);

        return redirect()->route('users.index')->with('success', 'User created successfully.');
    }

    public function edit(User $user)
    {
        $user->load(['roles', 'permissions']);

        return view('users.edit', [
            'user' => $user,
            'roles' => Role::orderBy('label')->get(),
            'permissions' => Permission::orderBy('label')->get(),
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
        ]);

        $payload = [
            'name' => $data['name'],
            'email' => $data['email'],
            'reseller_id' => $this->validatedResellerId($data['reseller_id'] ?? null),
        ];

        if (! empty($data['password'])) {
            $payload['password'] = Hash::make($data['password']);
        }

        DB::transaction(function () use ($user, $payload, $data, $recordVersionService): void {
            $user = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            $oldSnapshot = $recordVersionService->snapshot($user, ['roles', 'permissions']);

            if (array_key_exists('password', $payload)) {
                $oldSnapshot['login_credential_changed'] = false;
            }

            RecordVersionObserver::withoutRecording(fn () => $user->update($payload));
            $user->roles()->sync($data['roles'] ?? []);
            $user->permissions()->sync($data['permissions'] ?? []);

            $user->unsetRelation('roles');
            $user->unsetRelation('permissions');
            $newSnapshot = $recordVersionService->snapshot($user->refresh(), ['roles', 'permissions']);

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

    private function validatedResellerId(mixed $resellerId): ?int
    {
        if (! filled($resellerId)) {
            return null;
        }

        return Customer::query()->whereKey($resellerId)->where('is_reseller', true)->value('id')
            ?? abort(422, 'The selected party is not a reseller.');
    }
}
