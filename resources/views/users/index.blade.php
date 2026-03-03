@extends('layouts.vertical', ['title' => 'Dashboard Users', 'subTitle' => 'Users'])

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                    <div>
                        <h4 class="mb-1">Dashboard Users</h4>
                        <p class="text-muted mb-0">Manage admin accounts from the main users table.</p>
                    </div>
                    <a href="{{ route('users.create') }}" class="btn btn-danger">
                        <i class="bi bi-plus-circle me-1"></i>Create User
                    </a>
                </div>
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
                    <th>Name</th>
                    <th>Phone</th>
                    <th>Role</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($users as $user)
                    <tr>
                        <td><a href="{{ route('users.show', $user) }}" class="text-dark fw-semibold">{{ $user->name }}</a></td>
                        <td>{{ $user->phone ?: '-' }}</td>
                        <td>
                            <div>{{ $user->role_id ?: '-' }}</div>
                            <small class="text-muted">{{ $user->roles->pluck('name')->join(', ') ?: 'No Role' }}</small>
                        </td>
                        <td><span class="badge bg-light text-dark border">{{ $user->type }}</span></td>
                        <td>
                            <span class="badge {{ $user->is_active ? 'badge-soft-success' : 'badge-soft-danger' }}">
                                {{ $user->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="text-end">
                            <a href="{{ route('users.edit', $user) }}" class="btn btn-sm btn-soft-secondary me-1"><i class="bx bx-edit fs-16"></i></a>
                            <form method="POST" action="{{ route('users.destroy', $user) }}" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-soft-danger me-1">
                                    <i class="bx bx-trash fs-16"></i>
                                </button>
                            </form>
                            <form method="POST" action="{{ route('users.toggle-block', $user) }}" class="d-inline">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn btn-sm {{ $user->is_active ? 'btn-soft-danger' : 'btn-soft-success' }}">
                                    {{ $user->is_active ? 'Deactivate' : 'Activate' }}
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">No dashboard users found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="p-3 border-top">{{ $users->links() }}</div>
</div>
@endsection
