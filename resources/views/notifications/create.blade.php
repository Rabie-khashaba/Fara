@extends('layouts.vertical', ['title' => 'Send Notification', 'subTitle' => 'Notifications'])

@section('content')
<div class="row">
    <div class="col-xl-8">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h4 class="mb-1">Send Notification To User</h4>
                        <p class="text-muted mb-0">Choose the sender and recipient, then the dashboard will fetch the recipient FCM tokens from the device tokens table.</p>
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
                        <label for="sender_app_user_id" class="form-label">Sender</label>
                        <select id="sender_app_user_id" name="sender_app_user_id" class="form-select @error('sender_app_user_id') is-invalid @enderror" required>
                            <option value="">Select sender</option>
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
                        <label for="recipient_app_user_id" class="form-label">Recipient</label>
                        <select id="recipient_app_user_id" name="recipient_app_user_id" class="form-select @error('recipient_app_user_id') is-invalid @enderror">
                            <option value="">Select recipient</option>
                            @foreach ($appUsers as $appUser)
                                <option value="{{ $appUser->id }}" @selected((string) old('recipient_app_user_id') === (string) $appUser->id)>
                                    {{ $appUser->name }} - {{ $appUser->phone }}
                                </option>
                            @endforeach
                        </select>
                        @error('recipient_app_user_id')
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

                    <div class="d-flex gap-2 flex-wrap">
                        <button type="submit" class="btn btn-danger">Send Notification</button>
                        <button type="submit" class="btn btn-outline-danger" formaction="{{ route('notifications.store-all') }}">Send To All Users With FCM Token</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="card">
            <div class="card-body">
                <h5 class="mb-3">Notes</h5>
                <ul class="text-muted mb-0 ps-3">
                    <li>The selected sender will be saved as the sender.</li>
                    <li>The selected recipient will receive the single-user notification.</li>
                    <li>The send-all button targets every user that has a saved FCM token.</li>
                    <li>FCM tokens are loaded from the <code>app_user_device_tokens</code> table and the legacy <code>app_users.fcm_token</code> column.</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
