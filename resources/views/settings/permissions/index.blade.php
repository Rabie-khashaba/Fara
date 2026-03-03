@extends('layouts.vertical', ['title' => 'Permissions', 'subTitle' => 'Settings'])

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div>
                    <h4 class="mb-1">Permissions</h4>
                    <p class="text-muted mb-0">Manage permissions by group.</p>
                </div>
                <a href="{{ route('settings.permissions.create') }}" class="btn btn-primary">Add Permission</a>
            </div>
        </div>
    </div>
</div>

@if (session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
@endif

<div class="row">
    @forelse ($permissions as $permission)
        <div class="col-xl-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h4 class="mb-1"><a href="{{ route('settings.permissions.show', $permission->id) }}" class="text-dark">{{ $permission->name }}</a></h4>
                            <p class="text-muted mb-3">Assigned to {{ $permission->roles_count }} roles</p>
                        </div>
                        <span class="badge bg-light text-dark border">{{ $permission->guard_name }}</span>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('settings.permissions.edit', $permission->id) }}" class="btn btn-sm btn-soft-secondary">Edit</a>
                        <form method="POST" action="{{ route('settings.permissions.delete', $permission->id) }}">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-soft-danger">Delete</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <div class="card">
                <div class="card-body text-center text-muted">No permissions found.</div>
            </div>
        </div>
    @endforelse
</div>
@endsection
