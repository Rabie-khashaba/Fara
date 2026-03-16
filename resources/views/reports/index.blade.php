@extends('layouts.vertical', ['title' => $reportTitle, 'subTitle' => 'Reports'])

@section('css')
<style>
    :root {
        --reports-primary: #2563a6;
        --reports-primary-dark: #1d4f91;
        --reports-primary-soft: #eef6ff;
        --reports-primary-soft-2: #dbeafe;
        --reports-border: #cfe0f5;
    }

    .reports-card {
        border: 1px solid var(--reports-border);
        background: linear-gradient(145deg, #f7fbff 0%, var(--reports-primary-soft) 60%, #f8fbff 100%);
        box-shadow: 0 16px 38px rgba(37, 99, 166, 0.08);
    }

    .reports-filter-card,
    .reports-table-card {
        border: 1px solid #d8e5f2;
        background: #ffffff;
        box-shadow: 0 10px 28px rgba(15, 23, 42, 0.05);
    }

    .reports-table-header {
        background: linear-gradient(135deg, var(--reports-primary-soft) 0%, var(--reports-primary-soft-2) 65%, #f3f8ff 100%);
        border-bottom: 1px solid var(--reports-border);
    }

    .reports-title {
        color: var(--reports-primary-dark);
    }

    .reports-accent {
        color: var(--reports-primary);
    }

    .reports-stat-0 {
        color: #2563a6;
    }

    .reports-stat-1 {
        color: #0f766e;
    }

    .reports-stat-2 {
        color: #b45309;
    }

    .reports-stat-3 {
        color: #7c3aed;
    }

    .reports-filter-card .form-label {
        color: #4d6278;
        font-weight: 600;
    }

    .reports-filter-card .form-control,
    .reports-filter-card .form-select {
        border-color: #d6e2f0;
        background-color: #fbfdff;
    }

    .reports-filter-card .form-control:focus,
    .reports-filter-card .form-select:focus {
        border-color: #6f9fd4;
        box-shadow: 0 0 0 0.2rem rgba(37, 99, 166, 0.12);
    }

    .reports-btn,
    .reports-btn:hover,
    .reports-btn:focus,
    .reports-btn:active {
        background: linear-gradient(135deg, var(--reports-primary) 0%, var(--reports-primary-dark) 100%);
        border-color: var(--reports-primary-dark);
        color: #fff !important;
        box-shadow: 0 10px 22px rgba(29, 79, 145, 0.2);
    }

    .reports-btn:hover,
    .reports-btn:focus,
    .reports-btn:active {
        background: linear-gradient(135deg, #225b99 0%, #17437d 100%);
        border-color: #17437d;
    }

    .reports-reset-btn {
        border-color: #d7e2ee;
        background: linear-gradient(135deg, #ffffff 0%, #f3f7fb 100%);
        color: #526579;
    }

    .reports-reset-btn:hover,
    .reports-reset-btn:focus {
        border-color: #c7d7e8;
        background: linear-gradient(135deg, #f7fbff 0%, #eaf2fb 100%);
        color: #33485f;
    }
</style>
@endsection

@section('content')
@if (! empty($summaryCards))
    <div class="row">
        @foreach ($summaryCards as $index => $card)
            <div class="col-md-6 col-xl-4">
                <div class="card reports-card">
                    <div class="card-body">
                        <p class="text-uppercase fs-12 fw-semibold mb-2 reports-stat-{{ $index % 4 }}">{{ $card['label'] }}</p>
                        <h3 class="mb-0 reports-title reports-stat-{{ $index % 4 }}">{{ $card['value'] }}</h3>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endif

<div class="card border-0 reports-filter-card">
    <div class="card-body">
        <form method="GET" action="{{ $filterAction }}">
            <div class="row g-3 align-items-end">
                @if (in_array('search', $filters, true))
                    <div class="col-lg-4">
                        <label class="form-label">Search</label>
                        <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search">
                    </div>
                @endif

                @if (in_array('category', $filters, true))
                    <div class="col-lg-2">
                        <label class="form-label">Category</label>
                        <select name="category" class="form-select">
                            <option value="">All</option>
                            @foreach ($filterOptions['categories'] ?? [] as $value => $label)
                                <option value="{{ $value }}" @selected(request('category') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                @if (in_array('country_code', $filters, true))
                    <div class="col-lg-2">
                        <label class="form-label">Country</label>
                        <select name="country_code" class="form-select">
                            <option value="">All</option>
                            @foreach ($filterOptions['countries'] ?? [] as $country)
                                <option value="{{ $country }}" @selected(request('country_code') === $country)>{{ $country }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                @if (in_array('status', $filters, true))
                    <div class="col-lg-2">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">All</option>
                            <option value="active" @selected(request('status') === 'active')>Active</option>
                            <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
                        </select>
                    </div>
                @endif

                @if (in_array('status_select', $filters, true))
                    <div class="col-lg-2">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">All</option>
                            <option value="draft" @selected(request('status') === 'draft')>Draft</option>
                            <option value="published" @selected(request('status') === 'published')>Published</option>
                            <option value="archived" @selected(request('status') === 'archived')>Archived</option>
                        </select>
                    </div>
                @endif

                @if (in_array('ghost', $filters, true))
                    <div class="col-lg-2">
                        <label class="form-label">Ghost</label>
                        <select name="ghost" class="form-select">
                            <option value="">All</option>
                            <option value="yes" @selected(request('ghost') === 'yes')>Ghost</option>
                            <option value="no" @selected(request('ghost') === 'no')>Normal</option>
                        </select>
                    </div>
                @endif

                @if (in_array('city_id', $filters, true))
                    <div class="col-lg-3">
                        <label class="form-label">City</label>
                        <select name="city_id" class="form-select">
                            <option value="">All Cities</option>
                            @foreach ($filterOptions['cities'] ?? [] as $city)
                                <option value="{{ $city->id }}" @selected((int) request('city_id') === $city->id)>{{ $city->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                @if (in_array('date_range', $filters, true))
                    <div class="col-lg-2">
                        <label class="form-label">Date From</label>
                        <input type="date" name="date_from" value="{{ $dateFrom }}" class="form-control">
                    </div>
                    <div class="col-lg-2">
                        <label class="form-label">Date To</label>
                        <input type="date" name="date_to" value="{{ $dateTo }}" class="form-control">
                    </div>
                @endif

                <div class="col-lg-2 d-flex gap-2">
                    <button type="submit" class="btn reports-btn w-100">Filter</button>
                    <a href="{{ $resetRoute }}" class="btn reports-reset-btn w-100">Reset</a>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card overflow-hidden border-0 reports-table-card">
    <div class="card-header reports-table-header">
        <h4 class="card-title mb-0 reports-title">{{ $reportTitle }}</h4>
    </div>
    <div class="table-responsive">
        <table class="table align-middle text-nowrap mb-0">
            <thead class="table-light">
                <tr>
                    @foreach ($columns as $column)
                        <th>{{ $column }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $row)
                    <tr>
                        @foreach ($row as $cell)
                            <td>{{ $cell }}</td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($columns) }}" class="text-center text-muted py-4">No data found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($paginator)
        <div class="p-3 border-top">{{ $paginator->links() }}</div>
    @endif
</div>
@endsection
