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
            ->with(['followers.follower:id,name,username,email,phone,profile_image'])
            ->findOrFail($appUserId);

        return response()->json($this->followersPayload($targetAppUser));
    }

    public function followingList(int $appUserId): JsonResponse
    {
        $targetAppUser = AppUser::query()
            ->with(['following.following:id,name,username,email,phone,profile_image'])
            ->findOrFail($appUserId);

        return response()->json($this->followingPayload($targetAppUser));
    }

    public function myFollowers(Request $request): JsonResponse
    {
        /** @var AppUser $appUser */
        $appUser = $request->user();

        $appUser->load(['followers.follower:id,name,username,email,phone,profile_image']);

        return response()->json($this->followersPayload($appUser));
    }

    public function myFollowing(Request $request): JsonResponse
    {
        /** @var AppUser $appUser */
        $appUser = $request->user();

        $appUser->load(['following.following:id,name,username,email,phone,profile_image']);

        return response()->json($this->followingPayload($appUser));
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

    private function followersPayload(AppUser $targetAppUser): array
    {
        $followers = $targetAppUser->followers->map(fn ($follow) => [
            'follow_id' => $follow->id,
            'followed_at' => $follow->created_at,
            'user' => $this->formatUser($follow->follower),
        ]);

        return [
            'status' => true,
            'data' => [
                'app_user_id' => $targetAppUser->id,
                'count' => $followers->count(),
                'followers' => $followers,
            ],
        ];
    }

    private function followingPayload(AppUser $targetAppUser): array
    {
        $following = $targetAppUser->following->map(fn ($follow) => [
            'follow_id' => $follow->id,
            'followed_at' => $follow->created_at,
            'user' => $this->formatUser($follow->following),
        ]);

        return [
            'status' => true,
            'data' => [
                'app_user_id' => $targetAppUser->id,
                'count' => $following->count(),
                'following' => $following,
            ],
        ];
    }

    private function formatUser(?AppUser $appUser): ?array
    {
        if (! $appUser) {
            return null;
        }

        return [
            'id' => $appUser->id,
            'name' => $appUser->name,
            'username' => $appUser->username,
            'email' => $appUser->email,
            'phone' => $appUser->phone,
            'image' => $appUser->profile_image_url,
            'profile_image_url' => $appUser->profile_image_url,
        ];
    }
}
