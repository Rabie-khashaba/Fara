@extends('layouts.vertical', ['title' => 'Edit App User', 'subTitle' => 'App Users'])

@section('content')
<div class="card">
    <div class="card-header">
        <h4 class="card-title mb-0">Edit App User</h4>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('app-users.update', $appUser) }}">
            @csrf
            @method('PUT')
            <div class="row">
                <div class="col-lg-6 mb-3">
                    <label class="form-label">Name</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name', $appUser->name) }}" required>
                </div>
                <div class="col-lg-6 mb-3">
                    <label class="form-label">Phone</label>
                    <input type="text" name="phone" class="form-control" value="{{ old('phone', $appUser->phone) }}" required>
                </div>
                <div class="col-lg-6 mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" placeholder="Leave empty to keep current password">
                    @error('password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-12 mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="appUserActive" @checked(old('is_active', $appUser->is_active))>
                        <label class="form-check-label" for="appUserActive">Active</label>
                    </div>
                </div>
            </div>
            <div class="d-flex justify-content-end gap-2">
                <a href="{{ route('app-users.show', $appUser) }}" class="btn btn-light">Cancel</a>
                <button type="submit" class="btn btn-primary">Update App User</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('script-bottom')
@if ($errors->has('phone') || $errors->has('password'))
<script>
    document.addEventListener('DOMContentLoaded', function () {
        Toastify({
            text: @json($errors->first('phone') ?: $errors->first('password')),
            duration: 4000,
            gravity: 'top',
            position: 'right',
            className: 'bg-danger',
            close: true,
        }).showToast();
    });
</script>
@endif
@endsection
