@extends('layouts.vertical', ['title' => 'Places Management', 'subTitle' => 'Places Management'])

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div>
                    <h4 class="mb-1">Places Management</h4>
                    <p class="text-muted mb-0">Manage check-in places and monitor their activity.</p>
                </div>
                <a href="{{ route('places.create') }}" class="btn btn-danger">
                    <i class="bi bi-plus-circle me-1"></i>Create Place
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
        <form method="GET" action="{{ route('places.index') }}">
            <div class="row g-3 align-items-end">
                <div class="col-lg-8">
                    <label for="search" class="form-label">Search</label>
                    <input
                        type="text"
                        id="search"
                        name="search"
                        value="{{ request('search') }}"
                        class="form-control"
                        placeholder="Search by place name"
                    >
                </div>
                <div class="col-lg-4">
                    <label for="category" class="form-label">Category</label>
                    <select id="category" name="category" class="form-select">
                        <option value="">All</option>
                        @foreach ($categories as $value => $label)
                            <option value="{{ $value }}" @selected(request('category') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Filter</button>
                    <a href="{{ route('places.index') }}" class="btn btn-light">Reset</a>
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
                    <th>Place Name</th>
                    <th>Latitude</th>
                    <th>Longitude</th>
                    <th>Category</th>
                    <th>Check-ins</th>
                    <th>Users Currently There</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($places as $place)
                    <tr>
                        <td class="fw-semibold">{{ $place->name }}</td>
                        <td>{{ number_format((float) $place->latitude, 6) }}</td>
                        <td>{{ number_format((float) $place->longitude, 6) }}</td>
                        <td>
                            <span class="badge badge-soft-primary">{{ $categories[$place->category] ?? ucfirst($place->category) }}</span>
                        </td>
                        <td>{{ number_format($place->check_ins_count) }}</td>
                        <td>{{ number_format((int) $place->current_users_count) }}</td>
                        <td class="text-end">
                            <a href="{{ route('places.edit', $place) }}" class="btn btn-sm btn-soft-secondary">Edit</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">No places found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="p-3 border-top">{{ $places->links() }}</div>
</div>
@endsection
