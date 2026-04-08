@extends('layouts.vertical', ['title' => 'Support Ticket', 'subTitle' => 'Support Tickets'])

@section('content')
@php
    $authUserId = auth()->id();
@endphp

@if (session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
@endif

<div class="row g-4">
    <div class="col-xl-4">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                    <div>
                        <h4 class="mb-1">{{ $ticket->ticket_number }}</h4>
                        <div class="text-muted">{{ $ticket->subject }}</div>
                    </div>
                    <span class="badge {{ $ticket->status === 'open' ? 'badge-soft-success' : 'badge-soft-secondary' }}">
                        {{ ucfirst($ticket->status) }}
                    </span>
                </div>

                <div class="mb-2"><strong>App User:</strong> {{ $ticket->appUser?->name ?? '-' }}</div>
                <div class="mb-2"><strong>Assigned To:</strong> {{ $ticket->assignedUser?->name ?? 'Unassigned' }}</div>
                <div class="mb-2"><strong>Created By:</strong> {{ $ticket->createdByUser?->name ?? $ticket->createdByAppUser?->name ?? '-' }}</div>
                <div class="mb-2"><strong>Created At:</strong> {{ $ticket->created_at?->format('d M Y h:i A') ?: '-' }}</div>
                <div class="mb-3"><strong>Last Message:</strong> {{ ($ticket->last_message_at ?? $ticket->created_at)?->format('d M Y h:i A') ?: '-' }}</div>

                <div class="d-flex gap-2">
                    @if ($ticket->status === 'open')
                        <form method="POST" action="{{ route('support-tickets.close', $ticket) }}">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn btn-soft-danger">Close Ticket</button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('support-tickets.reopen', $ticket) }}">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn btn-soft-success">Reopen Ticket</button>
                        </form>
                    @endif

                    <a href="{{ route('support-tickets.index') }}" class="btn btn-light">Back</a>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-8">
        <div class="card">
            <div class="card-body">
                <h4 class="mb-3">Conversation</h4>

                <div class="border rounded p-3 mb-4" style="max-height: 520px; overflow-y: auto; background: #f8f9fb;">
                    @forelse ($ticket->messages as $ticketMessage)
                        @php
                            $isAdminMessage = $ticketMessage->sender_user_id !== null;
                            $senderName = $ticketMessage->senderUser?->name ?? $ticketMessage->senderAppUser?->name ?? 'Unknown';
                        @endphp

                        <div class="d-flex mb-3 {{ $isAdminMessage ? 'justify-content-end' : 'justify-content-start' }}">
                            <div
                                class="border rounded-3 p-3 {{ $isAdminMessage ? 'text-end' : 'bg-white' }}"
                                style="width: min(78%, 520px); {{ $isAdminMessage ? 'background: #eaf4ff; border-color: #b8dcff !important;' : '' }}"
                            >
                                <div class="d-flex align-items-center mb-2 {{ $isAdminMessage ? 'justify-content-end' : 'justify-content-between' }}">
                                    @if (! $isAdminMessage)
                                        <strong>{{ $senderName }}</strong>
                                    @endif

                                    <span class="text-muted small {{ $isAdminMessage ? 'ms-2' : '' }}">
                                        {{ $ticketMessage->created_at?->format('d M Y h:i A') ?: '-' }}
                                    </span>

                                    @if ($isAdminMessage)
                                        <strong class="ms-2">{{ $senderName }}</strong>
                                    @endif
                                </div>

                                <div style="white-space: pre-wrap;">{{ $ticketMessage->body }}</div>
                            </div>
                        </div>
                    @empty
                        <div class="text-muted">No messages yet.</div>
                    @endforelse
                </div>

                @if ($ticket->status === 'open')
                    <form method="POST" action="{{ route('support-tickets.messages.store', $ticket) }}">
                        @csrf
                        <div class="mb-3">
                            <label for="message" class="form-label">Reply</label>
                            <textarea
                                id="message"
                                name="message"
                                rows="2"
                                class="form-control @error('message') is-invalid @enderror"
                                required
                            >{{ old('message') }}</textarea>
                            @error('message')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <button type="submit" class="btn btn-danger">Send Reply</button>
                    </form>
                @else
                    <div class="alert alert-warning mb-0">This ticket is closed. Reopen it to send a new reply.</div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection




