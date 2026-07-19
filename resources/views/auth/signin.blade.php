@extends('layouts.base', ['subtitle' => 'Login'])

@section('body-attribuet')
    class="authentication-bg"
@endsection

<style>
    .authentication-bg {
     background: linear-gradient(135deg, #111111 0%, #1E1E1E 100%) !important;
    }
</style>

@section('content')
    <div class="account-pages py-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-6 col-lg-5">
                    <div class="card border-0 shadow-lg">
                        <div class="card-body p-5">
                            <div class="text-center">
                                <div class="mx-auto mb-4 text-center auth-logo">
                                    <a href="#" class="logo-dark">
                                        <img src="/images/switch-smarter.png" height="100" width="200" alt="logo dark">
                                    </a>

                                    <a href="#" class="logo-light">
                                        <img src="/images/switch-smarter.png" height="50" alt="logo light">
                                    </a>
                                </div>
                                {{-- <h4 class="fw-bold text-dark mb-2">Welcome Back!</h3>
                                    <p class="text-muted">Login in to your account to continue</p> --}}
                            </div>
                            <form method="POST" action="{{ route('login.post') }}" class="mt-4">
                                @csrf

                                <div class="mb-3">
                                    <label for="email" class="form-label">Email Address</label>
                                    <input type="email" class="form-control" id="email" name="email"
                                        value="{{ old('email') }}" placeholder="Enter your email" required>
                                    @error('email')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <label for="password" class="form-label">Password</label>
                                    </div>
                                    <input type="password" class="form-control" id="password" name="password"
                                        placeholder="Enter your password" required>
                                    @error('password')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>

                                <div class="form-check mb-3">
                                    <input type="checkbox" class="form-check-input" id="remember" name="remember">
                                    <label class="form-check-label" for="remember">Remember me</label>
                                </div>

                                <div class="d-grid">
                                    <button class="btn btn-dark btn-lg fw-medium" type="submit">Sign In</button>
                                </div>
                            </form>

                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
@endsection
