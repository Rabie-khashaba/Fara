@extends('layouts.vertical', ['title' => 'Reports', 'subTitle' => 'Reports'])

@section('content')
<div class="row">
    <div class="col-md-6 col-xl-3">
        <div class="card">
            <div class="card-body">
                <p class="text-muted mb-1">Total Posts</p>
                <h3 class="mb-0">{{ $totalPosts }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="card">
            <div class="card-body">
                <p class="text-muted mb-1">Published Posts</p>
                <h3 class="mb-0">{{ $publishedPosts }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="card">
            <div class="card-body">
                <p class="text-muted mb-1">Total Check-ins</p>
                <h3 class="mb-0">{{ $totalCheckIns }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="card">
            <div class="card-body">
                <p class="text-muted mb-1">Total Activities</p>
                <h3 class="mb-0">{{ $totalActivities }}</h3>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-xl-6">
        <div class="card overflow-hidden">
            <div class="card-header">
                <h4 class="card-title mb-0">Most Visited Places</h4>
            </div>
            <div class="table-responsive">
                <table class="table text-nowrap mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Place</th>
                            <th>Category</th>
                            <th>Check-ins</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($topPlaces as $place)
                            <tr>
                                <td>{{ $place->name }}</td>
                                <td>{{ ucfirst($place->category) }}</td>
                                <td>{{ number_format($place->check_ins_count) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center text-muted py-4">No place data found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-xl-6">
        <div class="card overflow-hidden">
            <div class="card-header">
                <h4 class="card-title mb-0">Cities With Most Check-ins</h4>
            </div>
            <div class="table-responsive">
                <table class="table text-nowrap mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>City</th>
                            <th>Country</th>
                            <th>Check-ins</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($topCities as $city)
                            <tr>
                                <td>{{ $city->name }}</td>
                                <td>{{ $city->country_code }}</td>
                                <td>{{ number_format($city->check_ins_count) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center text-muted py-4">No city data found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-xl-6">
        <div class="card overflow-hidden">
            <div class="card-header">
                <h4 class="card-title mb-0">Most Active Users</h4>
            </div>
            <div class="table-responsive">
                <table class="table text-nowrap mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>User</th>
                            <th>Activities</th>
                            <th>Posts</th>
                            <th>Check-ins</th>
                            <th>Score</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($mostActiveUsers as $user)
                            <tr>
                                <td>{{ $user->name }}</td>
                                <td>{{ number_format($user->activities_count) }}</td>
                                <td>{{ number_format($user->posts_count) }}</td>
                                <td>{{ number_format($user->check_ins_count) }}</td>
                                <td>{{ number_format($user->activity_score) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">No user activity found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-xl-6">
        <div class="card overflow-hidden">
            <div class="card-header">
                <h4 class="card-title mb-0">Daily Check-ins</h4>
            </div>
            <div class="table-responsive">
                <table class="table text-nowrap mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Check-ins</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($dailyCheckInRows as $row)
                            <tr>
                                <td>{{ $row['date'] }}</td>
                                <td>{{ number_format($row['count']) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="text-center text-muted py-4">No check-in data found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
