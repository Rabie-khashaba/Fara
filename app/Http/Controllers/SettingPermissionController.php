<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class SettingPermissionController extends Controller
{
    public function index(): View
    {
        return view('settings.permissions.index', [
            'permissions' => Permission::query()
                ->withCount('roles')
                ->latest()
                ->get(),
        ]);
    }

    public function create(): View
    {
        return view('settings.permissions.create', [
            'roles' => Role::query()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:permissions,name'],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['exists:roles,id'],
        ], [
            'name.unique' => 'This permission already exists.',
        ]);

        $permission = Permission::create([
            'name' => $data['name'],
            'guard_name' => 'web',
        ]);

        $selectedRoles = Role::query()
            ->whereIn('id', $data['roles'] ?? [])
            ->get();

        $permission->syncRoles($selectedRoles);

        return redirect()->route('settings.permissions.index')->with('status', 'Permission created successfully.');
    }

    public function show(int $permissionId): View
    {
        $permission = Permission::query()->findOrFail($permissionId);
        $permission->load('roles');

        return view('settings.permissions.show', [
            'permission' => $permission,
        ]);
    }

    public function edit(int $permissionId): View
    {
        $permission = Permission::query()->findOrFail($permissionId);
        $permission->load('roles');

        return view('settings.permissions.edit', [
            'permission' => $permission,
            'roles' => Role::query()->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, int $permissionId): RedirectResponse
    {
        $permission = Permission::query()->findOrFail($permissionId);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:permissions,name,' . $permission->id],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['exists:roles,id'],
        ], [
            'name.unique' => 'This permission already exists.',
        ]);

        $permission->update([
            'name' => $data['name'],
        ]);

        $selectedRoles = Role::query()
            ->whereIn('id', $data['roles'] ?? [])
            ->get();

        $permission->syncRoles($selectedRoles);

        return redirect()->route('settings.permissions.show', $permission->id)->with('status', 'Permission updated successfully.');
    }

    public function destroy(int $permissionId): RedirectResponse
    {
        $permission = Permission::query()->findOrFail($permissionId);
        $permission->delete();

        return redirect()->route('settings.permissions.index')->with('status', 'Permission deleted successfully.');
    }

    public function delete(int $permissionId): RedirectResponse
    {
        return $this->destroy($permissionId);
    }
}
