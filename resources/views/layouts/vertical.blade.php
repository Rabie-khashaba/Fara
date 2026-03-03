<!doctype html>
<html lang="en">
<head>
    @include('layouts.partials/title-meta', ['title' => $title])
    @yield('css')
    @include('layouts.partials/head-css')
</head>

<body>

<div class="wrapper">
    @include('layouts.partials/topbar')
    @include('layouts.partials/left-sidebar')

    <div class="page-content">

        <div class="container-xxl">
            @include("layouts.partials/page-title", ['title' => $title,'subTitle' => $subTitle])
            @yield('content')
        </div>

        @include("layouts.partials/footer")
    </div>

</div>

@include("layouts.partials/right-sidebar")
@include("layouts.partials/footer-scripts")
@vite(['resources/js/app.js'])
@yield('script-bottom')

</body>
</html>
