@extends('layouts.auth', ['title' => 'Sign Up'])

@section('content')

<div class="col-xl-12">
    <div class="card auth-card">
        <div class="card-body p-0">
            <div class="row align-items-center g-0">
                <div class="col-lg-6 d-none d-lg-inline-block border-end">
                    <div class="auth-page-sidebar">
                        <img src="/images/sign-in.svg" alt="auth" class="img-fluid" />
                    </div>
                </div>
                <!-- end col -->

                <div class="col-lg-6">
                    <div class="p-4">
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
                            New to our platform? Sign up
                            now! It only takes a minute.
                        </p>

                        <div class="row justify-content-center">
                            <div class="col-12 col-md-8">
                                <form method="POST" action="{{ route('register') }}" class="authentication-form">
                                    @csrf
                                    @if ($errors->any())
                                        @foreach ($errors->all() as $error)
                                            <p class="text-danger mb-3">{{ $error }}</p>
                                        @endforeach
                                    @endif
                                    <div class="mb-3">
                                        <label class="form-label" for="example-name">Name</label>
                                        <input type="text" id="example-name" name="name" value="{{ old('name') }}" class="form-control" placeholder="Enter your name" />
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label" for="example-phone">Phone</label>
                                        <input type="text" id="example-phone" name="phone" value="{{ old('phone') }}" class="form-control" placeholder="Enter your phone" />
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label" for="example-role-id">Role ID</label>
                                        <input type="number" id="example-role-id" name="role_id" value="{{ old('role_id') }}" class="form-control" placeholder="Enter role id" />
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label" for="example-type">Type</label>
                                        <select id="example-type" name="type" class="form-select">
                                            <option value="admin" selected>admin</option>
                                            <option value="user">user</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label" for="example-password">Password</label>
                                        <input type="password" id="example-password" name="password" class="form-control" placeholder="Enter your password" />
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label" for="example-password-confirmation">Confirm Password</label>
                                        <input type="password" id="example-password-confirmation" name="password_confirmation" class="form-control" placeholder="Confirm your password" />
                                    </div>
                                    <div class="mb-3">
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input" id="checkbox-signin" />
                                            <label class="form-check-label" for="checkbox-signin">I accept
                                                Terms and
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
                        <!-- end row -->
                    </div>
                </div>
                <!-- end col -->
            </div>
            <!-- end row -->
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
