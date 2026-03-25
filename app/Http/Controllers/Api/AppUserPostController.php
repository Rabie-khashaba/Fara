<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\AppUserPost\StorePostRequest;
use App\Http\Requests\Api\AppUserPost\UpdatePostRequest;
use App\Models\AppUser;
use App\Models\AppUserActivity;
use App\Models\AppUserPost;
use App\Models\AppUserRepost;
use App\Services\AppUserPushNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class AppUserPostController extends Controller
{
    public function __construct(
        private readonly AppUserPushNotificationService $pushNotificationService
    ) {
    }

    public function allPosts(Request $request): JsonResponse
{
    $appUser = $request->user('sanctum');

    $followingIds = $appUser
        ? Cache::remember(
            "user:{$appUser->id}:following",
            now()->addMinutes(5),
            fn () => $appUser->following()
                ->pluck('following_app_user_id')
                ->map(fn ($id) => (int) $id)
                ->all()
        )
        : [];

    $followingLookup = array_fill_keys($followingIds, true);

    $posts = AppUserPost::query()
        ->visible()
        ->with(['appUser:id,name,username'])
        ->withCount(['likes', 'comments'])
        ->latest()
        ->cursorPaginate(10);

    $posts->through(function ($post) use ($followingLookup) {
        $post->is_following = isset($followingLookup[(int) $post->app_user_id]);
        return $post;
    });

    return response()->json([
        'status' => true,
        'data' => $posts,
        'next_cursor' => optional($posts->nextCursor())->encode(),
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
        $data['is_ghost'] = $request->boolean('is_ghost', false);

        $post = $appUser->posts()->create($data);

        $this->logActivity(
            $appUser,
            $data['is_ghost'] ? 'ghost_post_created' : 'post_created',
            $post,
            null,
            $data['is_ghost'] ? 'Created a ghost post' : 'Created a new post'
        );

        return response()->json([
            'status' => true,
            'message' => $data['is_ghost'] ? 'Ghost post created successfully' : 'Post created successfully',
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

        if ($request->has('is_ghost')) {
            $data['is_ghost'] = $request->boolean('is_ghost');
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

        if ($repost->wasRecentlyCreated && $originalPost->appUser) {
            $this->pushNotificationService->sendToUser(
                $originalPost->appUser,
                $appUser,
                $appUser->name,
                'reposted your post',
                [
                    'type' => 'post_repost',
                    'post_id' => $originalPost->id,
                    'repost_id' => $repost->id,
                    'sender_app_user_id' => $appUser->id,
                ]
            );
        }

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
            ->map(fn (UploadedFile $file) => $this->storeImageAsWebp($file))
            ->values()
            ->all();
    }

    private function storeImageAsWebp(UploadedFile $file): string
    {
        try {
            $manager = new ImageManager(new Driver());
            $image = $manager->read($file->getRealPath());
            $image = $image->scaleDown(width: 1600);
            $encoded = $image->toWebp(65);
            $path = 'app-user-posts/' . Str::uuid() . '.webp';

            Storage::disk('public')->put($path, (string) $encoded);

            return $path;
        } catch (\Throwable) {
            throw ValidationException::withMessages([
                'image' => ['The uploaded image could not be converted to WebP.'],
            ]);
        }
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
