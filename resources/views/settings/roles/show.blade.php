@extends('layouts.vertical', ['title' => 'Role Details', 'subTitle' => 'Settings'])

@section('content')
@if (session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
@endif

<div class="row">
    <div class="col-xl-4">
        <div class="card">
            <div class="card-body text-center">
                <div class="avatar-xl mx-auto mb-3 bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center fs-1">
                    <i class="bx bx-shield-quarter"></i>
                </div>
                <h4>{{ $role->name }}</h4>
                <p class="text-muted">{{ $role->permissions->count() }} permissions assigned</p>
                <a href="{{ route('settings.roles.edit', $role->id) }}" class="btn btn-primary">Edit Role</a>
            </div>
        </div>
    </div>
    <div class="col-xl-8">
        <div class="card">
            <div class="card-header"><h4 class="card-title mb-0">Role Information</h4></div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6"><div class="border rounded p-3"><p class="text-muted mb-1">Role Name</p><h5 class="mb-0">{{ $role->name }}</h5></div></div>
                    <div class="col-md-6"><div class="border rounded p-3"><p class="text-muted mb-1">Guard</p><h5 class="mb-0">{{ $role->guard_name }}</h5></div></div>
                    <div class="col-md-6"><div class="border rounded p-3"><p class="text-muted mb-1">Assigned Users</p><h5 class="mb-0">{{ $role->users->count() }}</h5></div></div>
                    <div class="col-md-6"><div class="border rounded p-3"><p class="text-muted mb-1">Permissions Count</p><h5 class="mb-0">{{ $role->permissions->count() }}</h5></div></div>
                    <div class="col-12">
                        <div class="border rounded p-3">
                            <p class="text-muted mb-2">Permissions</p>
                            <div class="d-flex flex-wrap gap-2">
                                @forelse ($role->permissions as $permission)
                                    <span class="badge bg-light text-dark border">{{ $permission->name }}</span>
                                @empty
                                    <span class="text-muted">No permissions assigned.</span>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
