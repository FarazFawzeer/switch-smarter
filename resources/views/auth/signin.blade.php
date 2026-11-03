@extends('layouts.base', ['subtitle' => 'Login'])

{{-- the login page always uses its own light design, whatever the saved app theme --}}
@section('html-attribute')
    data-bs-theme="light"
@endsection

@section('body-attribuet')
    class="ss-login-body"
@endsection

@section('content')
    <style>
        .ss-login-body {
            margin: 0; min-height: 100vh; font-family: 'Roboto', system-ui, sans-serif;
            background: radial-gradient(900px 500px at 50% -10%, rgba(35,160,125,.18), transparent 60%),
                        linear-gradient(160deg, #13293D 0%, #0C1D2C 55%, #08141F 100%);
            background-attachment: fixed;
        }
        .ss-login { min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 32px 16px; }
        .ss-login-card { width: 100%; max-width: 400px; background: #fff; border-radius: 20px; padding: 40px 34px 34px;
            box-shadow: 0 24px 60px rgba(0,0,0,.35); }
        .ss-login-logo { display: block; height: 46px; width: auto; max-width: 200px; margin: 0 auto 26px; object-fit: contain; }
        .ss-login-title { font-size: 22px; font-weight: 700; color: #16233B; margin: 0 0 24px; text-align: center; }

        .ss-field { margin-bottom: 18px; }
        .ss-field label { display: block; font-size: 13px; font-weight: 600; color: #3C4655; margin-bottom: 7px; }
        .ss-input { position: relative; }
        .ss-input .ui-icon { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #98A3B3; pointer-events: none; }
        .ss-input input {
            width: 100%; height: 50px; border: 1.5px solid #E1E6ED; border-radius: 12px; padding: 0 46px 0 44px;
            font-size: 15px; color: #16233B; background: #F8FAFC; transition: border-color .15s ease, box-shadow .15s ease, background .15s ease; outline: 0;
        }
        .ss-input input::placeholder { color: #A3AEBC; }
        .ss-input input:focus { border-color: #1C8C6C; background: #fff; box-shadow: 0 0 0 4px rgba(28,140,108,.12); }
        .ss-input.has-error input { border-color: #D64545; background: #FFF8F8; }
        .ss-eye { position: absolute; right: 8px; top: 50%; transform: translateY(-50%); width: 36px; height: 36px; border: 0; background: transparent;
            color: #98A3B3; border-radius: 8px; display: flex; align-items: center; justify-content: center; cursor: pointer; }
        .ss-eye:hover { color: #3C4655; background: #EEF2F7; }
        .ss-error { display: flex; align-items: center; gap: 6px; color: #C23B3B; font-size: 12.5px; margin-top: 6px; }

        .ss-alert { display: flex; gap: 10px; align-items: flex-start; background: #FDECEC; color: #B23030; border-radius: 12px; padding: 12px 14px; font-size: 13.5px; margin-bottom: 20px; }

        .ss-row { display: flex; align-items: center; justify-content: space-between; margin: 4px 0 22px; }
        .ss-check { display: inline-flex; align-items: center; gap: 8px; font-size: 13.5px; color: #3C4655; cursor: pointer; user-select: none; }
        .ss-check input { width: 18px; height: 18px; accent-color: #1C8C6C; cursor: pointer; }

        .ss-btn { width: 100%; height: 52px; border: 0; border-radius: 12px; color: #fff; font-size: 15.5px; font-weight: 600;
            background: linear-gradient(90deg, #1C8C6C 0%, #23A07D 100%); box-shadow: 0 10px 22px rgba(28,140,108,.30);
            display: flex; align-items: center; justify-content: center; gap: 8px; cursor: pointer; transition: transform .12s ease, box-shadow .12s ease, opacity .12s ease; }
        .ss-btn:hover { box-shadow: 0 12px 26px rgba(28,140,108,.38); }
        .ss-btn:active { transform: scale(.99); }
        .ss-btn:disabled { opacity: .75; cursor: progress; }
        .ss-spin { width: 18px; height: 18px; border: 2.5px solid rgba(255,255,255,.4); border-top-color: #fff; border-radius: 50%; animation: ssSpin .8s linear infinite; display: none; }
        .ss-btn.loading .ss-spin { display: inline-block; }
        @keyframes ssSpin { to { transform: rotate(360deg); } }

        @media (max-width: 420px) {
            .ss-login { align-items: flex-start; padding-top: 48px; }
            .ss-login-card { padding: 32px 20px 26px; border-radius: 18px; }
        }
    </style>

    <div class="ss-login">
        {{-- Sign-in form --}}
        <main class="ss-login-card">
                <img src="{{ asset('images/switch-logo-dark.png') }}" alt="Switch Smarter" class="ss-login-logo">

                <h2 class="ss-login-title">Sign in</h2>

                @if ($errors->any())
                    <div class="ss-alert" role="alert">
                        <x-ui-icon name="alert" size="18" stroke="2" />
                        <span>{{ $errors->first() }}</span>
                    </div>
                @endif

                <form method="POST" action="{{ route('login.post') }}" id="loginForm" novalidate>
                    @csrf

                    <div class="ss-field">
                        <label for="email">Email address</label>
                        <div class="ss-input {{ $errors->has('email') ? 'has-error' : '' }}">
                            <x-ui-icon name="mail" size="18" />
                            <input type="email" id="email" name="email" value="{{ old('email') }}" placeholder="you@company.com"
                                autocomplete="username" inputmode="email" required autofocus>
                        </div>
                    </div>

                    <div class="ss-field">
                        <label for="password">Password</label>
                        <div class="ss-input {{ $errors->has('password') ? 'has-error' : '' }}">
                            <x-ui-icon name="lock" size="18" />
                            <input type="password" id="password" name="password" placeholder="Enter your password"
                                autocomplete="current-password" required>
                            <button type="button" class="ss-eye" id="togglePassword" aria-label="Show password">
                                <x-ui-icon name="eye" size="18" class="eye-open" />
                                <x-ui-icon name="eye-off" size="18" class="eye-closed d-none" />
                            </button>
                        </div>
                        @error('password')
                            <div class="ss-error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="ss-row">
                        <label class="ss-check">
                            <input type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }}>
                            Remember me
                        </label>
                    </div>

                    <button type="submit" class="ss-btn" id="loginBtn">
                        <span class="ss-spin"></span>
                        <span class="label">Sign In</span>
                    </button>
                </form>

        </main>
    </div>

    <script>
        // show / hide password
        document.getElementById('togglePassword').addEventListener('click', function() {
            const input = document.getElementById('password');
            const show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            this.querySelector('.eye-open').classList.toggle('d-none', show);
            this.querySelector('.eye-closed').classList.toggle('d-none', !show);
            this.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
        });

        // prevent double submits and show progress
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            if (!this.checkValidity()) {
                e.preventDefault();
                this.reportValidity();
                return;
            }
            const btn = document.getElementById('loginBtn');
            btn.disabled = true;
            btn.classList.add('loading');
            btn.querySelector('.label').textContent = 'Signing in…';
        });
    </script>
@endsection
