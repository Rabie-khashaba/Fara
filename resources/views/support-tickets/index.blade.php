@extends('layouts.vertical', ['title' => 'Support Tickets', 'subTitle' => 'Support Tickets'])

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div>
                    <h4 class="mb-1">Support Tickets</h4>
                </div>
                <a href="{{ route('support-tickets.create') }}" class="btn btn-danger">
                    <i class="bi bi-plus-circle me-1"></i>Open Ticket
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
        <form method="GET" action="{{ route('support-tickets.index') }}">
            <div class="row g-3 align-items-end">
                <div class="col-lg-4">
                    <label for="search" class="form-label">Search</label>
                    <input
                        type="text"
                        id="search"
                        name="search"
                        value="{{ request('search') }}"
                        class="form-control"
                        placeholder="Search by ticket number, subject, or app user"
                    >
                </div>
                <div class="col-lg-2">
                    <label for="status" class="form-label">Status</label>
                    <select id="status" name="status" class="form-select">
                        <option value="">All</option>
                        <option value="open" @selected(request('status') === 'open')>Open</option>
                        <option value="closed" @selected(request('status') === 'closed')>Closed</option>
                    </select>
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
                    <a href="{{ route('support-tickets.index') }}" class="btn btn-light">Reset</a>
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
                    <th>Ticket</th>
                    <th>App User</th>
                    <th>Assigned To</th>
                    <th>Status</th>
                    <th>Last Message</th>
                    <th>Updated</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($tickets as $ticket)
                    <tr>
                        <td>
                            <a href="{{ route('support-tickets.show', $ticket) }}" class="text-dark fw-semibold">
                                {{ $ticket->ticket_number }}
                            </a>
                            <div class="text-muted small">{{ $ticket->subject }}</div>
                        </td>
                        <td>{{ $ticket->appUser?->name ?? '-' }}</td>
                        <td>{{ $ticket->assignedUser?->name ?? 'Unassigned' }}</td>
                        <td>
                            <span class="badge {{ $ticket->status === 'open' ? 'badge-soft-success' : 'badge-soft-secondary' }}">
                                {{ ucfirst($ticket->status) }}
                            </span>
                        </td>
                        <td class="text-wrap" style="min-width: 260px;">
                            {{ \Illuminate\Support\Str::limit($ticket->latestMessage?->body ?? '-', 80) }}
                        </td>
                        <td>{{ ($ticket->last_message_at ?? $ticket->created_at)?->format('d M Y h:i A') ?: '-' }}</td>
                        <td class="text-end">
                            <a href="{{ route('support-tickets.show', $ticket) }}" class="btn btn-sm btn-soft-secondary">View</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">No support tickets found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="p-3 border-top">{{ $tickets->links() }}</div>
</div>
@endsection
