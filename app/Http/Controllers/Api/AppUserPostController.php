<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\AppUserPost\StorePostRequest;
use App\Http\Requests\Api\AppUserPost\UpdatePostRequest;
use App\Models\AppUser;
use App\Models\AppUserActivity;
use App\Models\AppUserPost;
use App\Models\AppUserRepost;
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
            ->visible()
            ->with(['appUser:id,name,username', 'repostedPost.appUser:id,name,username'])
            ->withCount(['likes', 'comments', 'reposts', 'sharedPosts', 'savedPosts'])
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
            ->visible()
            ->withCount(['likes', 'comments', 'sharedPosts', 'savedPosts'])
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

        $posts = $appUser->reposts()
            ->with([
                'post' => fn ($query) => $query
                    ->visible()
                    ->with(['appUser:id,name,username'])
                    ->withCount(['likes', 'comments', 'reposts', 'sharedPosts', 'savedPosts']),
            ])
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
            ->visible()
            ->whereIn('app_user_id', $followingIds)
            ->with(['appUser:id,name,username', 'repostedPost.appUser:id,name,username'])
            ->withCount(['likes', 'comments', 'reposts', 'sharedPosts', 'savedPosts'])
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
        $data['image'] = $this->storeImages($request->file('image'));
        $data['is_ghost'] = false;

        $post = $appUser->posts()->create($data);

        $this->logActivity($appUser, 'post_created', $post, null, 'Created a new post');

        return response()->json([
            'status' => true,
            'message' => 'Post created successfully',
            'data' => $post->loadCount(['likes', 'comments', 'sharedPosts', 'savedPosts']),
        ], 201);
    }

    public function storeGhost(StorePostRequest $request): JsonResponse
    {
        /** @var AppUser $appUser */
        $appUser = $request->user();
        $data = $request->validated();
        $data['image'] = $this->storeImages($request->file('image'));
        $data['is_ghost'] = true;

        $post = $appUser->posts()->create($data);

        $this->logActivity($appUser, 'ghost_post_created', $post, null, 'Created a ghost post');

        return response()->json([
            'status' => true,
            'message' => 'Ghost post created successfully',
            'data' => $post->loadCount(['likes', 'comments', 'sharedPosts', 'savedPosts']),
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $post = AppUserPost::query()->visible()->findOrFail($id);

        return response()->json([
            'status' => true,
            'data' => $post->load(['appUser', 'comments.appUser', 'repostedPost.appUser'])->loadCount(['likes', 'comments', 'reposts', 'sharedPosts', 'savedPosts']),
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
            $data['image'] = $this->storeImages($request->file('image'), $post);
        }

        $post->update($data);

        $this->logActivity($appUser, 'post_updated', $post, null, 'Updated a post');

        return response()->json([
            'status' => true,
            'message' => 'Post updated successfully',
            'data' => $post->fresh()->loadCount(['likes', 'comments', 'sharedPosts', 'savedPosts']),
        ]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        /** @var AppUser $appUser */
        $appUser = $request->user();
        $post = AppUserPost::query()->findOrFail($id);

        abort_if($post->app_user_id !== $appUser->id, 403, 'Unauthorized');

        $this->logActivity($appUser, 'post_deleted', $post, null, 'Deleted a post');
        $this->deletePostImages($post->image);
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
        $originalPost = AppUserPost::query()->visible()->findOrFail($id);

        $repost = AppUserRepost::query()->firstOrCreate([
            'app_user_id' => $appUser->id,
            'app_user_post_id' => $originalPost->id,
        ]);

        $this->logActivity($appUser, 'reposted_post', $originalPost, $originalPost->appUser, 'Reposted a post');

        return response()->json([
            'status' => true,
            'message' => 'Post reposted successfully',
            'data' => $repost->load([
                'appUser:id,name,username',
                'post' => fn ($query) => $query
                    ->with(['appUser:id,name,username'])
                    ->withCount(['likes', 'comments', 'reposts', 'sharedPosts', 'savedPosts']),
            ]),
        ], $repost->wasRecentlyCreated ? 201 : 200);
    }

    public function destroyRepost(Request $request, int $id): JsonResponse
    {
        /** @var AppUser $appUser */
        $appUser = $request->user();
        $post = AppUserPost::query()->findOrFail($id);

        $repost = AppUserRepost::query()
            ->where('app_user_id', $appUser->id)
            ->where('app_user_post_id', $post->id)
            ->firstOrFail();

        $repost->delete();

        $this->logActivity($appUser, 'unreposted_post', $post, $post->appUser, 'Removed a repost');

        return response()->json([
            'status' => true,
            'message' => 'Repost removed successfully',
        ]);
    }

    private function logActivity(AppUser $appUser, string $type, ?AppUserPost $post, ?AppUser $subject, ?string $description): void
    {
        AppUserActivity::create([
            'app_user_id' => $appUser->id,
            'type' => $type,
            'app_user_post_id' => $post?->id,
            'subject_app_user_id' => $subject?->id,
            'description' => $description,
            'meta' => [
                'subject_name' => $subject?->name,
                'post_excerpt' => $post?->content,
            ],
        ]);
    }

    private function storeImages(array|UploadedFile|null $images, ?AppUserPost $post = null): ?array
    {
        if (! $images) {
            return $post?->image;
        }

        $this->deletePostImages($post?->image);

        $files = $images instanceof UploadedFile ? [$images] : $images;

        return collect($files)
            ->filter(fn ($file) => $file instanceof UploadedFile)
            ->map(fn (UploadedFile $file) => $file->store('app-user-posts', 'public'))
            ->values()
            ->all();
    }

    private function deletePostImages(array|string|null $images): void
    {
        if (empty($images)) {
            return;
        }

        $paths = is_array($images) ? $images : [$images];

        Storage::disk('public')->delete(array_filter($paths));
    }
}
