@extends('layouts.vertical', ['title' => 'Analytics','subTitle' => 'Dashboards'])

@section('css')
                    <style>
        .analytics-summary-panels {
            height: 450px;
        }

        .analytics-summary-panel {
            height: 450px;
        }

        .analytics-summary-panel-left #conversions {
            min-height: 260px;
        }

        .analytics-summary-panel-left .analytics-summary-chart {
            flex: 1 1 auto;
            display: flex;
            align-items: center;
        }

        .analytics-summary-panel-right .analytics-summary-chart {
            flex: 1 1 auto;
            display: flex;
            align-items: flex-end;
        }

        .analytics-summary-panel-right #dash-performance-chart {
            width: 100%;
        }

        @media (max-width: 991.98px) {
            .analytics-summary-panels,
            .analytics-summary-panel {
                height: auto;
            }
        }

        .city-checkin-empty {
            min-height: 300px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #8391a2;
        }
    </style>
@endsection

@section('content')
    <div class="row">
        <div class="col-xxl-3">
            <div class="row">
                <div class="col-md-6 col-xxl-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-6">
                                    <div
                                        class="avatar-md bg-primary bg-opacity-10 rounded"
                                    >
                                        <iconify-icon
                                            icon="solar:users-group-rounded-bold-duotone"
                                            class="avatar-title text-primary fs-32"
                                        ></iconify-icon>
                                    </div>
                                </div>
                                <!-- end col -->
                                <div class="col-6 text-end">
                                    <p
                                        class="text-muted mb-0 text-truncate"
                                    >
                                        Total Users
                                    </p>
                                    <h3
                                        class="text-dark mt-1 mb-0"
                                    >
                                        {{ $totalUsersPercentage }}
                                    </h3>
                                </div>
                                <!-- end col -->
                            </div>
                            <!-- end row-->
                        </div>
                        <!-- end card body -->
                    </div>
                    <!-- end card -->
                </div>
                <!-- end col -->
                <div class="col-md-6 col-xxl-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-6">
                                    <div
                                        class="avatar-md bg-success bg-opacity-10 rounded"
                                    >
                                        <iconify-icon
                                            icon="solar:user-check-rounded-bold-duotone"
                                            class="avatar-title text-success fs-32"
                                        ></iconify-icon>
                                    </div>
                                </div>
                                <!-- end col -->
                                <div class="col-6 text-end">
                                    <p
                                        class="text-muted mb-0 text-truncate"
                                    >
                                        Reposts
                                    </p>
                                    <h3
                                        class="text-dark mt-1 mb-0"
                                    >
                                        {{ $repostsPercentage }}
                                    </h3>
                                </div>
                                <!-- end col -->
                            </div>
                            <!-- end row-->
                        </div>
                        <!-- end card body -->
                    </div>
                    <!-- end card -->
                </div>
                <!-- end col -->
                <div class="col-md-6 col-xxl-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-6">
                                    <div
                                        class="avatar-md bg-danger bg-opacity-10 rounded"
                                    >
                                        <iconify-icon
                                            icon="solar:chart-2-bold-duotone"
                                            class="avatar-title text-danger fs-32"
                                        ></iconify-icon>
                                    </div>
                                </div>
                                <!-- end col -->
                                <div class="col-6 text-end">
                                    <p
                                        class="text-muted mb-0 text-truncate"
                                    >
                                        Posts
                                    </p>
                                    <h3
                                        class="text-dark mt-1 mb-0"
                                    >
                                        {{ $postsPercentage }}
                                    </h3>
                                </div>
                                <!-- end col -->
                            </div>
                            <!-- end row-->
                        </div>
                        <!-- end card body -->
                    </div>
                    <!-- end card -->
                </div>
                <!-- end col -->
                <div class="col-md-6 col-xxl-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-6">
                                    <div
                                        class="avatar-md bg-warning bg-opacity-10 rounded"
                                    >
                                        <iconify-icon
                                            icon="solar:user-plus-rounded-bold-duotone"
                                            class="avatar-title text-warning fs-32"
                                        ></iconify-icon>
                                    </div>
                                </div>
                                <!-- end col -->
                                <div class="col-6 text-end">
                                    <p
                                        class="text-muted mb-0 text-truncate"
                                    >
                                        New Users
                                    </p>
                                    <h3
                                        class="text-dark mt-1 mb-0"
                                    >
                                        {{ $newUsersPercentage }}
                                    </h3>
                                </div>
                                <!-- end col -->
                            </div>
                            <!-- end row-->
                        </div>
                        <!-- end card body -->
                    </div>
                    <!-- end card -->
                </div>
                <!-- end col -->
            </div>
            <!-- end row -->
        </div>
        <!-- end col -->
        <div class="col-xxl-9">
            <div class="card">
                <div class="card-body p-0">
                    <div class="row g-0 align-items-stretch analytics-summary-panels">
                        <div class="col-lg-4 d-flex">
                            <div class="p-3 d-flex flex-column justify-content-between h-100 w-100 analytics-summary-panel analytics-summary-panel-left">
                                <h5 class="card-title">
                                    Conversions
                                </h5>
                                <div class="analytics-summary-chart">
                                    <div
                                        id="conversions"
                                        class="apex-charts mb-2 mt-n2"
                                    ></div>
                                </div>
                                <div class="row text-center">
                                    <div class="col-6">
                                        <p
                                            class="text-muted mb-2"
                                        >
                                            This Week
                                        </p>
                                        <h3
                                            class="text-dark mb-3"
                                        >
                                            23.5k
                                        </h3>
                                    </div>
                                    <!-- end col -->
                                    <div class="col-6">
                                        <p
                                            class="text-muted mb-2"
                                        >
                                            Last Week
                                        </p>
                                        <h3
                                            class="text-dark mb-3"
                                        >
                                            41.05k
                                        </h3>
                                    </div>
                                    <!-- end col -->
                                </div>
                                <!-- end row -->
                            </div>
                        </div>
                        <!-- end left chart card -->
                        <div class="col-lg-8 border-start d-flex">
                            <div class="p-3 d-flex flex-column justify-content-between h-100 w-100 analytics-summary-panel analytics-summary-panel-right">
                                <div
                                    class="d-flex justify-content-between align-items-center"
                                >
                                    <h4 class="card-title">
                                        Performance
                                    </h4>
                                    <div>
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-light"
                                        >
                                            ALL
                                        </button>
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-light"
                                        >
                                            1M
                                        </button>
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-light"
                                        >
                                            6M
                                        </button>
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-light active"
                                        >
                                            1Y
                                        </button>
                                    </div>
                                </div>
                                <!-- end card-title-->

                                <div dir="ltr" class="analytics-summary-chart">
                                    <div
                                        id="dash-performance-chart"
                                        class="apex-charts"
                                    ></div>
                                </div>
                            </div>
                        </div>
                        <!-- end right chart card -->
                    </div>
                    <!-- end chart card -->
                </div>
                <!-- end card body -->
            </div>
            <!-- end card -->
        </div>
        <!-- end col -->
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div
                    class="d-flex card-header justify-content-between align-items-center border-bottom border-dashed"
                >
                    <h4 class="card-title">
                        Cities Location
                    </h4>
                    <div class="dropdown">
                        <a
                            href="#"
                            class="dropdown-toggle btn btn-sm btn-outline-light"
                            data-bs-toggle="dropdown"
                            aria-expanded="false"
                        >
                            View Data
                        </a>
                        <div
                            class="dropdown-menu dropdown-menu-end"
                        >
                            <a
                                href="javascript:void(0);"
                                class="dropdown-item"
                            >Download</a
                            >
                            <a
                                href="javascript:void(0);"
                                class="dropdown-item"
                            >Export</a
                            >
                            <a
                                href="javascript:void(0);"
                                class="dropdown-item"
                            >Import</a
                            >
                        </div>
                    </div>
                </div>

                <div class="card-body pt-0">
                    <div class="row align-items-center">
                        <div class="col-lg-7">
                            <div
                                id="world-map-markers"
                                class="my-3"
                                style="height: 300px"
                            ></div>
                        </div>
                        <div class="col-lg-5" dir="ltr">
                            <div class="p-3">
                                @forelse ($cityLocationTop as $index => $city)
                                    <div
                                        class="d-flex justify-content-between align-items-center"
                                    >
                                        <p class="mb-1">
                                            <iconify-icon
                                                icon="solar:map-point-bold-duotone"
                                                class="fs-16 align-middle me-1"
                                            ></iconify-icon>
                                            <span
                                                class="align-middle"
                                            >{{ $city['name'] }}</span>
                                        </p>
                                    </div>
                                    <div
                                        class="row align-items-center {{ $loop->last ? '' : 'mb-3' }}"
                                    >
                                        <div class="col">
                                            <div
                                                class="progress progress-soft progress-sm"
                                            >
                                                <div
                                                    class="progress-bar {{ $index === 0 ? 'bg-secondary' : ($index === 1 ? 'bg-info' : ($index === 2 ? 'bg-warning' : ($index === 3 ? 'bg-success' : 'bg-primary'))) }}"
                                                    role="progressbar"
                                                    style="width: {{ $city['percentage'] }}%;"
                                                    aria-valuenow="{{ $city['percentage'] }}"
                                                    aria-valuemin="0"
                                                    aria-valuemax="100"
                                                ></div>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <p
                                                class="mb-0 fs-13 fw-semibold"
                                            >
                                                {{ number_format($city['count']) }}
                                            </p>
                                        </div>
                                    </div>
                                @empty
                                    <div class="city-checkin-empty">
                                        No check-ins yet.
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
                <!-- end card-body-->
            </div>
            <!-- end card-->
        </div>
        <!-- end col-->
    </div>

    {{-- <div class="row">
        <div class="col-lg-4">
            <div class="card">
                <div
                    class="d-flex card-header justify-content-between align-items-center border-bottom border-dashed"
                >
                    <h4 class="card-title">
                        Session By Browser
                    </h4>
                    <div class="dropdown">
                        <a
                            href="#"
                            class="dropdown-toggle arrow-none card-drop"
                            data-bs-toggle="dropdown"
                            aria-expanded="false"
                        >
                            <iconify-icon
                                icon="iconamoon:menu-kebab-vertical-circle-duotone"
                                class="fs-20 align-middle text-muted"
                            ></iconify-icon>
                        </a>
                        <div
                            class="dropdown-menu dropdown-menu-end"
                        >
                            <!-- item-->
                            <a
                                href="javascript:void(0);"
                                class="dropdown-item"
                            >Download</a
                            >
                            <!-- item-->
                            <a
                                href="javascript:void(0);"
                                class="dropdown-item"
                            >Export</a
                            >
                            <!-- item-->
                            <a
                                href="javascript:void(0);"
                                class="dropdown-item"
                            >Import</a
                            >
                        </div>
                    </div>
                </div>
                <div class="card-body py-2 px-0">
                    <div
                        class="px-2"
                        data-simplebar
                        style="height: 270px"
                    >
                        <div
                            class="d-flex justify-content-between align-items-center p-2"
                        >
                                            <span class="align-middle fw-medium"
                                            >Chrome</span
                                            >
                            <span class="fw-semibold text-muted"
                            >62.5%</span
                            >
                            <span class="fw-semibold text-muted"
                            >5.06k</span
                            >
                        </div>

                        <div
                            class="d-flex justify-content-between align-items-center p-2"
                        >
                                            <span class="align-middle fw-medium"
                                            >Firefox</span
                                            >
                            <span class="fw-semibold text-muted"
                            >12.3%</span
                            >
                            <span class="fw-semibold text-muted"
                            >1.5k</span
                            >
                        </div>

                        <div
                            class="d-flex justify-content-between align-items-center p-2"
                        >
                                            <span class="align-middle fw-medium"
                                            >Safari</span
                                            >
                            <span class="fw-semibold text-muted"
                            >9.86%</span
                            >
                            <span class="fw-semibold text-muted"
                            >1.03k</span
                            >
                        </div>

                        <div
                            class="d-flex justify-content-between align-items-center p-2"
                        >
                                            <span class="align-middle fw-medium"
                                            >Brave</span
                                            >
                            <span class="fw-semibold text-muted"
                            >3.15%</span
                            >
                            <span class="fw-semibold text-muted"
                            >0.3k</span
                            >
                        </div>

                        <div
                            class="d-flex justify-content-between align-items-center p-2"
                        >
                                            <span class="align-middle fw-medium"
                                            >Opera</span
                                            >
                            <span class="fw-semibold text-muted"
                            >3.01%</span
                            >
                            <span class="fw-semibold text-muted"
                            >1.58k</span
                            >
                        </div>

                        <div
                            class="d-flex justify-content-between align-items-center p-2"
                        >
                                            <span class="align-middle fw-medium"
                                            >Falkon</span
                                            >
                            <span class="fw-semibold text-muted"
                            >2.8%</span
                            >
                            <span class="fw-semibold text-muted"
                            >0.01k</span
                            >
                        </div>

                        <div
                            class="d-flex justify-content-between align-items-center p-2"
                        >
                                            <span class="align-middle fw-medium"
                                            >Other</span
                                            >
                            <span class="fw-semibold text-muted"
                            >6.38%</span
                            >
                            <span class="fw-semibold text-muted"
                            >3.6k</span
                            >
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="card">
                <div
                    class="card-header d-flex align-items-center justify-content-between gap-2"
                >
                    <h4 class="card-title flex-grow-1">
                        Top Pages
                    </h4>
                    <div>
                        <a
                            href="#"
                            class="btn btn-sm btn-soft-primary"
                        >View All</a
                        >
                    </div>
                </div>
                <div class="table-responsive">
                    <table
                        class="table table-hover table-nowrap table-centered m-0"
                    >
                        <thead class="bg-light bg-opacity-50">
                        <tr>
                            <th class="text-muted py-1">
                                Page Path
                            </th>
                            <th class="text-muted py-1">
                                Page Views
                            </th>
                            <th class="text-muted py-1">
                                Avg Time on Page
                            </th>
                            <th class="text-muted py-1">
                                Exit Rate
                            </th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr>
                            <td>
                                <a
                                    href="#"
                                    class="text-muted"
                                >reback/dashboard.html</a
                                >
                            </td>
                            <td>4265</td>
                            <td>09m:45s</td>
                            <td>
                                                    <span
                                                        class="badge badge-soft-danger"
                                                    >20.4%</span
                                                    >
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <a
                                    href="#"
                                    class="text-muted"
                                >reback/chat.html</a
                                >
                            </td>
                            <td>2584</td>
                            <td>05m:02s</td>
                            <td>
                                                    <span
                                                        class="badge badge-soft-warning"
                                                    >12.25%</span
                                                    >
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <a
                                    href="#"
                                    class="text-muted"
                                >reback/auth-login.html</a
                                >
                            </td>
                            <td>3369</td>
                            <td>04m:25s</td>
                            <td>
                                                    <span
                                                        class="badge badge-soft-success"
                                                    >5.2%</span
                                                    >
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <a
                                    href="#"
                                    class="text-muted"
                                >reback/email.html</a
                                >
                            </td>
                            <td>985</td>
                            <td>02m:03s</td>
                            <td>
                                                    <span
                                                        class="badge badge-soft-danger"
                                                    >64.2%</span
                                                    >
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <a
                                    href="#"
                                    class="text-muted"
                                >reback/social.html</a
                                >
                            </td>
                            <td>653</td>
                            <td>15m:56s</td>
                            <td>
                                                    <span
                                                        class="badge badge-soft-success"
                                                    >2.4%</span
                                                    >
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div> --}}
@endsection

@section('script-bottom')
    <script>
        window.dashboardAnalytics = @json($performanceChart);
        window.saudiCitiesMap = @json($saudiCitiesMap);
    </script>
    @vite(['resources/js/pages/dashboard.analytics.js'])
@endsection
