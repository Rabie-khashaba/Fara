@extends('layouts.auth', ['title' => 'Sign Up 2'])

@section('content')

<div class="col-xl-5">
    <div class="card auth-card">
        <div class="card-body px-3 py-5">
            <div class="mx-auto mb-4 text-center auth-logo">
                <a href="{{ route('any', 'home')}}" class="logo-dark">
                    <img src="/images/logo-sm.png" height="30" class="me-1" alt="logo sm" />
                    <img src="/images/logo-dark.png" height="24" alt="logo dark" />
                </a>

                <a href="{{ route('any', 'home')}}" class="logo-light">
                    <img src="/images/logo-sm.png" height="30" class="me-1" alt="logo sm" />
                    <img src="/images/logo-light.png" height="24" alt="logo light" />
                </a>
            </div>

            <h2 class="fw-bold text-center fs-18">
                Sign Up
            </h2>
            <p class="text-muted text-center mt-1 mb-4">
                New to our platform? Sign up now! It only
                takes a minute.
            </p>

            <div class="px-4">
                <form action="{{ route('any', 'home')}}" class="authentication-form">
                    <div class="mb-3">
                        <label class="form-label" for="example-name">Name</label>
                        <input type="name" id="example-name" name="example-name" class="form-control" placeholder="Enter your name" />
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="example-email">Email</label>
                        <input type="email" id="example-email" name="example-email" class="form-control" placeholder="Enter your email" />
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="example-password">Password</label>
                        <input type="password" id="example-password" class="form-control" placeholder="Enter your password" />
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="checkbox-signin" />
                            <label class="form-check-label" for="checkbox-signin">I accept Terms and
                                Condition</label>
                        </div>
                    </div>

                    <div class="mb-1 text-center d-grid">
                        <button class="btn btn-primary" type="submit">
                            Sign Up
                        </button>
                    </div>
                </form>
                <p class="mt-3 fw-semibold no-span">
                    OR sign with
                </p>

                <div class="text-center">
                    <a href="javascript:void(0);" class="btn btn-light shadow-none"><i class="bx bxl-google fs-20"></i></a>
                    <a href="javascript:void(0);" class="btn btn-light shadow-none"><i class="bx bxl-facebook fs-20"></i></a>
                    <a href="javascript:void(0);" class="btn btn-light shadow-none"><i class="bx bxl-github fs-20"></i></a>
                </div>
            </div>
            <!-- end col -->
        </div>
        <!-- end card-body -->
    </div>
    <!-- end card -->

    <p class="text-white mb-0 text-center">
        I already have an account
        <a href="{{ route('second', ['auth', 'login-2'])}}" class="text-white fw-bold ms-1">Sign In</a>
    </p>
</div>

@endsection
