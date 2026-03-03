@extends('layouts.vertical', ['title' => 'Create Permission', 'subTitle' => 'Settings'])

@section('content')
<div class="card">
    <div class="card-header">
        <h4 class="card-title mb-0">Create Permission</h4>
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
        <form method="POST" action="{{ route('settings.permissions.store') }}">
            @csrf
            <div class="row">
                <div class="col-lg-6 mb-3">
                    <label class="form-label">Permission Name</label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="Enter permission name">
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-12 mb-3">
                    <label class="form-label">Assign To Roles</label>
                    <div class="row">
                        @foreach ($roles as $role)
                            <div class="col-md-4 mb-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="roles[]" value="{{ $role->id }}" id="role{{ $role->id }}"
                                           @checked(collect(old('roles', []))->contains($role->id))>
                                    <label class="form-check-label" for="role{{ $role->id }}">{{ $role->name }}</label>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="d-flex justify-content-end gap-2">
                <a href="{{ route('settings.permissions.index') }}" class="btn btn-light">Cancel</a>
                <button type="submit" class="btn btn-primary">Save Permission</button>
            </div>
        </form>
    </div>
</div>
@endsection
