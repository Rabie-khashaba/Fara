<!doctype html>
<html lang="en">
<head>
    @include('layouts.partials/title-meta', ['title' => $title])
    @yield('css')
    @include('layouts.partials/head-css')
</head>

<body class="authentication-bg">
<div class="account-pages pt-2 pt-sm-5 pb-4 pb-sm-5">
    <div class="container">
        <div class="row justify-content-center">
            @yield('content')
        </div>

    </div>
</div>

@include('layouts.partials/footer-scripts')
@vite(['resources/js/app.js'])
@yield('script-bottom')
</body>
</html>
