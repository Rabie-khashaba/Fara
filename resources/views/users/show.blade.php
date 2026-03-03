@extends('layouts.vertical', ['title' => 'Dashboard User Details', 'subTitle' => 'Users'])

@php
    $followers = [
        ['name' => 'Hilda B. Brid', 'email' => 'hildabbridges@teleworm.us', 'avatar' => '/images/users/avatar-6.jpg'],
        ['name' => 'Kevin M. Bacon', 'email' => 'kevinmbacon@dayrep.com', 'avatar' => '/images/users/avatar-2.jpg'],
        ['name' => 'Sherrie W. Torres', 'email' => 'sherriewtorres@dayrep.com', 'avatar' => '/images/users/avatar-3.jpg'],
        ['name' => 'David R. Willi', 'email' => 'davidrwill@teleworm.us', 'avatar' => '/images/users/avatar-4.jpg'],
    ];

    $activities = [
        ['time' => '12:15', 'title' => 'Added a comment on a reported post', 'type' => 'Comment', 'badge' => 'badge-soft-danger', 'icon_class' => 'bg-danger', 'icon_text' => 'CM'],
        ['time' => '11:00', 'title' => 'Liked 8 community posts this morning', 'type' => 'Like', 'badge' => 'badge-soft-success', 'icon_class' => 'bg-success', 'icon_text' => 'LK'],
        ['time' => '10:30', 'title' => 'Updated moderation notes for an app user', 'type' => 'Update', 'badge' => 'badge-soft-primary', 'icon_class' => 'bg-primary', 'icon_text' => 'UP'],
        ['time' => '09:00', 'title' => 'Reviewed new followers and profile changes', 'type' => 'Review', 'badge' => 'badge-soft-warning', 'icon_class' => 'bg-warning', 'icon_text' => 'RV'],
    ];

    $posts = [
        ['title' => 'Community Guidelines Refresh', 'type' => 'Announcement', 'days_left' => '10 day left', 'files' => '13 Files', 'progress' => 59, 'done' => '8/12', 'icon' => 'iconamoon:pen-duotone', 'icon_color' => 'text-primary'],
        ['title' => 'Moderation Dashboard Notes', 'type' => 'Internal', 'days_left' => '15 day left', 'files' => '8 Files', 'progress' => 78, 'done' => '15/20', 'icon' => 'iconamoon:file-document-duotone', 'icon_color' => 'text-warning'],
        ['title' => 'Followers Engagement Summary', 'type' => 'Analytics', 'days_left' => '7 day left', 'files' => '6 Files', 'progress' => 66, 'done' => '6/9', 'icon' => 'iconamoon:chart-line-duotone', 'icon_color' => 'text-success'],
        ['title' => 'Top Comments This Week', 'type' => 'Report', 'days_left' => '4 day left', 'files' => '5 Files', 'progress' => 84, 'done' => '21/25', 'icon' => 'iconamoon:message-text-duotone', 'icon_color' => 'text-danger'],
    ];
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
                                <h4 class="mb-1">{{ $user->name }}</h4>
                                <p class="fs-14 mb-0">{{ '@' . \Illuminate\Support\Str::slug($user->name, '.') }}</p>
                            </div>
                            <div class="ms-auto">
                                <div class="dropdown">
                                    <a href="javascript:void(0);" class="dropdown-toggle arrow-none" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="bx bx-dots-vertical-rounded fs-18 text-dark"></i>
                                    </a>
                                    <div class="dropdown-menu dropdown-menu-end">
                                        <a href="{{ route('users.edit', $user) }}" class="dropdown-item">
                                            <i class="bx bx-edit-alt me-2"></i>Edit User
                                        </a>
                                        <form method="POST" action="{{ route('users.toggle-block', $user) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="dropdown-item">
                                                <i class="bx {{ $user->is_active ? 'bx-block' : 'bx-check-circle' }} me-2"></i>
                                                {{ $user->is_active ? 'Deactivate User' : 'Activate User' }}
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
                                    Dashboard user with access based on the assigned role and permissions. This page shows profile details,
                                    followers, activities, and posts in the same dashboard style.
                                </p>
                                <div class="mt-3">
                                    <div class="d-flex gap-2 flex-wrap">
                                        <span class="badge text-secondary py-1 px-2 fs-12 border rounded-1">{{ $user->type }}</span>
                                        <span class="badge text-secondary py-1 px-2 fs-12 border rounded-1">{{ $user->roles->pluck('name')->join(', ') ?: 'No Role' }}</span>
                                        <span class="badge text-secondary py-1 px-2 fs-12 border rounded-1">{{ $user->is_active ? 'Active' : 'Inactive' }}</span>
                                        <span class="badge text-secondary py-1 px-2 fs-12 border rounded-1">Role ID: {{ $user->role_id ?: '-' }}</span>
                                    </div>
                                </div>
                                <div class="mt-4">
                                    <h5 class="text-dark fw-medium">Links :</h5>
                                    <a href="#!" class="text-primary text-decoration-underline">https://dashboard-user-profile.local</a>
                                    <p class="mb-0 mt-1">
                                        <a href="#!" class="text-primary text-decoration-underline">https://fara-admin.local/{{ \Illuminate\Support\Str::slug($user->name, '-') }}</a>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-light-subtle">
                        <div class="row g-2 mb-1">
                            <div class="col-lg-6">
                                <a href="{{ route('users.edit', $user) }}" class="btn btn-primary d-flex align-items-center justify-content-center gap-1 w-100">
                                    <iconify-icon icon="iconamoon:edit-duotone"></iconify-icon>
                                    Edit
                                </a>
                            </div>
                            <div class="col-lg-6">
                                <form method="POST" action="{{ route('users.toggle-block', $user) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn {{ $user->is_active ? 'btn-danger' : 'btn-success' }} d-flex align-items-center justify-content-center gap-1 w-100">
                                        <iconify-icon icon="{{ $user->is_active ? 'iconamoon:block-duotone' : 'iconamoon:check-circle-1-duotone' }}"></iconify-icon>
                                        {{ $user->is_active ? 'Block' : 'Activate' }}
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
                                <p class="fs-15 mb-1 float-end">82%</p>
                                <p class="fs-15 mb-1">Task Completion</p>
                                <div class="progress progress-sm mb-3">
                                    <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 82%" aria-valuenow="82" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>

                                <p class="fs-15 mb-1 float-end">55%</p>
                                <p class="fs-15 mb-1">Comments Activity</p>
                                <div class="progress progress-sm mb-3">
                                    <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 55%" aria-valuenow="55" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>

                                <p class="fs-15 mb-1 float-end">68%</p>
                                <p class="fs-15 mb-1">Likes Engagement</p>
                                <div class="progress progress-sm mb-3">
                                    <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 68%" aria-valuenow="68" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>

                                <p class="fs-15 mb-1 float-end">37%</p>
                                <p class="fs-15 mb-1">Followers Growth</p>
                                <div class="progress progress-sm mb-2">
                                    <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 37%" aria-valuenow="37" aria-valuemin="0" aria-valuemax="100"></div>
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
                        @foreach ($activities as $activity)
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
                        @endforeach
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
                                    <span class="fs-14 text-muted">{{ $user->name }}</span>
                                </div>
                            </li>
                            <li class="list-group-item border-0 border-bottom px-0">
                                <div class="d-flex flex-wrap align-items-center">
                                    <h5 class="me-2 fw-medium mb-0">Username :</h5>
                                    <span class="fs-14 text-muted">{{ '@' . \Illuminate\Support\Str::slug($user->name, '.') }}</span>
                                </div>
                            </li>
                            <li class="list-group-item border-0 border-bottom px-0">
                                <div class="d-flex flex-wrap align-items-center">
                                    <h5 class="me-2 mb-0 fw-medium">Phone :</h5>
                                    <span class="fs-14 text-muted">{{ $user->phone ?: '-' }}</span>
                                </div>
                            </li>
                            <li class="list-group-item border-0 border-bottom px-0">
                                <div class="d-flex flex-wrap align-items-center">
                                    <h5 class="me-2 mb-0 fw-medium">Role :</h5>
                                    <span class="fs-14 text-muted">{{ $user->roles->pluck('name')->join(', ') ?: '-' }}</span>
                                </div>
                            </li>
                            <li class="list-group-item border-0 border-bottom px-0">
                                <div class="d-flex flex-wrap align-items-center">
                                    <h5 class="me-2 mb-0 fw-medium">Role ID :</h5>
                                    <span class="fs-14 text-muted">{{ $user->role_id ?: '-' }}</span>
                                </div>
                            </li>
                            <li class="list-group-item border-0 border-bottom px-0">
                                <div class="d-flex flex-wrap align-items-center">
                                    <h5 class="me-2 mb-0 fw-medium">Type :</h5>
                                    <span class="fs-14 text-muted">{{ $user->type }}</span>
                                </div>
                            </li>
                            <li class="list-group-item border-0 border-bottom px-0">
                                <div class="d-flex flex-wrap align-items-center">
                                    <h5 class="me-2 mb-0 fw-medium">Status :</h5>
                                    <span class="fs-14 text-muted">{{ $user->is_active ? 'Active' : 'Inactive' }}</span>
                                </div>
                            </li>
                            <li class="list-group-item border-0 px-0 pb-0">
                                <div class="d-flex flex-wrap align-items-center">
                                    <h5 class="me-2 mb-0 fw-medium">Create Date :</h5>
                                    <span class="fs-14 text-muted">{{ $user->created_at?->format('d M Y') ?: '-' }}</span>
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
                            <a href="javascript:void(0);" class="text-primary">View All</a>
                        </div>
                    </div>
                    <div class="card-body">
                        <ul class="list-group">
                            @foreach ($followers as $follower)
                                <li class="list-group-item border-0 {{ $loop->last ? 'px-0 pb-0' : 'border-bottom px-0' }} {{ $loop->first ? 'pt-0' : '' }}">
                                    <div class="d-flex flex-wrap align-items-center gap-2">
                                        <img src="{{ $follower['avatar'] }}" alt="{{ $follower['name'] }}" class="rounded-circle avatar-sm" />
                                        <div class="d-block">
                                            <h5 class="mb-1">{{ $follower['name'] }}</h5>
                                            <h6 class="mb-0 text-muted">{{ $follower['email'] }}</h6>
                                        </div>
                                        <div class="ms-auto">
                                            <a href="#!" class="btn btn-soft-secondary btn-sm">Follow</a>
                                        </div>
                                    </div>
                                </li>
                            @endforeach
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
                            @foreach ($posts as $post)
                                <div class="col-lg-6">
                                    <div class="card shadow-none mb-0">
                                        <div class="card-body p-lg-3 p-2">
                                            <div class="d-flex align-items-center gap-3 mb-3">
                                                <div class="avatar-md flex-shrink-0">
                                                    <span class="avatar-title bg-light rounded-circle">
                                                        <iconify-icon icon="{{ $post['icon'] }}" class="{{ $post['icon_color'] }} fs-28"></iconify-icon>
                                                    </span>
                                                </div>
                                                <a href="#!" class="fw-medium text-dark">{{ $post['title'] }}</a>
                                                <div class="ms-auto">
                                                    <span class="badge bg-light text-dark border">{{ $post['type'] }}</span>
                                                </div>
                                            </div>

                                            <div class="d-flex gap-2">
                                                <h5 class="card-title badge text-secondary d-flex gap-1 align-items-center py-1 px-2 fs-13 mb-3 border rounded-1">
                                                    <iconify-icon icon="iconamoon:clock-duotone"></iconify-icon>
                                                    {{ $post['days_left'] }}
                                                </h5>
                                                <h5 class="card-title badge text-secondary d-flex gap-1 align-items-center py-1 px-2 fs-13 mb-3 border rounded-1">
                                                    <iconify-icon icon="iconamoon:file-duotone"></iconify-icon>
                                                    {{ $post['files'] }}
                                                </h5>
                                            </div>

                                            <div>
                                                <p class="fs-15 mb-1 float-end">{{ $post['done'] }}</p>
                                                <p class="fs-15 mb-1">{{ $post['progress'] }}%</p>
                                                <div class="progress progress-sm mb-3">
                                                    <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: {{ $post['progress'] }}%" aria-valuenow="{{ $post['progress'] }}" aria-valuemin="0" aria-valuemax="100"></div>
                                                </div>
                                            </div>

                                            <div class="d-flex align-items-center gap-3">
                                                <div class="avatar-group">
                                                    <div class="avatar-group-item"><img src="/images/users/avatar-4.jpg" alt="" class="rounded-circle avatar-sm" /></div>
                                                    <div class="avatar-group-item"><img src="/images/users/avatar-5.jpg" alt="" class="rounded-circle avatar-sm" /></div>
                                                    <div class="avatar-group-item"><img src="/images/users/avatar-3.jpg" alt="" class="rounded-circle avatar-sm" /></div>
                                                    <div class="avatar-group-item"><img src="/images/users/avatar-6.jpg" alt="" class="rounded-circle avatar-sm" /></div>
                                                </div>
                                                <h5 class="mb-0">Posts Activity</h5>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
