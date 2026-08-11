<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\Role;
use App\Observers\RecordVersionObserver;
use App\Services\RecordVersionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
    public function index(Request $request)
    {
        return view('roles.index', [
            'roles' => Role::withCount('users')
                ->with('permissions')
                ->orderBy('label')
                ->paginate($this->perPage($request))
                ->appends($request->query()),
        ]);
    }

    public function create()
    {
        return view('roles.create', [
            'permissions' => Permission::orderBy('label')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', 'alpha_dash', 'unique:roles,name'],
            'label' => ['required', 'string', 'max:255'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['exists:permissions,id'],
        ]);

        $role = Role::create([
            'name' => $data['name'],
            'label' => $data['label'],
        ]);
        $role->permissions()->sync($data['permissions'] ?? []);

        return redirect()->route('roles.index')->with('success', 'Role created successfully.');
    }

    public function edit(Role $role)
    {
        $role->load('permissions');

        return view('roles.edit', [
            'role' => $role,
            'permissions' => Permission::orderBy('label')->get(),
        ]);
    }

    public function update(Request $request, Role $role, RecordVersionService $recordVersionService)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', 'alpha_dash', Rule::unique('roles', 'name')->ignore($role->id)],
            'label' => ['required', 'string', 'max:255'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['exists:permissions,id'],
        ]);

        DB::transaction(function () use ($role, $data, $recordVersionService): void {
            $role = Role::query()->whereKey($role->id)->lockForUpdate()->firstOrFail();
            $oldSnapshot = $recordVersionService->snapshot($role, ['permissions']);

            RecordVersionObserver::withoutRecording(fn () => $role->update([
                'name' => $data['name'],
                'label' => $data['label'],
            ]));
            $role->permissions()->sync($data['permissions'] ?? []);

            $role->unsetRelation('permissions');
            $newSnapshot = $recordVersionService->snapshot($role->refresh(), ['permissions']);
            $recordVersionService->recordUpdate($role, $oldSnapshot, $newSnapshot, [
                'source' => 'role_edit',
                'role_name' => $role->name,
            ]);
        });

        return redirect()->route('roles.index')->with('success', 'Role updated successfully.');
    }

    public function destroy(Role $role)
    {
        if ($role->name === 'admin') {
            return back()->withErrors(['role' => 'The built-in Administrator role cannot be deleted.']);
        }

        if ($role->users()->exists()) {
            return back()->withErrors(['role' => 'Remove this role from all users before deleting it.']);
        }

        $role->delete();

        return redirect()->route('roles.index')->with('success', 'Role deleted successfully.');
    }
}
