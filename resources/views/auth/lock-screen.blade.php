@extends('layouts.base', ['subtitle' => 'Lock Screen'])

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

                                {{-- Display logged user --}}
                                <img src="{{ Auth::user()?->image_path ? asset(Auth::user()->image_path) : asset('images/users/avatar-6.jpg') }}"
                                    alt="User Avatar" class="rounded-circle mb-3"
                                    style="width:80px;height:80px;object-fit:cover;">

                                <h4 class="fw-bold text-dark mb-2">
                                    Hi ! {{ Auth::user()?->name ?? 'Guest' }}
                                </h4>
                                <p class="text-muted">Enter your password to access the admin.</p>
                            </div>

                            {{-- Lock screen form --}}
                            <form action="{{ route('login') }}" method="POST" class="mt-4">
                                @csrf
                                <input type="hidden" name="email" value="{{ Auth::user()?->email }}">

                                <div class="mb-3">
                                    <label class="form-label" for="password">Password</label>
                                    <input type="password" id="password" name="password"
                                        class="form-control @error('password') is-invalid @enderror"
                                        placeholder="Enter your password">

                                    {{-- Show validation error for password --}}
                                    @error('password')
                                        <span class="invalid-feedback d-block" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>

                                {{-- Show global auth error (like wrong password/email) --}}
                                @if (session('error'))
                                    <div class="alert alert-danger">
                                        {{ session('error') }}
                                    </div>
                                @endif
                                @if ($errors->has('email'))
                                    <div class="alert alert-danger">
                                        {{ $errors->first('email') }}
                                    </div>
                                @endif

                                <div class="mb-1 text-center d-grid">
                                    <button class="btn btn-dark btn-lg fw-medium" type="submit">Unlock</button>
                                </div>
                            </form>

                        </div>
                    </div>

                    <p class="text-center mt-4 text-white text-opacity-50">
                        Not you? return
                        <a href="{{ route('login') }}" class="text-decoration-none text-white fw-bold">Sign In</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
@endsection
