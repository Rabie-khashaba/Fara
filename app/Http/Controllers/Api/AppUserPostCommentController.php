<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\AppUserPostComment\StoreCommentRequest;
use App\Http\Requests\Api\AppUserPostComment\UpdateCommentRequest;
use App\Models\AppUser;
use App\Models\AppUserActivity;
use App\Models\AppUserPost;
use App\Models\AppUserPostComment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppUserPostCommentController extends Controller
{
    public function index(int $id): JsonResponse
    {
        $post = AppUserPost::query()->visible()->findOrFail($id);

        return response()->json([
            'status' => true,
            'data' => $post->comments()->with('appUser')->latest()->get(),
        ]);
    }

    public function store(StoreCommentRequest $request, int $id): JsonResponse
    {
        /** @var AppUser $appUser */
        $appUser = $request->user();
        $post = AppUserPost::query()->visible()->findOrFail($id);

        $comment = $post->comments()->create([
            'app_user_id' => $appUser->id,
            'comment' => $request->validated()['comment'],
        ]);

        AppUserActivity::create([
            'app_user_id' => $appUser->id,
            'type' => 'commented_on_post',
            'app_user_post_id' => $post->id,
            'subject_app_user_id' => $post->app_user_id,
            'description' => 'Commented on a post',
            'meta' => [
                'subject_name' => $post->appUser?->name,
                'post_excerpt' => $post->content,
                'comment_excerpt' => $comment->comment,
            ],
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Comment added successfully',
            'data' => $comment->load('appUser'),
        ], 201);
    }

    public function update(UpdateCommentRequest $request, int $commentId): JsonResponse
    {
        /** @var AppUser $appUser */
        $appUser = $request->user();
        $comment = AppUserPostComment::query()->findOrFail($commentId);

        abort_if($comment->app_user_id !== $appUser->id, 403, 'Unauthorized');

        $comment->update($request->validated());

        AppUserActivity::create([
            'app_user_id' => $appUser->id,
            'type' => 'updated_comment',
            'app_user_post_id' => $comment->app_user_post_id,
            'subject_app_user_id' => $comment->post?->app_user_id,
            'description' => 'Updated a comment',
            'meta' => [
                'subject_name' => $comment->post?->appUser?->name,
                'post_excerpt' => $comment->post?->content,
                'comment_excerpt' => $comment->comment,
            ],
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Comment updated successfully',
            'data' => $comment->fresh()->load('appUser'),
        ]);
    }

    public function destroy(Request $request, int $commentId): JsonResponse
    {
        /** @var AppUser $appUser */
        $appUser = $request->user();
        $comment = AppUserPostComment::query()->findOrFail($commentId);

        abort_if($comment->app_user_id !== $appUser->id, 403, 'Unauthorized');

        AppUserActivity::create([
            'app_user_id' => $appUser->id,
            'type' => 'deleted_comment',
            'app_user_post_id' => $comment->app_user_post_id,
            'subject_app_user_id' => $comment->post?->app_user_id,
            'description' => 'Deleted a comment',
            'meta' => [
                'subject_name' => $comment->post?->appUser?->name,
                'post_excerpt' => $comment->post?->content,
                'comment_excerpt' => $comment->comment,
            ],
        ]);

        $comment->delete();

        return response()->json([
            'status' => true,
            'message' => 'Comment deleted successfully',
        ]);
    }
}
