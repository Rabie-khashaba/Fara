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

        <form method="POST" action="{{ route('app-users.store') }}">
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
