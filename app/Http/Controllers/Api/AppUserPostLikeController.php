<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppUser;
use App\Models\AppUserActivity;
use App\Models\AppUserPost;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppUserPostLikeController extends Controller
{
    public function store(Request $request, int $id): JsonResponse
    {
        /** @var AppUser $appUser */
        $appUser = $request->user();
        $post = AppUserPost::query()->findOrFail($id);

        $post->likes()->firstOrCreate([
            'app_user_id' => $appUser->id,
        ]);

        AppUserActivity::create([
            'app_user_id' => $appUser->id,
            'type' => 'liked_post',
            'app_user_post_id' => $post->id,
            'subject_app_user_id' => $post->app_user_id,
            'description' => 'Liked a post',
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Post liked successfully',
        ], 201);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        /** @var AppUser $appUser */
        $appUser = $request->user();
        $post = AppUserPost::query()->findOrFail($id);

        $post->likes()->where('app_user_id', $appUser->id)->delete();

        AppUserActivity::create([
            'app_user_id' => $appUser->id,
            'type' => 'unliked_post',
            'app_user_post_id' => $post->id,
            'subject_app_user_id' => $post->app_user_id,
            'description' => 'Removed like from a post',
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Post unliked successfully',
        ]);
    }
}
