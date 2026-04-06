<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\AppUserPostComment\StoreCommentRequest;
use App\Http\Requests\Api\AppUserPostComment\UpdateCommentRequest;
use App\Models\AppUser;
use App\Models\AppUserActivity;
use App\Models\AppUserPost;
use App\Models\AppUserPostComment;
use App\Services\AppUserPushNotificationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppUserPostCommentController extends Controller
{
    public function __construct(
        private readonly AppUserPushNotificationService $pushNotificationService
    ) {
    }

    public function index(int $id): JsonResponse
    {
        /** @var AppUser|null $appUser */
        $appUser = request()->user();
        $post = AppUserPost::query()->visible()->findOrFail($id);
        $comments = $this->withViewerState($post->comments()->whereNull('parent_comment_id'), $appUser)
            ->with([
                'appUser',
                'replies' => fn ($query) => $this->withViewerState($query, $appUser)
                    ->with('appUser')
                    ->withCount('likes')
                    ->latest(),
            ])
            ->withCount(['likes', 'replies'])
            ->latest()
            ->get()
            ->each(fn ($comment) => $this->prepareCommentForResponse($comment));

        return response()->json([
            'status' => true,
            'data' => $comments,
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

        if ($post->appUser) {
            $this->pushNotificationService->sendToUser(
                $post->appUser,
                $appUser,
                $appUser->name,
                $comment->comment,
                [
                    'type' => 'post_comment',
                    'post_id' => $post->id,
                    'comment_id' => $comment->id,
                    'sender_app_user_id' => $appUser->id,
                ]
            );
        }

        return response()->json([
            'status' => true,
            'message' => 'Comment added successfully',
            'data' => $this->loadCommentResponse($comment->id, $appUser),
        ], 201);
    }

    public function show(Request $request, int $commentId): JsonResponse
    {
        /** @var AppUser|null $appUser */
        $appUser = $request->user();

        return response()->json([
            'status' => true,
            'data' => $this->loadCommentResponse($commentId, $appUser),
        ]);
    }

    public function reply(StoreCommentRequest $request, int $commentId): JsonResponse
    {
        /** @var AppUser $appUser */
        $appUser = $request->user();
        $parentComment = AppUserPostComment::query()
            ->with(['post.appUser', 'appUser'])
            ->findOrFail($commentId);

        $reply = AppUserPostComment::query()->create([
            'app_user_post_id' => $parentComment->app_user_post_id,
            'app_user_id' => $appUser->id,
            'parent_comment_id' => $parentComment->id,
            'comment' => $request->validated()['comment'],
        ]);

        AppUserActivity::create([
            'app_user_id' => $appUser->id,
            'type' => 'replied_to_comment',
            'app_user_post_id' => $parentComment->app_user_post_id,
            'subject_app_user_id' => $parentComment->app_user_id,
            'description' => 'Replied to a comment',
            'meta' => [
                'subject_name' => $parentComment->appUser?->name,
                'post_excerpt' => $parentComment->post?->content,
                'comment_excerpt' => $reply->comment,
                'parent_comment_excerpt' => $parentComment->comment,
            ],
        ]);

        if ($parentComment->appUser) {
            $this->pushNotificationService->sendToUser(
                $parentComment->appUser,
                $appUser,
                $appUser->name,
                $reply->comment,
                [
                    'type' => 'comment_reply',
                    'post_id' => $parentComment->app_user_post_id,
                    'comment_id' => $parentComment->id,
                    'reply_id' => $reply->id,
                    'sender_app_user_id' => $appUser->id,
                ]
            );
        }

        return response()->json([
            'status' => true,
            'message' => 'Reply added successfully',
            'data' => $this->loadCommentResponse($reply->id, $appUser),
        ], 201);
    }

    public function update(UpdateCommentRequest $request, int $commentId): JsonResponse
    {
        /** @var AppUser $appUser */
        $appUser = $request->user();
        $comment = AppUserPostComment::query()->findOrFail($commentId);

        //abort_if($comment->app_user_id !== $appUser->id, 403, 'Unauthorized');

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
            'data' => $this->loadCommentResponse($comment->id, $appUser),
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

    private function withViewerState(Builder|Relation $query, ?AppUser $appUser): Builder|Relation
    {
        if (! $appUser) {
            return $query;
        }

        return $query->withExists([
            'likes as liked_by_me' => fn ($nested) => $nested->where('app_user_id', $appUser->id),
        ]);
    }

    private function loadCommentResponse(int $commentId, ?AppUser $appUser): AppUserPostComment
    {
        $comment = $this->withViewerState(AppUserPostComment::query(), $appUser)
            ->whereKey($commentId)
            ->with([
                'appUser',
                'parent.appUser',
                'replies' => fn ($query) => $this->withViewerState($query, $appUser)
                    ->with('appUser')
                    ->withCount('likes')
                    ->latest(),
            ])
            ->withCount(['likes', 'replies'])
            ->firstOrFail();

        $this->prepareCommentForResponse($comment);

        return $comment;
    }

    private function prepareCommentForResponse(AppUserPostComment $comment): void
    {
        $comment->liked_by_me = (bool) ($comment->liked_by_me ?? false);

        if (! $comment->relationLoaded('replies')) {
            return;
        }

        $comment->replies->each(function (AppUserPostComment $reply) {
            $reply->liked_by_me = (bool) ($reply->liked_by_me ?? false);
        });
    }
}