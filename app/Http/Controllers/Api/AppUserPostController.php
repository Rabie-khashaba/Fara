<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\AppUserPost\StorePostRequest;
use App\Http\Requests\Api\AppUserPost\UpdatePostRequest;
use App\Models\AppUser;
use App\Models\AppUserActivity;
use App\Models\AppUserPost;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class AppUserPostController extends Controller
{
    public function allPosts(Request $request): JsonResponse
    {
        /** @var AppUser|null $appUser */
        $appUser = $request->user('sanctum');

        $followingIds = $appUser?->following()
            ->pluck('following_app_user_id')
            ->map(fn ($id) => (int) $id)
            ->all() ?? [];

        $posts = AppUserPost::query()
            ->with(['appUser:id,name,username', 'repostedPost.appUser:id,name,username'])
            ->withCount(['likes', 'comments', 'reposts'])
            ->latest()
            ->get()
            ->map(function (AppUserPost $post) use ($followingIds) {
                $post->is_following = in_array((int) $post->app_user_id, $followingIds, true);

                return $post;
            });

        return response()->json([
            'status' => true,
            'data' => $posts,
        ]);
    }

    public function myPosts(Request $request): JsonResponse
    {
        /** @var AppUser $appUser */
        $appUser = $request->user();

        $posts = $appUser->posts()
            ->withCount(['likes', 'comments'])
            ->latest()
            ->get();

        return response()->json([
            'status' => true,
            'data' => $posts,
        ]);
    }

    public function myReposts(Request $request): JsonResponse
    {
        /** @var AppUser $appUser */
        $appUser = $request->user();

        $posts = $appUser->posts()
            ->whereNotNull('reposted_post_id')
            ->with(['appUser:id,name,username', 'repostedPost.appUser:id,name,username'])
            ->withCount(['likes', 'comments', 'reposts'])
            ->latest()
            ->get();

        return response()->json([
            'status' => true,
            'data' => $posts,
        ]);
    }

    public function followingPosts(Request $request): JsonResponse
    {
        /** @var AppUser $appUser */
        $appUser = $request->user();

        $followingIds = $appUser->following()->pluck('following_app_user_id');

        $posts = AppUserPost::query()
            ->whereIn('app_user_id', $followingIds)
            ->with(['appUser:id,name,username', 'repostedPost.appUser:id,name,username'])
            ->withCount(['likes', 'comments', 'reposts'])
            ->latest()
            ->get();

        return response()->json([
            'status' => true,
            'data' => $posts,
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        return $this->myPosts($request);
    }

    public function store(StorePostRequest $request): JsonResponse
    {
        /** @var AppUser $appUser */
        $appUser = $request->user();
        $data = $request->validated();
        $data['image'] = $this->storeImage($request->file('image'));

        $post = $appUser->posts()->create($data);

        $this->logActivity($appUser, 'post_created', $post, null, 'Created a new post');

        return response()->json([
            'status' => true,
            'message' => 'Post created successfully',
            'data' => $post->loadCount(['likes', 'comments']),
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $post = AppUserPost::query()->findOrFail($id);

        return response()->json([
            'status' => true,
            'data' => $post->load(['appUser', 'comments.appUser', 'repostedPost.appUser'])->loadCount(['likes', 'comments', 'reposts']),
        ]);
    }

    public function update(UpdatePostRequest $request, int $id): JsonResponse
    {
        /** @var AppUser $appUser */
        $appUser = $request->user();
        $post = AppUserPost::query()->findOrFail($id);

        abort_if($post->app_user_id !== $appUser->id, 403, 'Unauthorized');

        $data = $request->validated();

        if ($request->hasFile('image')) {
            $data['image'] = $this->storeImage($request->file('image'), $post);
        }

        $post->update($data);

        $this->logActivity($appUser, 'post_updated', $post, null, 'Updated a post');

        return response()->json([
            'status' => true,
            'message' => 'Post updated successfully',
            'data' => $post->fresh()->loadCount(['likes', 'comments']),
        ]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        /** @var AppUser $appUser */
        $appUser = $request->user();
        $post = AppUserPost::query()->findOrFail($id);

        abort_if($post->app_user_id !== $appUser->id, 403, 'Unauthorized');

        $this->logActivity($appUser, 'post_deleted', $post, null, 'Deleted a post');
        $post->delete();

        return response()->json([
            'status' => true,
            'message' => 'Post deleted successfully',
        ]);
    }

    public function repost(Request $request, int $id): JsonResponse
    {
        /** @var AppUser $appUser */
        $appUser = $request->user();
        $originalPost = AppUserPost::query()->findOrFail($id);

        $repost = $appUser->posts()->create([
            'content' => $originalPost->content,
            'image' => $originalPost->image,
            'location' => $originalPost->location,
            'status' => 'published',
            'published_at' => now(),
            'reposted_post_id' => $originalPost->id,
        ]);

        $this->logActivity($appUser, 'reposted_post', $repost, $originalPost->appUser, 'Reposted a post');

        return response()->json([
            'status' => true,
            'message' => 'Post reposted successfully',
            'data' => $repost->load(['appUser', 'repostedPost.appUser'])->loadCount(['likes', 'comments', 'reposts']),
        ], 201);
    }

    private function logActivity(AppUser $appUser, string $type, ?AppUserPost $post, ?AppUser $subject, ?string $description): void
    {
        AppUserActivity::create([
            'app_user_id' => $appUser->id,
            'type' => $type,
            'app_user_post_id' => $post?->id,
            'subject_app_user_id' => $subject?->id,
            'description' => $description,
        ]);
    }

    private function storeImage(?UploadedFile $image, ?AppUserPost $post = null): ?string
    {
        if (! $image) {
            return $post?->image;
        }

        if ($post?->image) {
            Storage::disk('public')->delete($post->image);
        }

        return $image->store('app-user-posts', 'public');
    }
}
