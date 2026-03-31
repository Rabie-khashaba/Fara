<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\AppUserPost\StorePostRequest;
use App\Http\Requests\Api\AppUserPost\UpdatePostRequest;
use App\Models\AppUser;
use App\Models\AppUserActivity;
use App\Models\AppUserCheckIn;
use App\Models\AppUserCheckInCity;
use App\Models\AppUserPost;
use App\Models\AppUserRepost;
use App\Services\AppUserPushNotificationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
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
        /** @var AppUser|null $appUser */
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

        $posts = $this->withViewerState(AppUserPost::query()->visible(), $appUser)
            ->with(['appUser:id,name,username'])
            ->withCount(['likes', 'comments', 'reposts', 'sharedPosts', 'savedPosts'])
            ->latest()
            ->cursorPaginate(10);

        $posts->through(function (AppUserPost $post) use ($followingLookup) {
            $this->ensurePostViewerStateDefaults($post);
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

        $posts = $this->withViewerState($appUser->posts()->visible(), $appUser)
            ->withCount(['likes', 'comments', 'reposts', 'sharedPosts', 'savedPosts'])
            ->latest()
            ->get()
            ->each(fn (AppUserPost $post) => $this->ensurePostViewerStateDefaults($post));

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
                'post' => fn ($query) => $this->withViewerState($query->visible(), $appUser)
                    ->with(['appUser:id,name,username'])
                    ->withCount(['likes', 'comments', 'reposts', 'sharedPosts', 'savedPosts']),
            ])
            ->latest()
            ->get();

        $posts->each(function ($repost) {
            if ($repost->post instanceof AppUserPost) {
                $this->ensurePostViewerStateDefaults($repost->post);
            }
        });

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

        $posts = $this->withViewerState(AppUserPost::query()->visible(), $appUser)
            ->whereIn('app_user_id', $followingIds)
            ->with(['appUser:id,name,username', 'repostedPost.appUser:id,name,username'])
            ->withCount(['likes', 'comments', 'reposts', 'sharedPosts', 'savedPosts'])
            ->latest()
            ->get()
            ->each(fn (AppUserPost $post) => $this->ensurePostViewerStateDefaults($post));

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
        $this->createCheckInFromPostData($appUser, $data);

        $this->logActivity(
            $appUser,
            $data['is_ghost'] ? 'ghost_post_created' : 'post_created',
            $post,
            null,
            $data['is_ghost'] ? 'Created a ghost post' : 'Created a new post'
        );

        $post = $this->withViewerState(AppUserPost::query(), $appUser)
            ->whereKey($post->id)
            ->withCount(['likes', 'comments', 'reposts', 'sharedPosts', 'savedPosts'])
            ->firstOrFail();
        $this->ensurePostViewerStateDefaults($post);

        return response()->json([
            'status' => true,
            'message' => $data['is_ghost'] ? 'Ghost post created successfully' : 'Post created successfully',
            'data' => $post,
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
        $this->createCheckInFromPostData($appUser, $data);

        $this->logActivity($appUser, 'ghost_post_created', $post, null, 'Created a ghost post');

        $post = $this->withViewerState(AppUserPost::query(), $appUser)
            ->whereKey($post->id)
            ->withCount(['likes', 'comments', 'reposts', 'sharedPosts', 'savedPosts'])
            ->firstOrFail();
        $this->ensurePostViewerStateDefaults($post);

        return response()->json([
            'status' => true,
            'message' => 'Ghost post created successfully',
            'data' => $post,
        ], 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        /** @var AppUser|null $appUser */
        $appUser = $request->user();

        $post = $this->withViewerState(AppUserPost::query()->visible(), $appUser)
            ->whereKey($id)
            ->firstOrFail();

        $post->load([
            'appUser',
            'comments' => fn ($query) => $this->withCommentViewerState($query, $appUser)
                ->with('appUser')
                ->withCount('likes'),
            'repostedPost.appUser',
        ])->loadCount(['likes', 'comments', 'reposts', 'sharedPosts', 'savedPosts']);

        $this->ensurePostViewerStateDefaults($post);
        $post->comments->each(fn ($comment) => $this->ensureCommentViewerStateDefaults($comment));

        return response()->json([
            'status' => true,
            'data' => $post,
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

        $post = $this->withViewerState(AppUserPost::query(), $appUser)
            ->whereKey($post->id)
            ->withCount(['likes', 'comments', 'reposts', 'sharedPosts', 'savedPosts'])
            ->firstOrFail();
        $this->ensurePostViewerStateDefaults($post);

        return response()->json([
            'status' => true,
            'message' => 'Post updated successfully',
            'data' => $post,
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

        $response = $repost->load([
            'appUser:id,name,username',
            'post' => fn ($query) => $this->withViewerState($query, $appUser)
                ->with(['appUser:id,name,username'])
                ->withCount(['likes', 'comments', 'reposts', 'sharedPosts', 'savedPosts']),
        ]);

        if ($response->post instanceof AppUserPost) {
            $this->ensurePostViewerStateDefaults($response->post);
        }

        return response()->json([
            'status' => true,
            'message' => 'Post reposted successfully',
            'data' => $response,
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

    private function withViewerState(Builder|Relation $query, ?AppUser $appUser): Builder|Relation
    {
        if (! $appUser) {
            return $query;
        }

        return $query->withExists([
            'likes as liked_by_me' => fn ($nested) => $nested->where('app_user_id', $appUser->id),
            'reposts as reposted_by_me' => fn ($nested) => $nested->where('app_user_id', $appUser->id),
            'savedPosts as saved_by_me' => fn ($nested) => $nested->where('app_user_id', $appUser->id),
        ]);
    }

    private function withCommentViewerState(Builder|Relation $query, ?AppUser $appUser): Builder|Relation
    {
        if (! $appUser) {
            return $query;
        }

        return $query->withExists([
            'likes as liked_by_me' => fn ($nested) => $nested->where('app_user_id', $appUser->id),
        ]);
    }

    private function ensurePostViewerStateDefaults(AppUserPost $post): void
    {
        $post->liked_by_me = (bool) ($post->liked_by_me ?? false);
        $post->reposted_by_me = (bool) ($post->reposted_by_me ?? false);
        $post->saved_by_me = (bool) ($post->saved_by_me ?? false);
    }

    private function ensureCommentViewerStateDefaults($comment): void
    {
        $comment->liked_by_me = (bool) ($comment->liked_by_me ?? false);
    }

    private function createCheckInFromPostData(AppUser $appUser, array $data): void
    {
        if (! isset($data['latitude'], $data['longitude'])) {
            return;
        }

        $latitude = (float) $data['latitude'];
        $longitude = (float) $data['longitude'];

        $city = $this->resolveCityForCheckIn(
            $latitude,
            $longitude,
            $data['city_name'] ?? null
        );

        AppUserCheckIn::query()->create([
            'app_user_id' => $appUser->id,
            'app_user_check_in_city_id' => $city->id,
            'place_name' => $data['location'] ?? null,
            'category' => $data['category'] ?? 'other',
            'latitude' => $latitude,
            'longitude' => $longitude,
            'checked_in_at' => $data['checked_in_at'] ?? now(),
        ]);
    }

    private function resolveCityForCheckIn(
        float $latitude,
        float $longitude,
        ?string $cityName
    ): AppUserCheckInCity {
        $normalizedCityName = $cityName ? trim($cityName) : null;

        if ($normalizedCityName) {
            $cityByName = AppUserCheckInCity::query()
                ->whereRaw('LOWER(name) = ?', [Str::lower($normalizedCityName)])
                ->first();

            if ($cityByName) {
                return $cityByName;
            }
        }

        $city = AppUserCheckInCity::query()
            ->where('country_code', 'SA')
            ->get()
            ->first(function (AppUserCheckInCity $city) use ($latitude, $longitude) {
                return $this->distanceKmForCheckIn($latitude, $longitude, $city->latitude, $city->longitude) <= $city->radius_km;
            });

        if ($city) {
            return $city;
        }

        $name = $normalizedCityName ?: sprintf('Custom City %.4f, %.4f', $latitude, $longitude);

        return AppUserCheckInCity::query()->create([
            'name' => $name,
            'slug' => Str::slug($name) . '-' . Str::lower(Str::random(6)),
            'country_code' => 'SA',
            'latitude' => $latitude,
            'longitude' => $longitude,
            'radius_km' => 30,
            'is_predefined' => false,
        ]);
    }

    private function distanceKmForCheckIn(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371;
        $latDelta = deg2rad($lat2 - $lat1);
        $lngDelta = deg2rad($lng2 - $lng1);

        $a = sin($latDelta / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($lngDelta / 2) ** 2;

        return 2 * $earthRadius * asin(min(1, sqrt($a)));
    }
}
