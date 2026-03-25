<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppUser;
use App\Models\AppUserActivity;
use App\Models\AppUserPostComment;
use App\Services\AppUserPushNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppUserPostCommentLikeController extends Controller
{
    public function __construct(
        private readonly AppUserPushNotificationService $pushNotificationService
    ) {
    }

    public function store(Request $request, int $commentId): JsonResponse
    {
        /** @var AppUser $appUser */
        $appUser = $request->user();
        $comment = AppUserPostComment::query()->with(['appUser', 'post.appUser'])->findOrFail($commentId);

        $like = $comment->likes()->firstOrCreate([
            'app_user_id' => $appUser->id,
        ]);

        if ($like->wasRecentlyCreated) {
            AppUserActivity::create([
                'app_user_id' => $appUser->id,
                'type' => 'liked_comment',
                'app_user_post_id' => $comment->app_user_post_id,
                'subject_app_user_id' => $comment->post?->app_user_id,
                'description' => 'Liked a comment',
                'meta' => [
                    'subject_name' => $comment->post?->appUser?->name,
                    'post_excerpt' => $comment->post?->content,
                    'comment_excerpt' => $comment->comment,
                ],
            ]);

            if ($comment->appUser) {
                $this->pushNotificationService->sendToUser(
                    $comment->appUser,
                    $appUser,
                    $appUser->name,
                    'liked your comment',
                    [
                        'type' => 'comment_like',
                        'post_id' => $comment->app_user_post_id,
                        'comment_id' => $comment->id,
                        'sender_app_user_id' => $appUser->id,
                    ]
                );
            }
        }

        return response()->json([
            'status' => true,
            'message' => 'Comment liked successfully',
            'data' => $comment->fresh()->load('appUser')->loadCount('likes'),
        ], $like->wasRecentlyCreated ? 201 : 200);
    }

    public function destroy(Request $request, int $commentId): JsonResponse
    {
        /** @var AppUser $appUser */
        $appUser = $request->user();
        $comment = AppUserPostComment::query()->with('post.appUser')->findOrFail($commentId);

        $comment->likes()->where('app_user_id', $appUser->id)->delete();

        AppUserActivity::create([
            'app_user_id' => $appUser->id,
            'type' => 'unliked_comment',
            'app_user_post_id' => $comment->app_user_post_id,
            'subject_app_user_id' => $comment->post?->app_user_id,
            'description' => 'Removed like from a comment',
            'meta' => [
                'subject_name' => $comment->post?->appUser?->name,
                'post_excerpt' => $comment->post?->content,
                'comment_excerpt' => $comment->comment,
            ],
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Comment unliked successfully',
        ]);
    }
}
