<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppUser;
use App\Models\AppUserActivity;
use App\Services\AppUserPushNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppUserFollowController extends Controller
{
    public function __construct(
        private readonly AppUserPushNotificationService $pushNotificationService
    ) {
    }

    public function followers(int $appUserId): JsonResponse
    {
        $targetAppUser = AppUser::query()
            ->with(['followers.follower:id,name,username,email,phone'])
            ->findOrFail($appUserId);

        $followers = $targetAppUser->followers->map(fn ($follow) => [
            'follow_id' => $follow->id,
            'followed_at' => $follow->created_at,
            'user' => [
                'id' => $follow->follower?->id,
                'name' => $follow->follower?->name,
                'username' => $follow->follower?->username,
                'email' => $follow->follower?->email,
                'phone' => $follow->follower?->phone,
            ],
        ]);

        return response()->json([
            'status' => true,
            'data' => [
                'app_user_id' => $targetAppUser->id,
                'count' => $followers->count(),
                'followers' => $followers,
            ],
        ]);
    }

    public function followingList(int $appUserId): JsonResponse
    {
        $targetAppUser = AppUser::query()
            ->with(['following.following:id,name,username,email,phone'])
            ->findOrFail($appUserId);

        $following = $targetAppUser->following->map(fn ($follow) => [
            'follow_id' => $follow->id,
            'followed_at' => $follow->created_at,
            'user' => [
                'id' => $follow->following?->id,
                'name' => $follow->following?->name,
                'username' => $follow->following?->username,
                'email' => $follow->following?->email,
                'phone' => $follow->following?->phone,
            ],
        ]);

        return response()->json([
            'status' => true,
            'data' => [
                'app_user_id' => $targetAppUser->id,
                'count' => $following->count(),
                'following' => $following,
            ],
        ]);
    }

    public function store(Request $request, int $appUserId): JsonResponse
    {
        /** @var AppUser $appUser */
        $appUser = $request->user();
        $targetAppUser = AppUser::query()->findOrFail($appUserId);

        abort_if($appUser->is($targetAppUser), 422, 'You cannot follow yourself');

        $appUser->following()->firstOrCreate([
            'following_app_user_id' => $targetAppUser->id,
        ]);

        AppUserActivity::create([
            'app_user_id' => $appUser->id,
            'type' => 'followed_user',
            'subject_app_user_id' => $targetAppUser->id,
            'description' => 'Started following a user',
            'meta' => [
                'subject_name' => $targetAppUser->name,
            ],
        ]);

        $this->pushNotificationService->sendToUser(
            $targetAppUser,
            $appUser,
            'New follower',
            "{$appUser->name} started following you.",
            [
                'type' => 'follow',
                'sender_app_user_id' => (string) $appUser->id,
            ]
        );

        return response()->json([
            'status' => true,
            'message' => 'User followed successfully',
        ], 201);
    }

    public function destroy(Request $request, int $appUserId): JsonResponse
    {
        /** @var AppUser $appUser */
        $appUser = $request->user();
        $targetAppUser = AppUser::query()->findOrFail($appUserId);

        $appUser->following()
            ->where('following_app_user_id', $targetAppUser->id)
            ->delete();

        AppUserActivity::create([
            'app_user_id' => $appUser->id,
            'type' => 'unfollowed_user',
            'subject_app_user_id' => $targetAppUser->id,
            'description' => 'Stopped following a user',
            'meta' => [
                'subject_name' => $targetAppUser->name,
            ],
        ]);

        return response()->json([
            'status' => true,
            'message' => 'User unfollowed successfully',
        ]);
    }
}
