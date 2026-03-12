@extends('layouts.vertical', ['title' => 'Create App User', 'subTitle' => 'App Users'])

@section('content')
<div class="card">
    <div class="card-header">
        <h4 class="card-title mb-0">Create App User</h4>
    </div>
    <div class="card-body">
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0 ps-3">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('app-users.store') }}" enctype="multipart/form-data">
            @csrf
            <div class="row">
                <div class="col-lg-6 mb-3">
                    <label class="form-label">Name</label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-lg-6 mb-3">
                    <label class="form-label">Username</label>
                    <input type="text" name="username" class="form-control @error('username') is-invalid @enderror" value="{{ old('username') }}">
                    @error('username')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-lg-6 mb-3">
                    <label class="form-label">Phone</label>
                    <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone') }}" required>
                    @error('phone')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-lg-6 mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" required>
                    @error('password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-lg-6 mb-3">
                    <label class="form-label">Packages</label>
                    <select name="package_ids[]" class="form-select" multiple>
                        @foreach ($packages as $package)
                            <option value="{{ $package->id }}" @selected(collect(old('package_ids', []))->contains($package->id))>
                                {{ $package->name }}
                            </option>
                        @endforeach
                    </select>
                    <small class="text-muted">Hold Ctrl or Command to select multiple packages.</small>
                </div>
                <div class="col-lg-6 mb-3">
                    <label class="form-label">Profile Image</label>
                    <input type="file" name="profile_image" class="form-control @error('profile_image') is-invalid @enderror" accept="image/*">
                    @error('profile_image')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-lg-6 mb-3">
                    <label class="form-label">Cover Photo</label>
                    <input type="file" name="cover_photo" class="form-control @error('cover_photo') is-invalid @enderror" accept="image/*">
                    @error('cover_photo')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-12 mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="appUserActive" @checked(old('is_active', true))>
                        <label class="form-check-label" for="appUserActive">Active</label>
                    </div>
                </div>
            </div>
            <div class="d-flex justify-content-end gap-2">
                <a href="{{ route('app-users.index') }}" class="btn btn-light">Cancel</a>
                <button type="submit" class="btn btn-primary">Save App User</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('script-bottom')
@if ($errors->any())
<script>
    document.addEventListener('DOMContentLoaded', function () {
        Toastify({
            text: @json($errors->first()),
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
