@extends('layouts.vertical', ['title' => 'App User Details', 'subTitle' => 'App Users'])

@php
    use Illuminate\Support\Str;

    $posts = $appUser->posts;
    $followers = $appUser->followers;
    $following = $appUser->following;
    $activities = $appUser->activities->take(6)->map(function ($activity) {
        $map = [
            'liked_post' => ['Like', 'badge-soft-success', 'bg-success', 'LK'],
            'commented_on_post' => ['Comment', 'badge-soft-danger', 'bg-danger', 'CM'],
            'followed_user' => ['Follow', 'badge-soft-warning', 'bg-warning', 'FW'],
            'post_created' => ['Post', 'badge-soft-primary', 'bg-primary', 'PT'],
            'post_updated' => ['Update', 'badge-soft-primary', 'bg-primary', 'UP'],
            'reposted_post' => ['Repost', 'badge-soft-warning', 'bg-warning', 'RP'],
        ];

        [$type, $badge, $iconClass, $iconText] = $map[$activity->type] ?? ['Activity', 'badge-soft-secondary', 'bg-secondary', 'AC'];

        return [
            'time' => $activity->created_at?->format('H:i') ?: '--:--',
            'title' => $activity->description ?: Str::headline(str_replace('_', ' ', $activity->type)),
            'type' => $type,
            'badge' => $badge,
            'icon_class' => $iconClass,
            'icon_text' => $iconText,
        ];
    });

    $taskCompletion = min(100, ($posts->count() * 20) ?: 0);
    $commentsActivity = min(100, $posts->sum('comments_count') * 10);
    $likesEngagement = min(100, $posts->sum('likes_count') * 10);
    $followersGrowth = min(100, $followers->count() * 10);
@endphp

@section('content')
@if (session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
@endif

<div class="row">
    <div class="col-xxl-4">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="position-relative">
                        <img src="/images/small/img-6.jpg" alt="cover" class="card-img rounded-bottom-0" height="200" />
                        <img src="/images/users/avatar-1.jpg" alt="avatar" class="avatar-lg rounded-circle position-absolute top-100 start-0 translate-middle-y ms-3 border border-light border-3" />
                    </div>
                    <div class="card-body mt-4">
                        <div class="d-flex align-items-center">
                            <div class="d-block">
                                <h4 class="mb-1">{{ $appUser->name }}</h4>
                                <p class="fs-14 mb-0">{{ '@' . ($appUser->username ?: Str::slug($appUser->name, '.')) }}</p>
                            </div>
                            <div class="ms-auto">
                                <div class="dropdown">
                                    <a href="javascript:void(0);" class="dropdown-toggle arrow-none" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="bx bx-dots-vertical-rounded fs-18 text-dark"></i>
                                    </a>
                                    <div class="dropdown-menu dropdown-menu-end">
                                        <a href="{{ route('app-users.edit', $appUser) }}" class="dropdown-item">
                                            <i class="bx bx-edit-alt me-2"></i>Edit User
                                        </a>
                                        <form method="POST" action="{{ route('app-users.toggle-status', $appUser) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="dropdown-item">
                                                <i class="bx {{ $appUser->is_active ? 'bx-block' : 'bx-check-circle' }} me-2"></i>
                                                {{ $appUser->is_active ? 'Deactivate User' : 'Activate User' }}
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-12">
                                <h5 class="card-title badge bg-light text-secondary py-1 px-2 fs-13 mb-3 border-start border-secondary border-2 rounded-1">
                                    About User
                                </h5>
                                <p class="fs-15 mb-0 text-muted">
                                    Dashboard app user profile in the same style. This page shows profile details,
                                    followers, activities, and posts in the same dashboard layout.
                                </p>
                                <div class="mt-3">
                                    <div class="d-flex gap-2 flex-wrap">
                                        <span class="badge text-secondary py-1 px-2 fs-12 border rounded-1">{{ $appUser->provider ?: 'Manual' }}</span>
                                        <span class="badge text-secondary py-1 px-2 fs-12 border rounded-1">{{ $appUser->email ?: 'No Email' }}</span>
                                        <span class="badge text-secondary py-1 px-2 fs-12 border rounded-1">{{ $appUser->is_active ? 'Active' : 'Inactive' }}</span>
                                        <span class="badge text-secondary py-1 px-2 fs-12 border rounded-1">User ID: {{ $appUser->id }}</span>
                                        <span class="badge text-secondary py-1 px-2 fs-12 border rounded-1">Followers: {{ $followers->count() }}</span>
                                        <span class="badge text-secondary py-1 px-2 fs-12 border rounded-1">Following: {{ $following->count() }}</span>
                                    </div>
                                </div>
                                <div class="mt-4">
                                    <h5 class="text-dark fw-medium">Links :</h5>
                                    <a href="#!" class="text-primary text-decoration-underline">https://app-user-profile.local</a>
                                    <p class="mb-0 mt-1">
                                        <a href="#!" class="text-primary text-decoration-underline">https://fara-app.local/{{ $appUser->username ?: Str::slug($appUser->name, '-') }}</a>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-light-subtle">
                        <div class="row g-2 mb-1">
                            <div class="col-lg-6">
                                <a href="{{ route('app-users.edit', $appUser) }}" class="btn btn-primary d-flex align-items-center justify-content-center gap-1 w-100">
                                    <iconify-icon icon="iconamoon:edit-duotone"></iconify-icon>
                                    Edit
                                </a>
                            </div>
                            <div class="col-lg-6">
                                <form method="POST" action="{{ route('app-users.toggle-status', $appUser) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn {{ $appUser->is_active ? 'btn-danger' : 'btn-success' }} d-flex align-items-center justify-content-center gap-1 w-100">
                                        <iconify-icon icon="{{ $appUser->is_active ? 'iconamoon:block-duotone' : 'iconamoon:check-circle-1-duotone' }}"></iconify-icon>
                                        {{ $appUser->is_active ? 'Block' : 'Activate' }}
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">User Stats</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-12">
                                <p class="fs-15 mb-1 float-end">{{ $taskCompletion }}%</p>
                                <p class="fs-15 mb-1">Task Completion</p>
                                <div class="progress progress-sm mb-3">
                                    <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: {{ $taskCompletion }}%" aria-valuenow="{{ $taskCompletion }}" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>

                                <p class="fs-15 mb-1 float-end">{{ $commentsActivity }}%</p>
                                <p class="fs-15 mb-1">Comments Activity</p>
                                <div class="progress progress-sm mb-3">
                                    <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: {{ $commentsActivity }}%" aria-valuenow="{{ $commentsActivity }}" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>

                                <p class="fs-15 mb-1 float-end">{{ $likesEngagement }}%</p>
                                <p class="fs-15 mb-1">Likes Engagement</p>
                                <div class="progress progress-sm mb-3">
                                    <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: {{ $likesEngagement }}%" aria-valuenow="{{ $likesEngagement }}" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>

                                <p class="fs-15 mb-1 float-end">{{ $followersGrowth }}%</p>
                                <p class="fs-15 mb-1">Followers Growth</p>
                                <div class="progress progress-sm mb-2">
                                    <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: {{ $followersGrowth }}%" aria-valuenow="{{ $followersGrowth }}" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex align-items-center">
                        <h5 class="card-title">Activities</h5>
                        <div class="ms-auto">
                            <a href="javascript:void(0);" class="text-primary">Export <i class="bx bx-export ms-1"></i></a>
                        </div>
                    </div>
                    <div class="card-body">
                        @forelse ($activities as $activity)
                            <div class="d-flex align-items-start {{ $loop->last ? '' : 'mb-4' }}">
                                <p class="mb-0 mt-2 pe-3 me-2">{{ $activity['time'] }}</p>
                                <div class="position-relative ps-4">
                                    <span class="position-absolute start-0 top-0 border border-dashed h-100"></span>
                                    <div class="mb-3">
                                        <span class="position-absolute start-0 avatar-sm translate-middle-x {{ $activity['icon_class'] }} d-inline-flex align-items-center justify-content-center rounded-circle text-light fs-12">
                                            {{ $activity['icon_text'] }}
                                        </span>
                                        <div class="d-flex gap-2">
                                            <div class="ms-2">
                                                <h5 class="mb-0 text-dark fw-semibold fs-15 lh-base">{{ $activity['title'] }}</h5>
                                                <span class="badge {{ $activity['badge'] }} mt-1">{{ $activity['type'] }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <p class="text-muted mb-0">No activities found for this app user.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xxl-8">
        <div class="row">
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Personal Info</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-group">
                            <li class="list-group-item border-0 border-bottom px-0 pt-0">
                                <div class="d-flex flex-wrap align-items-center">
                                    <h5 class="me-2 fw-medium mb-0">Name :</h5>
                                    <span class="fs-14 text-muted">{{ $appUser->name }}</span>
                                </div>
                            </li>
                            <li class="list-group-item border-0 border-bottom px-0">
                                <div class="d-flex flex-wrap align-items-center">
                                    <h5 class="me-2 fw-medium mb-0">Username :</h5>
                                    <span class="fs-14 text-muted">{{ '@' . ($appUser->username ?: Str::slug($appUser->name, '.')) }}</span>
                                </div>
                            </li>
                            <li class="list-group-item border-0 border-bottom px-0">
                                <div class="d-flex flex-wrap align-items-center">
                                    <h5 class="me-2 mb-0 fw-medium">Phone :</h5>
                                    <span class="fs-14 text-muted">{{ $appUser->phone ?: '-' }}</span>
                                </div>
                            </li>
                            <li class="list-group-item border-0 border-bottom px-0">
                                <div class="d-flex flex-wrap align-items-center">
                                    <h5 class="me-2 mb-0 fw-medium">Email :</h5>
                                    <span class="fs-14 text-muted">{{ $appUser->email ?: '-' }}</span>
                                </div>
                            </li>
                            <li class="list-group-item border-0 border-bottom px-0">
                                <div class="d-flex flex-wrap align-items-center">
                                    <h5 class="me-2 mb-0 fw-medium">Provider :</h5>
                                    <span class="fs-14 text-muted">{{ $appUser->provider ?: '-' }}</span>
                                </div>
                            </li>
                            <li class="list-group-item border-0 border-bottom px-0">
                                <div class="d-flex flex-wrap align-items-center">
                                    <h5 class="me-2 mb-0 fw-medium">Provider ID :</h5>
                                    <span class="fs-14 text-muted">{{ $appUser->provider_id ?: '-' }}</span>
                                </div>
                            </li>
                            <li class="list-group-item border-0 border-bottom px-0">
                                <div class="d-flex flex-wrap align-items-center">
                                    <h5 class="me-2 mb-0 fw-medium">Status :</h5>
                                    <span class="fs-14 text-muted">{{ $appUser->is_active ? 'Active' : 'Inactive' }}</span>
                                </div>
                            </li>
                            <li class="list-group-item border-0 px-0 pb-0">
                                <div class="d-flex flex-wrap align-items-center">
                                    <h5 class="me-2 mb-0 fw-medium">Create Date :</h5>
                                    <span class="fs-14 text-muted">{{ $appUser->created_at?->format('d M Y') ?: '-' }}</span>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header d-flex">
                        <h5 class="card-title">Followers</h5>
                        <div class="ms-auto">
                            <span class="text-primary">Following: {{ $following->count() }}</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <ul class="list-group">
                            @forelse ($followers as $follower)
                                <li class="list-group-item border-0 {{ $loop->last ? 'px-0 pb-0' : 'border-bottom px-0' }} {{ $loop->first ? 'pt-0' : '' }}">
                                    <div class="d-flex flex-wrap align-items-center gap-2">
                                        <img src="/images/users/avatar-{{ ($loop->iteration % 6) + 1 }}.jpg" alt="{{ $follower->follower?->name }}" class="rounded-circle avatar-sm" />
                                        <div class="d-block">
                                            <h5 class="mb-1">{{ $follower->follower?->name ?: 'Unknown User' }}</h5>
                                            <h6 class="mb-0 text-muted">{{ $follower->follower?->email ?: $follower->follower?->phone ?: '-' }}</h6>
                                        </div>
                                        <div class="ms-auto">
                                            <span class="btn btn-soft-secondary btn-sm disabled">Follower</span>
                                        </div>
                                    </div>
                                </li>
                            @empty
                                <li class="list-group-item border-0 px-0 pt-0 pb-0 text-muted">No followers yet.</li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex align-items-center">
                        <h5 class="card-title">Posts</h5>
                        <div class="ms-auto">
                            <div class="dropdown">
                                <a href="javascript:void(0);" class="dropdown-toggle arrow-none" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="bx bx-dots-vertical-rounded fs-18 text-dark"></i>
                                </a>
                                <div class="dropdown-menu dropdown-menu-end">
                                    <a href="javascript:void(0);" class="dropdown-item"><i class="bx bx-edit-alt me-2"></i>Edit Report</a>
                                    <a href="javascript:void(0);" class="dropdown-item"><i class="bx bx-export me-2"></i>Export Report</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            @forelse ($posts as $post)
                                <div class="col-lg-6">
                                    <div class="card shadow-none mb-0">
                                        <div class="card-body p-lg-3 p-2">
                                            <div class="d-flex align-items-center gap-3 mb-3">
                                                <div class="avatar-md flex-shrink-0">
                                                    <span class="avatar-title bg-light rounded-circle">
                                                        <iconify-icon icon="{{ $post->reposted_post_id ? 'iconamoon:repeat-1-duotone' : 'iconamoon:image-duotone' }}" class="{{ $post->reposted_post_id ? 'text-warning' : 'text-primary' }} fs-28"></iconify-icon>
                                                    </span>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <a href="#!" class="fw-medium text-dark d-block">{{ Str::limit($post->content ?: 'No content', 40) }}</a>
                                                    @if ($post->location)
                                                        <span class="text-muted fs-13">{{ $post->location }}</span>
                                                    @endif
                                                </div>
                                                <div class="ms-auto">
                                                    <span class="badge bg-light text-dark border">{{ Str::headline($post->status) }}</span>
                                                </div>
                                            </div>

                                            @if ($post->image_url)
                                                <div class="mb-3">
                                                    <img src="{{ $post->image_url }}" alt="post image" class="img-fluid rounded-3 w-100" style="height: 180px; object-fit: cover;" />
                                                </div>
                                            @endif

                                            @if ($post->reposted_post_id)
                                                <div class="alert alert-light border mb-3 py-2 px-3">
                                                    <div class="d-flex align-items-center gap-2 mb-1">
                                                        <iconify-icon icon="iconamoon:repeat-1-duotone" class="text-warning"></iconify-icon>
                                                        <span class="fw-medium text-dark fs-13">Repost</span>
                                                    </div>
                                                    @if ($post->repostedPost)
                                                        <p class="mb-1 text-dark fs-13 fw-medium">
                                                            {{ Str::limit($post->repostedPost->content ?: 'Original post', 60) }}
                                                        </p>
                                                        <p class="mb-0 text-muted fs-13">
                                                            {{ $post->repostedPost->appUser?->name ?: 'Unknown user' }}
                                                            @if ($post->repostedPost->location)
                                                                | {{ $post->repostedPost->location }}
                                                            @endif
                                                        </p>
                                                    @else
                                                        <p class="mb-0 text-muted fs-13">
                                                            Original post ID: {{ $post->reposted_post_id }}
                                                        </p>
                                                    @endif
                                                </div>
                                            @endif

                                            <div class="d-flex gap-2">
                                                <h5 class="card-title badge text-secondary d-flex gap-1 align-items-center py-1 px-2 fs-13 mb-3 border rounded-1">
                                                    <iconify-icon icon="iconamoon:clock-duotone"></iconify-icon>
                                                    {{ $post->published_at?->diffForHumans() ?: 'Not published' }}
                                                </h5>
                                                <h5 class="card-title badge text-secondary d-flex gap-1 align-items-center py-1 px-2 fs-13 mb-3 border rounded-1">
                                                    <iconify-icon icon="iconamoon:location-pin-duotone"></iconify-icon>
                                                    {{ $post->location ?: 'No location' }}
                                                </h5>
                                            </div>

                                            <div>
                                                <p class="fs-15 mb-1 float-end">{{ $post->comments_count }} comments</p>
                                                <p class="fs-15 mb-1">{{ min(100, ($post->likes_count * 10)) }}%</p>
                                                <div class="progress progress-sm mb-3">
                                                    <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: {{ min(100, ($post->likes_count * 10)) }}%" aria-valuenow="{{ min(100, ($post->likes_count * 10)) }}" aria-valuemin="0" aria-valuemax="100"></div>
                                                </div>
                                            </div>

                                            <div class="d-flex align-items-center gap-3">
                                                <div class="avatar-group">
                                                    <div class="avatar-group-item"><img src="/images/users/avatar-4.jpg" alt="" class="rounded-circle avatar-sm" /></div>
                                                    <div class="avatar-group-item"><img src="/images/users/avatar-5.jpg" alt="" class="rounded-circle avatar-sm" /></div>
                                                    <div class="avatar-group-item"><img src="/images/users/avatar-3.jpg" alt="" class="rounded-circle avatar-sm" /></div>
                                                    <div class="avatar-group-item"><img src="/images/users/avatar-6.jpg" alt="" class="rounded-circle avatar-sm" /></div>
                                                </div>
                                                <h5 class="mb-0">{{ $post->likes_count }} Likes{{ $post->reposted_post_id ? ' | Repost' : '' }}</h5>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="col-12">
                                    <p class="text-muted mb-0">No posts found for this app user.</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
