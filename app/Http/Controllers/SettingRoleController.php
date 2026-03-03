<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class SettingRoleController extends Controller
{
    public function index(): View
    {
        return view('settings.roles.index', [
            'roles' => Role::query()
                ->withCount('users')
                ->with('permissions')
                ->latest()
                ->get(),
        ]);
    }

    public function create(): View
    {
        return view('settings.roles.create', [
            'permissions' => Permission::query()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:roles,name'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['exists:permissions,id'],
        ], [
            'name.unique' => 'This role already exists.',
        ]);

        $role = Role::create([
            'name' => $data['name'],
            'guard_name' => 'web',
        ]);

        $selectedPermissions = Permission::query()
            ->whereIn('id', $data['permissions'] ?? [])
            ->get();

        $role->syncPermissions($selectedPermissions);

        return redirect()->route('settings.roles.index')->with('status', 'Role created successfully.');
    }

    public function show(int $role): View
    {
        $role = Role::query()->findOrFail($role);
        $role->load(['permissions', 'users']);

        return view('settings.roles.show', [
            'role' => $role,
        ]);
    }

    public function edit(int $role): View
    {
        $role = Role::query()->findOrFail($role);
        $role->load('permissions');

        return view('settings.roles.edit', [
            'role' => $role,
            'permissions' => Permission::query()->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, int $role): RedirectResponse
    {
        $role = Role::query()->findOrFail($role);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:roles,name,' . $role->id],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['exists:permissions,id'],
        ], [
            'name.unique' => 'This role already exists.',
        ]);

        $role->update([
            'name' => $data['name'],
        ]);

        $selectedPermissions = Permission::query()
            ->whereIn('id', $data['permissions'] ?? [])
            ->get();

        $role->syncPermissions($selectedPermissions);

        return redirect()->route('settings.roles.show', $role)->with('status', 'Role updated successfully.');
    }

    public function destroy(int $role): RedirectResponse
    {
        $role = Role::query()->findOrFail($role);
        $role->delete();

        return redirect()->route('settings.roles.index')->with('status', 'Role deleted successfully.');
    }
}
