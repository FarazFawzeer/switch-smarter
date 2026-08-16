@extends('layouts.technician', ['subtitle' => 'Profile'])

@section('content')
    @include('layouts.partials.page-title', ['title' => 'Profile', 'subtitle' => 'My Details'])

    <div class="card">
        <div class="card-body text-center">
            <img src="{{ $user->image_path ? asset($user->image_path) : asset('images/users/avatar-17.jpg') }}"
                class="rounded-circle mb-3" style="width:88px; height:88px; object-fit:cover;">
            <h5 class="mb-1">{{ $user->name }}</h5>
            <span class="badge" style="background:#2E5AAC;">Technician</span>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-6">
                    <p class="text-muted small mb-0">Employee ID</p>
                    <p class="fw-semibold mb-0">{{ $user->employee_id ?? '—' }}</p>
                </div>
                <div class="col-6">
                    <p class="text-muted small mb-0">Contact No</p>
                    <p class="fw-semibold mb-0">{{ $user->contact_no ?? '—' }}</p>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-6">
                    <p class="text-muted small mb-0">Email</p>
                    <p class="fw-semibold mb-0">{{ $user->email }}</p>
                </div>
                <div class="col-6">
                    <p class="text-muted small mb-0">Shift</p>
                    <p class="fw-semibold mb-0">{{ optional($user->shift)->name ?? '—' }}</p>
                </div>
            </div>
            <div class="row">
                <div class="col-12">
                    <p class="text-muted small mb-0">Supervisor</p>
                    <p class="fw-semibold mb-0">{{ optional($user->supervisor)->name ?? '—' }}</p>
                </div>
            </div>
        </div>
    </div>

    <form action="{{ route('logout') }}" method="POST">
        @csrf
        <button type="submit" class="btn btn-outline-danger w-100">
            <iconify-icon icon="solar:logout-3-outline"></iconify-icon> Logout
        </button>
    </form>
@endsection