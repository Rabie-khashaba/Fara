<?php

namespace App\Http\Controllers;

use App\Models\AppUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AppUserController extends Controller
{
    public function index(): View
    {
        return view('app-users.index', [
            'appUsers' => AppUser::query()->latest()->paginate(10),
        ]);
    }

    public function create(): View
    {
        return view('app-users.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30', 'unique:app_users,phone'],
            'password' => ['required', 'string', 'min:6'],
        ], [
            'phone.unique' => 'This phone number already exists.',
        ]);

        AppUser::create($data);

        return redirect()->route('app-users.index')->with('status', 'App user created successfully.');
    }

    public function show(AppUser $app_user): View
    {
        $app_user->load([
            'socialAccounts',
            'posts' => fn ($query) => $query->with(['repostedPost.appUser'])->withCount(['likes', 'comments'])->latest(),
            'followers.follower',
            'following.following',
            'activities' => fn ($query) => $query->with(['post', 'subjectAppUser'])->latest(),
        ]);

        return view('app-users.show', [
            'appUser' => $app_user,
        ]);
    }

    public function edit(AppUser $app_user): View
    {
        return view('app-users.edit', [
            'appUser' => $app_user,
        ]);
    }

    public function update(Request $request, AppUser $app_user): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30', 'unique:app_users,phone,' . $app_user->id],
            'password' => ['nullable', 'string', 'min:6'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'phone.unique' => 'This phone number already exists.',
        ]);

        if (empty($data['password'])) {
            unset($data['password']);
        }

        $data['is_active'] = $request->boolean('is_active');

        $app_user->update($data);

        return redirect()->route('app-users.show', $app_user)->with('status', 'App user updated successfully.');
    }

    public function toggleStatus(AppUser $app_user): RedirectResponse
    {
        $app_user->update([
            'is_active' => ! $app_user->is_active,
        ]);

        return redirect()->back()->with('status', 'App user status updated successfully.');
    }
}
