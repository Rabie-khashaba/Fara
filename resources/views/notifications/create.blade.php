@extends('layouts.vertical', ['title' => 'Send Notification', 'subTitle' => 'Notifications'])

@section('content')
<div class="row">
    <div class="col-xl-8">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h4 class="mb-1">Send Notification To All Users</h4>
                        <p class="text-muted mb-0">Choose the app user this notification is about, then send it to all users who have an FCM token.</p>
                    </div>
                    <a href="{{ route('notifications.index') }}" class="btn btn-light">Back</a>
                </div>

                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0 ps-3">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('notifications.store') }}">
                    @csrf

                    <div class="mb-3">
                        <label for="sender_app_user_id" class="form-label">User</label>
                        <select id="sender_app_user_id" name="sender_app_user_id" class="form-select @error('sender_app_user_id') is-invalid @enderror" required>
                            <option value="">Select app user</option>
                            @foreach ($appUsers as $appUser)
                                <option value="{{ $appUser->id }}" @selected((string) old('sender_app_user_id') === (string) $appUser->id)>
                                    {{ $appUser->name }} - {{ $appUser->phone }}
                                </option>
                            @endforeach
                        </select>
                        @error('sender_app_user_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="title" class="form-label">Title</label>
                        <input type="text" id="title" name="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title') }}" required>
                        @error('title')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="body" class="form-label">Body</label>
                        <textarea id="body" name="body" rows="5" class="form-control @error('body') is-invalid @enderror" required>{{ old('body') }}</textarea>
                        @error('body')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <button type="submit" class="btn btn-danger">Send To All Users</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="card">
            <div class="card-body">
                <h5 class="mb-3">Notes</h5>
                <ul class="text-muted mb-0 ps-3">
                    <li>The selected app user will be saved as the sender.</li>
                    <li>The form includes only user, title, and body.</li>
                    <li>The notification will be sent to all app users with a non-empty FCM token.</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
