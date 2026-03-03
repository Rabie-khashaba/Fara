<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(): View
    {
        return view('users.index', [
            'users' => User::query()->with('roles')->latest()->paginate(10),
        ]);
    }

    public function create(): View
    {
        return view('users.create', [
            'roles' => Role::query()->orderBy('id')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30', 'unique:users,phone'],
            'password' => ['required', 'string', 'min:6'],
            'role_id' => ['nullable', 'integer', 'exists:roles,id'],
            'type' => ['required', 'in:admin,user'],
        ], [
            'phone.unique' => 'This phone number already exists.',
        ]);

        $user = User::create($data);

        if (! empty($data['role_id'])) {
            $role = Role::query()->find($data['role_id']);

            if ($role) {
                $user->syncRoles([$role]);
            }
        }

        return redirect()->route('users.index')->with('status', 'Dashboard user created successfully.');
    }

    public function show(User $user): View
    {
        $user->load('roles');

        return view('users.show', [
            'user' => $user,
        ]);
    }

    public function edit(User $user): View
    {
        $user->load('roles');

        return view('users.edit', [
            'user' => $user,
            'roles' => Role::query()->orderBy('id')->get(),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30', 'unique:users,phone,' . $user->id],
            'password' => ['nullable', 'string', 'min:6'],
            'role_id' => ['nullable', 'integer', 'exists:roles,id'],
            'type' => ['required', 'in:admin,user'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'phone.unique' => 'This phone number already exists.',
        ]);

        if (empty($data['password'])) {
            unset($data['password']);
        }

        $data['is_active'] = $request->boolean('is_active');

        $user->update($data);

        if (! empty($data['role_id'])) {
            $role = Role::query()->find($data['role_id']);

            if ($role) {
                $user->syncRoles([$role]);
            }
        } else {
            $user->syncRoles([]);
        }

        return redirect()->route('users.show', $user)->with('status', 'Dashboard user updated successfully.');
    }

    public function toggleBlock(User $user): RedirectResponse
    {
        $user->update([
            'is_active' => ! $user->is_active,
        ]);

        return redirect()->back()->with('status', 'Dashboard user status updated successfully.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $user->delete();

        return redirect()->route('users.index')->with('status', 'Dashboard user deleted successfully.');
    }
}
