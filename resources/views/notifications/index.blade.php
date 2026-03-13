@extends('layouts.vertical', ['title' => 'Notifications', 'subTitle' => 'Notifications'])

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div>
                    <h4 class="mb-1">All Notifications</h4>
                </div>
                <a href="{{ route('notifications.create') }}" class="btn btn-danger">
                    <i class="bi bi-send me-1"></i>Send Notification
                </a>
            </div>
        </div>
    </div>
</div>

@if (session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
@endif

<div class="card">
    <div class="card-body">
        <form method="GET" action="{{ route('notifications.index') }}">
            <div class="row g-3 align-items-end">
                <div class="col-lg-6">
                    <label for="search" class="form-label">Search</label>
                    <input
                        type="text"
                        id="search"
                        name="search"
                        value="{{ request('search') }}"
                        class="form-control"
                        placeholder="Search by title, body, sender, or recipient"
                    >
                </div>
                <div class="col-lg-3">
                    <label for="date_from" class="form-label">Date From</label>
                    <input type="date" id="date_from" name="date_from" value="{{ request('date_from') }}" class="form-control">
                </div>
                <div class="col-lg-3">
                    <label for="date_to" class="form-label">Date To</label>
                    <input type="date" id="date_to" name="date_to" value="{{ request('date_to') }}" class="form-control">
                </div>
                <div class="col-12 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Filter</button>
                    <a href="{{ route('notifications.index') }}" class="btn btn-light">Reset</a>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card overflow-hidden">
    <div class="table-responsive">
        <table class="table text-nowrap mb-0">
            <thead class="table-light">
                <tr>
                    <th>Sender</th>
                    <th>Recipient</th>
                    <th>Title</th>
                    <th>Body</th>
                    <th>Status</th>
                    <th>Sent At</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($notifications as $notification)
                    <tr>
                        <td>{{ $notification->sender?->name ?? '-' }}</td>
                        <td>{{ $notification->recipient?->name ?? 'Unknown recipient' }}</td>
                        <td>{{ $notification->title }}</td>
                        <td class="text-wrap" style="min-width: 280px;">{{ $notification->body }}</td>
                        <td>
                            <span class="badge {{ $notification->is_read ? 'badge-soft-success' : 'badge-soft-warning' }}">
                                {{ $notification->is_read ? 'Read' : 'Unread' }}
                            </span>
                        </td>
                        <td>{{ $notification->sent_at?->format('d M Y h:i A') ?: '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">No notifications found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="p-3 border-top">{{ $notifications->links() }}</div>
</div>
@endsection
