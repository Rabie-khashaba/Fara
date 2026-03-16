<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppUser;
use App\Models\AppUserActivity;
use App\Models\AppUserPost;
use App\Models\AppUserSavedPost;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppUserSavedPostController extends Controller
{
    public function mySaved(Request $request): JsonResponse
    {
        /** @var AppUser $appUser */
        $appUser = $request->user();

        $savedPosts = AppUserSavedPost::query()
            ->with([
                'post' => fn ($query) => $query
                    ->visible()
                    ->with(['appUser:id,name,username', 'repostedPost.appUser:id,name,username'])
                    ->withCount(['likes', 'comments', 'reposts', 'sharedPosts', 'savedPosts']),
            ])
            ->where('app_user_id', $appUser->id)
            ->whereHas('post', fn ($query) => $query->visible())
            ->latest()
            ->get();

        return response()->json([
            'status' => true,
            'data' => $savedPosts,
        ]);
    }

    public function store(Request $request, int $postId): JsonResponse
    {
        /** @var AppUser $appUser */
        $appUser = $request->user();
        $post = AppUserPost::query()->visible()->findOrFail($postId);

        $savedPost = AppUserSavedPost::query()->firstOrCreate([
            'app_user_id' => $appUser->id,
            'app_user_post_id' => $post->id,
        ]);

        if ($savedPost->wasRecentlyCreated) {
            AppUserActivity::create([
                'app_user_id' => $appUser->id,
                'type' => 'saved_post',
                'app_user_post_id' => $post->id,
                'subject_app_user_id' => $post->app_user_id,
                'description' => 'Saved a post',
                'meta' => [
                    'subject_name' => $post->appUser?->name,
                    'post_excerpt' => $post->content,
                ],
            ]);
        }

        return response()->json([
            'status' => true,
            'message' => 'Post saved successfully',
            'data' => $savedPost->load([
                'post' => fn ($query) => $query
                    ->with(['appUser:id,name,username', 'repostedPost.appUser:id,name,username'])
                    ->withCount(['likes', 'comments', 'reposts', 'sharedPosts', 'savedPosts']),
            ]),
        ], $savedPost->wasRecentlyCreated ? 201 : 200);
    }

    public function destroy(Request $request, int $postId): JsonResponse
    {
        /** @var AppUser $appUser */
        $appUser = $request->user();
        $post = AppUserPost::query()->findOrFail($postId);

        $savedPost = AppUserSavedPost::query()
            ->where('app_user_id', $appUser->id)
            ->where('app_user_post_id', $post->id)
            ->firstOrFail();

        $savedPost->delete();

        AppUserActivity::create([
            'app_user_id' => $appUser->id,
            'type' => 'unsaved_post',
            'app_user_post_id' => $post->id,
            'subject_app_user_id' => $post->app_user_id,
            'description' => 'Removed a saved post',
            'meta' => [
                'subject_name' => $post->appUser?->name,
                'post_excerpt' => $post->content,
            ],
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Saved post removed successfully',
        ]);
    }
}
