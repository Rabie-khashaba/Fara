@extends('layouts.vertical', ['title' => 'Permission Details', 'subTitle' => 'Settings'])

@section('content')
@if (session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
@endif

<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h4 class="card-title mb-0">Permission Details</h4>
        <a href="{{ route('settings.permissions.edit', $permission->id) }}" class="btn btn-primary">Edit Permission</a>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6"><div class="border rounded p-3"><p class="text-muted mb-1">Permission Name</p><h5 class="mb-0">{{ $permission->name }}</h5></div></div>
            <div class="col-md-6"><div class="border rounded p-3"><p class="text-muted mb-1">Guard</p><h5 class="mb-0">{{ $permission->guard_name }}</h5></div></div>
            <div class="col-12">
                <div class="border rounded p-3">
                    <p class="text-muted mb-2">Assigned Roles</p>
                    <div class="d-flex flex-wrap gap-2">
                        @forelse ($permission->roles as $role)
                            <span class="badge bg-light text-dark border">{{ $role->name }}</span>
                        @empty
                            <span class="text-muted">No roles assigned.</span>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
