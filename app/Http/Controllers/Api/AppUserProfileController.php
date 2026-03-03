<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\AppUserProfile\UpdateProfileRequest;
use App\Models\AppUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppUserProfileController extends Controller
{
    public function me(Request $request): JsonResponse
    {
        /** @var AppUser $appUser */
        $appUser = $request->user();

        return $this->profileResponse($appUser->id, true);
    }

    public function update(UpdateProfileRequest $request): JsonResponse
    {
        /** @var AppUser $appUser */
        $appUser = $request->user();

        $appUser->update($request->validated());

        return response()->json([
            'status' => true,
            'message' => 'Profile updated successfully',
            'data' => [
                'profile' => [
                    'id' => $appUser->id,
                    'name' => $appUser->name,
                    'username' => $appUser->username,
                    'email' => $appUser->email,
                    'phone' => $appUser->phone,
                    'provider' => $appUser->provider,
                    'provider_id' => $appUser->provider_id,
                    'is_active' => $appUser->is_active,
                    'created_at' => $appUser->created_at,
                    'updated_at' => $appUser->updated_at,
                ],
            ],
        ]);
    }

    public function show(int $appUserId): JsonResponse
    {
        return $this->profileResponse($appUserId, false);
    }

    private function profileResponse(int $appUserId, bool $isMe): JsonResponse
    {
        $appUser = AppUser::query()
            ->with([
                'posts' => fn ($query) => $query
                    ->with(['repostedPost.appUser:id,name,username'])
                    ->withCount(['likes', 'comments'])
                    ->latest(),
                'comments' => fn ($query) => $query
                    ->with(['post:id,content,location,app_user_id', 'post.appUser:id,name,username'])
                    ->latest(),
                'likes' => fn ($query) => $query
                    ->with(['post' => fn ($postQuery) => $postQuery->withCount(['likes', 'comments'])])
                    ->latest(),
                'followers.follower:id,name,username,email,phone',
                'following.following:id,name,username,email,phone',
                'socialAccounts:id,app_user_id,provider,provider_email,provider_avatar,created_at',
            ])
            ->findOrFail($appUserId);

        $posts = $appUser->posts->map(fn ($post) => [
            'id' => $post->id,
            'content' => $post->content,
            'image' => $post->image,
            'image_url' => $post->image_url,
            'location' => $post->location,
            'status' => $post->status,
            'published_at' => $post->published_at,
            'created_at' => $post->created_at,
            'likes_count' => $post->likes_count,
            'comments_count' => $post->comments_count,
            'is_repost' => (bool) $post->reposted_post_id,
            'reposted_post' => $post->repostedPost ? [
                'id' => $post->repostedPost->id,
                'content' => $post->repostedPost->content,
                'image' => $post->repostedPost->image,
                'image_url' => $post->repostedPost->image_url,
                'location' => $post->repostedPost->location,
                'author' => [
                    'id' => $post->repostedPost->appUser?->id,
                    'name' => $post->repostedPost->appUser?->name,
                    'username' => $post->repostedPost->appUser?->username,
                ],
            ] : null,
        ]);

        $comments = $appUser->comments->map(fn ($comment) => [
            'id' => $comment->id,
            'comment' => $comment->comment,
            'created_at' => $comment->created_at,
            'post' => [
                'id' => $comment->post?->id,
                'content' => $comment->post?->content,
                'location' => $comment->post?->location,
                'author' => [
                    'id' => $comment->post?->appUser?->id,
                    'name' => $comment->post?->appUser?->name,
                    'username' => $comment->post?->appUser?->username,
                ],
            ],
        ]);

        $likedPosts = $appUser->likes->map(fn ($like) => [
            'like_id' => $like->id,
            'liked_at' => $like->created_at,
            'post' => [
                'id' => $like->post?->id,
                'content' => $like->post?->content,
                'image' => $like->post?->image,
                'image_url' => $like->post?->image_url,
                'location' => $like->post?->location,
                'status' => $like->post?->status,
                'likes_count' => $like->post?->likes_count,
                'comments_count' => $like->post?->comments_count,
                'author_id' => $like->post?->app_user_id,
            ],
        ]);

        $followers = $appUser->followers->map(fn ($follow) => [
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

        $following = $appUser->following->map(fn ($follow) => [
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

        $postsLikesCount = (int) $appUser->posts->sum('likes_count');
        $postsCommentsCount = (int) $appUser->posts->sum('comments_count');

        return response()->json([
            'status' => true,
            'data' => [
                'profile' => [
                    'id' => $appUser->id,
                    'name' => $appUser->name,
                    'username' => $appUser->username,
                    'email' => $appUser->email,
                    'phone' => $appUser->phone,
                    'provider' => $appUser->provider,
                    'provider_id' => $appUser->provider_id,
                    'is_active' => $appUser->is_active,
                    'created_at' => $appUser->created_at,
                    'updated_at' => $appUser->updated_at,
                    'social_accounts' => $appUser->socialAccounts,
                    'is_me' => $isMe,
                ],
                'posts' => $posts,
                'comments' => $comments,
                'liked_posts' => $likedPosts,
                'followers' => $followers,
                'following' => $following,
                'counts' => [
                    'posts' => $appUser->posts->count(),
                    'likes' => $postsLikesCount,
                    'comments' => $postsCommentsCount,
                    'followers' => $appUser->followers->count(),
                    'following' => $appUser->following->count(),
                    'my_comments' => $appUser->comments->count(),
                    'my_likes' => $appUser->likes->count(),
                ],
            ],
        ]);
    }
}
