<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppUser;
use App\Models\AppUserActivity;
use App\Models\AppUserPost;
use App\Models\AppUserSharedPost;
use App\Services\AppUserPushNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppUserSharedPostController extends Controller
{
    public function __construct(
        private readonly AppUserPushNotificationService $pushNotificationService
    ) {
    }

    public function all(): JsonResponse
    {
        $sharedPosts = AppUserSharedPost::query()
            ->with([
                'appUser:id,name,username',
                'post' => fn ($query) => $query
                    ->visible()
                    ->with(['appUser:id,name,username', 'repostedPost.appUser:id,name,username'])
                    ->withCount(['likes', 'comments', 'reposts', 'sharedPosts', 'savedPosts']),
            ])
            ->whereHas('post', fn ($query) => $query->visible())
            ->latest()
            ->get();

        return response()->json([
            'status' => true,
            'data' => $sharedPosts,
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $sharedPost = AppUserSharedPost::query()
            ->with([
                'appUser:id,name,username',
                'post' => fn ($query) => $query
                    ->visible()
                    ->with(['appUser:id,name,username', 'comments.appUser', 'repostedPost.appUser:id,name,username'])
                    ->withCount(['likes', 'comments', 'reposts', 'sharedPosts', 'savedPosts']),
            ])
            ->whereHas('post', fn ($query) => $query->visible())
            ->findOrFail($id);

        return response()->json([
            'status' => true,
            'data' => $sharedPost,
        ]);
    }

    public function store(Request $request, int $postId): JsonResponse
    {
        /** @var AppUser $appUser */
        $appUser = $request->user();
        $post = AppUserPost::query()->visible()->findOrFail($postId);

        $sharedPost = AppUserSharedPost::query()->firstOrCreate([
            'app_user_id' => $appUser->id,
            'app_user_post_id' => $post->id,
        ]);

        AppUserActivity::create([
            'app_user_id' => $appUser->id,
            'type' => 'shared_post',
            'app_user_post_id' => $post->id,
            'subject_app_user_id' => $post->app_user_id,
            'description' => 'Shared a post',
            'meta' => [
                'subject_name' => $post->appUser?->name,
                'post_excerpt' => $post->content,
            ],
        ]);

        if ($sharedPost->wasRecentlyCreated && $post->appUser) {
            $this->pushNotificationService->sendToUser(
                $post->appUser,
                $appUser,
                $appUser->name,
                'shared your post',
                [
                    'type' => 'post_interaction',
                    'interaction_type' => 'share',
                    'post_id' => $post->id,
                    'shared_post_id' => $sharedPost->id,
                    'sender_app_user_id' => $appUser->id,
                ]
            );
        }

        return response()->json([
            'status' => true,
            'message' => 'Post shared successfully',
            'data' => $sharedPost->load([
                'appUser:id,name,username',
                'post' => fn ($query) => $query
                    ->with(['appUser:id,name,username', 'repostedPost.appUser:id,name,username'])
                    ->withCount(['likes', 'comments', 'reposts', 'sharedPosts', 'savedPosts']),
            ]),
        ], $sharedPost->wasRecentlyCreated ? 201 : 200);
    }

    public function destroy(Request $request, int $postId): JsonResponse
    {
        /** @var AppUser $appUser */
        $appUser = $request->user();

        $sharedPost = AppUserSharedPost::query()
            ->where('app_user_id', $appUser->id)
            ->where('app_user_post_id', $postId)
            ->firstOrFail();

        $sharedPost->delete();

        return response()->json([
            'status' => true,
            'message' => 'Shared post removed successfully',
        ]);
    }
}
