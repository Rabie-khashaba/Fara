@extends('layouts.vertical', ['title' => 'App Users', 'subTitle' => 'App Users'])

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div>
                    <h4 class="mb-1">App Users</h4>
                    <p class="text-muted mb-0">App users table: Name, Phone, Create Date, Edit, Status.</p>
                </div>
                <a href="{{ route('app-users.create') }}" class="btn btn-danger">
                    <i class="bi bi-plus-circle me-1"></i>Create App User
                </a>
            </div>
        </div>
    </div>
</div>

@if (session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
@endif

<div class="card overflow-hidden">
    <div class="table-responsive">
        <table class="table text-nowrap mb-0">
            <thead class="table-light">
                <tr>
                    <th>Name</th>
                    <th>Phone</th>
                    <th>Create Date</th>
                    <th>Status</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($appUsers as $appUser)
                    <tr>
                        <td><a href="{{ route('app-users.show', $appUser) }}" class="text-dark fw-semibold">{{ $appUser->name }}</a></td>
                        <td>{{ $appUser->phone }}</td>
                        <td>{{ $appUser->created_at?->format('d M Y') ?: '-' }}</td>
                        <td><span class="badge {{ $appUser->is_active ? 'badge-soft-success' : 'badge-soft-danger' }}">{{ $appUser->is_active ? 'Active' : 'Inactive' }}</span></td>
                        <td class="text-end">
                            <a href="{{ route('app-users.edit', $appUser) }}" class="btn btn-sm btn-soft-secondary me-1">Edit</a>
                            <form method="POST" action="{{ route('app-users.toggle-status', $appUser) }}" class="d-inline">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn btn-sm {{ $appUser->is_active ? 'btn-soft-danger' : 'btn-soft-success' }}">
                                    {{ $appUser->is_active ? 'Deactivate' : 'Activate' }}
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">No app users found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="p-3 border-top">{{ $appUsers->links() }}</div>
</div>
@endsection
