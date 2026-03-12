@extends('layouts.vertical', ['title' => 'App Users', 'subTitle' => 'App Users'])

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div>
                    <h4 class="mb-1">App Users</h4>

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

<div class="card">
    <div class="card-body">
        <form method="GET" action="{{ route('app-users.index') }}">
            <div class="row g-3 align-items-end">
                <div class="col-lg-4">
                    <label for="search" class="form-label">Search</label>
                    <input
                        type="text"
                        id="search"
                        name="search"
                        value="{{ request('search') }}"
                        class="form-control"
                        placeholder="Search by name or phone"
                    >
                </div>
                <div class="col-lg-2">
                    <label for="date_from" class="form-label">Date From</label>
                    <input type="date" id="date_from" name="date_from" value="{{ request('date_from') }}" class="form-control">
                </div>
                <div class="col-lg-2">
                    <label for="date_to" class="form-label">Date To</label>
                    <input type="date" id="date_to" name="date_to" value="{{ request('date_to') }}" class="form-control">
                </div>
                <div class="col-lg-2">
                    <label for="status" class="form-label">Status</label>
                    <select id="status" name="status" class="form-select">
                        <option value="">All</option>
                        <option value="active" @selected(request('status') === 'active')>Active</option>
                        <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
                    </select>
                </div>
                <div class="col-lg-2">
                    <label for="subscribed" class="form-label">Subscribed</label>
                    <select id="subscribed" name="subscribed" class="form-select">
                        <option value="">All</option>
                        <option value="yes" @selected(request('subscribed') === 'yes')>Yes</option>
                        <option value="no" @selected(request('subscribed') === 'no')>No</option>
                    </select>
                </div>
                <div class="col-12 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Filter</button>
                    <a href="{{ route('app-users.index') }}" class="btn btn-light">Reset</a>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card overflow-hidden">
    <div class="table-responsive">
        <table class="table text-nowrap mb-0">
            <thead class="table-light">
                <tr>
                    <th>Name</th>
                    <th>Phone</th>
                    <th>Create Date</th>
                    <th>Status</th>
                    <th>Subscribed</th>
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
                        <td>
                            <span class="badge {{ $appUser->packages->isNotEmpty() ? 'badge-soft-primary' : 'badge-soft-secondary' }}">
                                {{ $appUser->packages->isNotEmpty() ? 'Yes' : 'No' }}
                            </span>
                        </td>
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
                        <td colspan="6" class="text-center text-muted py-4">No app users found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="p-3 border-top">{{ $appUsers->links() }}</div>
</div>
@endsection
