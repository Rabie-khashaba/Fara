<?php

namespace App\Http\Controllers;

use App\Models\AppUser;
use App\Models\Package;
use App\Models\AppUserPost;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AppUserController extends Controller
{
    public function index(Request $request): View
    {
        $query = AppUser::query()
            ->with('packages');

        if ($search = trim((string) $request->string('search'))) {
            $query->where(function ($builder) use ($search) {
                $builder
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($dateFrom = $request->input('date_from')) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo = $request->input('date_to')) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        if (in_array($request->input('status'), ['active', 'inactive'], true)) {
            $query->where('is_active', $request->input('status') === 'active');
        }

        if (in_array($request->input('subscribed'), ['yes', 'no'], true)) {
            $request->input('subscribed') === 'yes'
                ? $query->whereHas('packages')
                : $query->whereDoesntHave('packages');
        }

        return view('app-users.index', [
            'appUsers' => $query->latest()->paginate(10)->withQueryString(),
        ]);
    }

    public function create(): View
    {
        return view('app-users.create', [
            'packages' => Package::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['nullable', 'string', 'max:255', 'unique:app_users,username'],
            'phone' => ['required', 'string', 'max:30', 'unique:app_users,phone'],
            'password' => ['required', 'string', 'min:6'],
            'is_active' => ['nullable', 'boolean'],
            'profile_image' => ['nullable', 'image', 'max:2048'],
            'cover_photo' => ['nullable', 'image', 'max:4096'],
            'package_ids' => ['nullable', 'array'],
            'package_ids.*' => ['integer', 'exists:packages,id'],
        ], [
            'phone.unique' => 'This phone number already exists.',
        ]);

        $data['is_active'] = $request->boolean('is_active', true);
        $data['inactive_reason'] = $data['is_active'] ? null : AppUser::INACTIVE_REASON_ADMIN;

        if ($request->hasFile('profile_image')) {
            $data['profile_image'] = $request->file('profile_image')->store('app-user-profiles', 'public');
        }

        if ($request->hasFile('cover_photo')) {
            $data['cover_photo'] = $request->file('cover_photo')->store('app-user-profiles', 'public');
        }

        $appUser = AppUser::create($data);
        $appUser->packages()->sync($request->input('package_ids', []));

        return redirect()->route('app-users.index')->with('status', 'App user created successfully.');
    }

    public function show(AppUser $app_user): View
    {
        $app_user->load([
            'packages',
            'socialAccounts',
            'posts' => fn ($query) => $query
                ->with(['repostedPost.appUser', 'comments.appUser'])
                ->withCount(['likes', 'comments'])
                ->latest(),
            'followers.follower',
            'following.following',
            'activities' => fn ($query) => $query
                ->with(['post.comments', 'subjectAppUser'])
                ->latest(),
        ]);

        return view('app-users.show', [
            'appUser' => $app_user,
        ]);
    }

    public function edit(AppUser $app_user): View
    {
        $app_user->load('packages');

        return view('app-users.edit', [
            'appUser' => $app_user,
            'packages' => Package::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, AppUser $app_user): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['nullable', 'string', 'max:255', Rule::unique('app_users', 'username')->ignore($app_user->id)],
            'phone' => ['required', 'string', 'max:30', 'unique:app_users,phone,' . $app_user->id],
            'password' => ['nullable', 'string', 'min:6'],
            'is_active' => ['nullable', 'boolean'],
            'profile_image' => ['nullable', 'image', 'max:2048'],
            'cover_photo' => ['nullable', 'image', 'max:4096'],
            'package_ids' => ['nullable', 'array'],
            'package_ids.*' => ['integer', 'exists:packages,id'],
        ], [
            'phone.unique' => 'This phone number already exists.',
        ]);

        if (empty($data['password'])) {
            unset($data['password']);
        }

        $data['is_active'] = $request->boolean('is_active', $app_user->is_active);

        if ($request->has('is_active')) {
            $data['inactive_reason'] = $data['is_active'] ? null : AppUser::INACTIVE_REASON_ADMIN;
        }

        if ($request->hasFile('profile_image')) {
            if ($app_user->profile_image) {
                Storage::disk('public')->delete($app_user->profile_image);
            }

            $data['profile_image'] = $request->file('profile_image')->store('app-user-profiles', 'public');
        }

        if ($request->hasFile('cover_photo')) {
            if ($app_user->cover_photo) {
                Storage::disk('public')->delete($app_user->cover_photo);
            }

            $data['cover_photo'] = $request->file('cover_photo')->store('app-user-profiles', 'public');
        }

        $app_user->update($data);

        if ($request->has('is_active')) {
            if ($data['is_active']) {
                $app_user->activateAccount();
            } else {
                $app_user->deactivate(AppUser::INACTIVE_REASON_ADMIN);
            }
        }

        $app_user->packages()->sync($request->input('package_ids', []));

        return redirect()->route('app-users.show', $app_user)->with('status', 'App user updated successfully.');
    }

    public function toggleStatus(AppUser $app_user): RedirectResponse
    {
        if ($app_user->is_active) {
            $app_user->deactivate(AppUser::INACTIVE_REASON_ADMIN);
        } else {
            $app_user->activateAccount();
        }

        return redirect()->back()->with('status', 'App user status updated successfully.');
    }

    public function togglePostVisibility(AppUser $app_user, AppUserPost $post): RedirectResponse
    {
        abort_if($post->app_user_id !== $app_user->id, 404);

        $post->update([
            'is_hide' => ! $post->is_hide,
        ]);

        return redirect()
            ->back()
            ->with('status', $post->is_hide ? 'Post hidden successfully.' : 'Post is visible again.');
    }
}
