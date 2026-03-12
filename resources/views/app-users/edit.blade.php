@extends('layouts.vertical', ['title' => 'Edit App User', 'subTitle' => 'App Users'])

@section('content')
<div class="card">
    <div class="card-header">
        <h4 class="card-title mb-0">Edit App User</h4>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('app-users.update', $appUser) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <div class="row">
                <div class="col-lg-6 mb-3">
                    <label class="form-label">Name</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name', $appUser->name) }}" required>
                </div>
                <div class="col-lg-6 mb-3">
                    <label class="form-label">Username</label>
                    <input type="text" name="username" class="form-control" value="{{ old('username', $appUser->username) }}">
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
                <div class="col-lg-6 mb-3">
                    <label class="form-label">Packages</label>
                    <select name="package_ids[]" class="form-select" multiple>
                        @foreach ($packages as $package)
                            <option
                                value="{{ $package->id }}"
                                @selected(collect(old('package_ids', $appUser->packages->pluck('id')->all()))->contains($package->id))
                            >
                                {{ $package->name }}
                            </option>
                        @endforeach
                    </select>
                    <small class="text-muted">Hold Ctrl or Command to select multiple packages.</small>
                </div>
                <div class="col-lg-6 mb-3">
                    <label class="form-label">Profile Image</label>
                    <input type="file" name="profile_image" class="form-control" accept="image/*">
                    @if ($appUser->profile_image_url)
                        <img src="{{ $appUser->profile_image_url }}" alt="Profile" class="rounded mt-2" style="width: 80px; height: 80px; object-fit: cover;">
                    @endif
                </div>
                <div class="col-lg-6 mb-3">
                    <label class="form-label">Cover Photo</label>
                    <input type="file" name="cover_photo" class="form-control" accept="image/*">
                    @if ($appUser->cover_photo_url)
                        <img src="{{ $appUser->cover_photo_url }}" alt="Cover" class="rounded mt-2 w-100" style="max-height: 120px; object-fit: cover;">
                    @endif
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
