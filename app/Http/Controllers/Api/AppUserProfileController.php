<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\AppUserProfile\UpdateProfileRequest;
use App\Models\AppUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

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

        return $this->updateProfile($request, $appUser);
    }

    public function updateById(UpdateProfileRequest $request, int $appUserId): JsonResponse
    {
        /** @var AppUser $appUser */
        $appUser = $request->user();

        abort_if($appUser->id !== $appUserId, 403, 'Unauthorized');

        return $this->updateProfile($request, $appUser);
    }

    public function show(int $appUserId): JsonResponse
    {
        return $this->profileResponse($appUserId, false);
    }

    public function destroy(Request $request): JsonResponse
    {
        /** @var AppUser|null $appUser */
        $appUser = $request->user();

        return $this->deleteProfile($appUser);
    }

    public function destroyById(Request $request, int $appUserId): JsonResponse
    {
        /** @var AppUser|null $appUser */
        $appUser = $request->user();

        if (! $appUser) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        abort_if($appUser->id !== $appUserId, 403, 'Unauthorized');

        return $this->deleteProfile($appUser);
    }

    private function deleteProfile(AppUser $appUser): JsonResponse
    {
        DB::transaction(function () use ($appUser): void {
            foreach (array_filter([$appUser->profile_image, $appUser->cover_photo]) as $path) {
                Storage::disk('public')->delete($path);
            }

            $appUser->tokens()->delete();
            $appUser->delete();
        });

        return response()->json([
            'status' => true,
            'message' => 'Profile deleted successfully',
        ]);
    }

    private function profileResponse(int $appUserId, bool $isMe): JsonResponse
    {
        $appUser = AppUser::query()
            ->with([
                'posts' => fn ($query) => $query
                    ->visible()
                    ->when(! $isMe, fn ($postsQuery) => $postsQuery->where('is_ghost', false))
                    ->with(['repostedPost.appUser:id,name,username'])
                    ->withCount(['likes', 'comments', 'sharedPosts', 'savedPosts'])
                    ->latest(),
                'comments' => fn ($query) => $query
                    ->with(['post:id,content,location,app_user_id', 'post.appUser:id,name,username'])
                    ->withCount('likes')
                    ->latest(),
                'likes' => fn ($query) => $query
                    ->with(['post' => fn ($postQuery) => $postQuery->withCount(['likes', 'comments', 'sharedPosts', 'savedPosts'])])
                    ->latest(),
                'sharedPosts' => fn ($query) => $query
                    ->with(['post' => fn ($postQuery) => $postQuery->with(['appUser:id,name,username'])->withCount(['likes', 'comments', 'sharedPosts', 'savedPosts'])])
                    ->latest(),
                'savedPosts' => fn ($query) => $query
                    ->with(['post' => fn ($postQuery) => $postQuery->with(['appUser:id,name,username'])->withCount(['likes', 'comments', 'sharedPosts', 'savedPosts'])])
                    ->latest(),
                'followers.follower:id,name,username,email,phone',
                'following.following:id,name,username,email,phone',
                'socialAccounts:id,app_user_id,provider,provider_email,provider_avatar,created_at',
            ])
            ->findOrFail($appUserId);

        $posts = $appUser->posts->map(fn ($post) => [
            'id' => $post->id,
            'content' => $post->content,
            'is_ghost' => (bool) $post->is_ghost,
            'image' => $post->image,
            'image_url' => $post->image_url,
            'image_urls' => $post->image_urls,
            'background_color' => $post->background_color,
            'location' => $post->location,
            'status' => $post->status,
            'published_at' => $post->published_at,
            'created_at' => $post->created_at,
            'likes_count' => $post->likes_count,
            'comments_count' => $post->comments_count,
            'shares_count' => $post->shared_posts_count,
            'saved_count' => $post->saved_posts_count,
            'is_repost' => (bool) $post->reposted_post_id,
            'reposted_post' => $post->repostedPost ? [
                'id' => $post->repostedPost->id,
                'content' => $post->repostedPost->content,
                'image' => $post->repostedPost->image,
                'image_url' => $post->repostedPost->image_url,
                'image_urls' => $post->repostedPost->image_urls,
                'background_color' => $post->repostedPost->background_color,
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
            'likes_count' => $comment->likes_count,
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
                'image_urls' => $like->post?->image_urls,
                'background_color' => $like->post?->background_color,
                'location' => $like->post?->location,
                'status' => $like->post?->status,
                'likes_count' => $like->post?->likes_count,
                'comments_count' => $like->post?->comments_count,
                'shares_count' => $like->post?->shared_posts_count,
                'saved_count' => $like->post?->saved_posts_count,
                'author_id' => $like->post?->app_user_id,
            ],
        ]);

        $sharedPosts = $appUser->sharedPosts->map(fn ($sharedPost) => [
            'shared_post_id' => $sharedPost->id,
            'shared_at' => $sharedPost->created_at,
            'post' => [
                'id' => $sharedPost->post?->id,
                'content' => $sharedPost->post?->content,
                'image' => $sharedPost->post?->image,
                'image_url' => $sharedPost->post?->image_url,
                'image_urls' => $sharedPost->post?->image_urls,
                'background_color' => $sharedPost->post?->background_color,
                'location' => $sharedPost->post?->location,
                'status' => $sharedPost->post?->status,
                'likes_count' => $sharedPost->post?->likes_count,
                'comments_count' => $sharedPost->post?->comments_count,
                'shares_count' => $sharedPost->post?->shared_posts_count,
                'saved_count' => $sharedPost->post?->saved_posts_count,
                'author_id' => $sharedPost->post?->app_user_id,
            ],
        ]);

        $savedPosts = $appUser->savedPosts->map(fn ($savedPost) => [
            'saved_post_id' => $savedPost->id,
            'saved_at' => $savedPost->created_at,
            'post' => [
                'id' => $savedPost->post?->id,
                'content' => $savedPost->post?->content,
                'image' => $savedPost->post?->image,
                'image_url' => $savedPost->post?->image_url,
                'image_urls' => $savedPost->post?->image_urls,
                'background_color' => $savedPost->post?->background_color,
                'location' => $savedPost->post?->location,
                'status' => $savedPost->post?->status,
                'likes_count' => $savedPost->post?->likes_count,
                'comments_count' => $savedPost->post?->comments_count,
                'shares_count' => $savedPost->post?->shared_posts_count,
                'saved_count' => $savedPost->post?->saved_posts_count,
                'author_id' => $savedPost->post?->app_user_id,
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
        $postsSharesCount = (int) $appUser->posts->sum('shared_posts_count');
        $postsSavedCount = (int) $appUser->posts->sum('saved_posts_count');

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
                    'profile_image' => $appUser->profile_image,
                    'profile_image_url' => $appUser->profile_image_url,
                    'cover_photo' => $appUser->cover_photo,
                    'cover_photo_url' => $appUser->cover_photo_url,
                    'created_at' => $appUser->created_at,
                    'updated_at' => $appUser->updated_at,
                    'social_accounts' => $appUser->socialAccounts,
                    'is_me' => $isMe,
                ],
                'posts' => $posts,
                'comments' => $comments,
                'liked_posts' => $likedPosts,
                'shared_posts' => $sharedPosts,
                'saved_posts' => $savedPosts,
                'followers' => $followers,
                'following' => $following,
                'counts' => [
                    'posts' => $appUser->posts->count(),
                    'likes' => $postsLikesCount,
                    'comments' => $postsCommentsCount,
                    'shares' => $postsSharesCount,
                    'saved' => $postsSavedCount,
                    'followers' => $appUser->followers->count(),
                    'following' => $appUser->following->count(),
                    'my_comments' => $appUser->comments->count(),
                    'my_likes' => $appUser->likes->count(),
                    'my_shared' => $appUser->sharedPosts->count(),
                    'my_saved' => $appUser->savedPosts->count(),
                ],
            ],
        ]);
    }

    private function storeProfileAsset(?UploadedFile $file, ?string $currentPath = null): ?string
    {
        if (! $file) {
            return $currentPath;
        }

        if ($currentPath) {
            Storage::disk('public')->delete($currentPath);
        }

        return $file->store('app-user-profiles', 'public');
    }

    private function updateProfile(UpdateProfileRequest $request, AppUser $appUser): JsonResponse
    {
        $data = $request->validated();

        if ($request->hasFile('profile_image')) {
            $data['profile_image'] = $this->storeProfileAsset(
                $request->file('profile_image'),
                $appUser->profile_image
            );
        }

        if ($request->hasFile('cover_photo')) {
            $data['cover_photo'] = $this->storeProfileAsset(
                $request->file('cover_photo'),
                $appUser->cover_photo
            );
        }

        $appUser->update($data);

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
                    'profile_image' => $appUser->profile_image,
                    'profile_image_url' => $appUser->profile_image_url,
                    'cover_photo' => $appUser->cover_photo,
                    'cover_photo_url' => $appUser->cover_photo_url,
                    'created_at' => $appUser->created_at,
                    'updated_at' => $appUser->updated_at,
                ],
            ],
        ]);
    }
}
