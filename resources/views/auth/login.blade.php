@extends('layouts.auth', ['title' => 'Login'])

@section('content')
    <div class="col-xl-12">
        <div class="card auth-card">
            <div class="card-body p-0">
                <div class="row align-items-center g-0">
                    <div
                        class="col-lg-6 d-none d-lg-inline-block border-end"
                    >
                        <div class="auth-page-sidebar">
                            <img
                                src="/images/sign-in.svg"
                                alt="auth"
                                class="img-fluid"
                            />
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="p-4">
                            <div
                                class="mx-auto mb-4 text-center auth-logo"
                            >
                                <a
                                    href="{{ route('any', 'home') }}"
                                    class="logo-dark"
                                >
                                    <img
                                        src="/images/logo-sm.png"
                                        height="30"
                                        class="me-1"
                                        alt="logo sm"
                                    />
                                    <img
                                        src="/images/logo-dark.png"
                                        height="24"
                                        alt="logo dark"
                                    />
                                </a>

                                <a
                                    href="{{ route('any', 'home') }}"
                                    class="logo-light"
                                >
                                    <img
                                        src="/images/logo-sm.png"
                                        height="30"
                                        class="me-1"
                                        alt="logo sm"
                                    />
                                    <img
                                        src="/images/logo-light.png"
                                        height="24"
                                        alt="logo light"
                                    />
                                </a>
                            </div>
                            <h2
                                class="fw-bold text-center fs-18"
                            >
                                Sign In
                            </h2>
                            <p
                                class="text-muted text-center mt-1 mb-4"
                            >
                                Enter your phone number and
                                password to access admin panel.
                            </p>

                            <div
                                class="row justify-content-center"
                            >
                                <div class="col-12 col-md-8">
                                    <form method="POST" action="{{ route('login') }}"
                                          class="authentication-form">
                                        @csrf
                                        @if (sizeof($errors) > 0)
                                            @foreach ($errors->all() as $error)
                                                <p class="text-danger mb-3">{{ $error }}</p>
                                            @endforeach
                                        @endif

                                        <div class="mb-3">
                                            <label
                                                class="form-label"
                                                for="example-phone"
                                            >Phone</label>
                                            <input
                                                type="text"
                                                id="example-phone"
                                                name="phone"
                                                value="{{ old('phone') }}"
                                                class="form-control"
                                                placeholder="Enter your phone"
                                            />
                                        </div>
                                        <div class="mb-3">
                                            <a
                                                href="{{ route('second', [ 'auth' , 'reset-password']) }}"
                                                class="float-end text-muted text-unline-dashed ms-1"
                                            >Reset
                                                password</a
                                            >
                                            <label
                                                class="form-label"
                                                for="example-password"
                                            >Password</label
                                            >
                                            <input
                                                type="password"
                                                id="example-password"
                                                name="password"
                                                class="form-control"
                                                placeholder="Enter your password"
                                            />
                                        </div>
                                        <div class="mb-3">
                                            <div
                                                class="form-check"
                                            >
                                                <input
                                                    type="checkbox"
                                                    class="form-check-input"
                                                    id="checkbox-signin"
                                                    name="remember"
                                                    {{ old('remember') ? 'checked' : '' }}
                                                />
                                                <label
                                                    class="form-check-label"
                                                    for="checkbox-signin"
                                                >Remember
                                                    me</label
                                                >
                                            </div>
                                        </div>

                                        <div class="mb-1 text-center d-grid">
                                            <button
                                                class="btn btn-primary"
                                                type="submit">
                                                Sign In
                                            </button>
                                        </div>
                                    </form>

                                    <p class="mt-3 fw-semibold no-span">
                                        OR sign with
                                    </p>

                                    <div class="text-center">
                                        <a
                                            href="{{ route('auth.social.redirect', 'google') }}"
                                            class="btn btn-light shadow-none"
                                            aria-label="Sign in with Google"><i
                                                class="bx bxl-google fs-20"></i></a>
                                        <a
                                            href="{{ route('auth.social.redirect', 'facebook') }}"
                                            class="btn btn-light shadow-none"
                                            aria-label="Sign in with Facebook"><i
                                                class="bx bxl-facebook fs-20"></i></a>
                                        <a
                                            href="{{ route('auth.social.redirect', 'apple') }}"
                                            class="btn btn-light shadow-none"
                                            aria-label="Sign in with Apple"><i
                                                class="bx bxl-apple fs-20"></i></a>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

                </div>

            </div>

        </div>

        <p class="text-white mb-0 text-center">
            Don't have an account?
            <a
                href="{{ route('second', [ 'auth' , 'register']) }}"
                class="text-white fw-bold ms-1"
            >Sign Up</a>
        </p>
    </div>
@endsection
