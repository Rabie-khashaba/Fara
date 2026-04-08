@extends('layouts.vertical', ['title' => 'Open Support Ticket', 'subTitle' => 'Support Tickets'])

@section('content')
<div class="row justify-content-center">
    <div class="col-xl-8">
        <div class="card">
            <div class="card-body">
                <h4 class="mb-3">Open New Support Ticket</h4>

                <form method="POST" action="{{ route('support-tickets.store') }}">
                    @csrf

                    <div class="mb-3">
                        <label for="app_user_id" class="form-label">App User</label>
                        <select id="app_user_id" name="app_user_id" class="form-select @error('app_user_id') is-invalid @enderror" required>
                            <option value="">Select App User</option>
                            @foreach ($appUsers as $appUser)
                                <option value="{{ $appUser->id }}" @selected(old('app_user_id') == $appUser->id)>
                                    {{ $appUser->name }}{{ $appUser->phone ? ' - ' . $appUser->phone : '' }}
                                </option>
                            @endforeach
                        </select>
                        @error('app_user_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="subject" class="form-label">Subject</label>
                        <input
                            type="text"
                            id="subject"
                            name="subject"
                            value="{{ old('subject') }}"
                            class="form-control @error('subject') is-invalid @enderror"
                            maxlength="255"
                            required
                        >
                        @error('subject')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="message" class="form-label">First Message</label>
                        <textarea
                            id="message"
                            name="message"
                            rows="6"
                            class="form-control @error('message') is-invalid @enderror"
                            required
                        >{{ old('message') }}</textarea>
                        @error('message')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-danger">Create Ticket</button>
                        <a href="{{ route('support-tickets.index') }}" class="btn btn-light">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
