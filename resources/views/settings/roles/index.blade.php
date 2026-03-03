@extends('layouts.vertical', ['title' => 'Roles', 'subTitle' => 'Settings'])

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div>
                    <h4 class="mb-1">Roles</h4>
                    <p class="text-muted mb-0">Manage all dashboard roles from one section.</p>
                </div>
                <a href="{{ route('settings.roles.create') }}" class="btn btn-primary">Add Role</a>
            </div>
        </div>
    </div>
</div>

@if (session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
@endif

<div class="card overflow-hidden">
    <div class="table-responsive">
        <table class="table align-middle text-nowrap mb-0">
            <thead class="table-light">
                <tr>
                    <th>Role Name</th>
                    <th>Assigned Users</th>
                    <th>Permissions</th>
                    <th>Status</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($roles as $role)
                    <tr>
                        <td><a href="{{ route('settings.roles.show', $role->id) }}" class="fw-semibold text-dark">{{ $role->name }}</a></td>
                        <td>{{ $role->users_count }}</td>
                        <td>{{ $role->permissions->pluck('name')->join(', ') ?: '-' }}</td>
                        <td><span class="badge badge-soft-success">Active</span></td>
                        <td class="text-end">
                            <a href="{{ route('settings.roles.edit', $role->id) }}" class="btn btn-sm btn-soft-secondary me-1"><i class="bx bx-edit fs-16"></i></a>
                            <form method="POST" action="{{ route('settings.roles.destroy', $role->id) }}" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-soft-danger"><i class="bx bx-trash fs-16"></i></button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">No roles found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
