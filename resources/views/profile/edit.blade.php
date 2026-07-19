@extends('layouts.vertical', ['subtitle' => 'User Profile'])

@section('content')
    @include('layouts.partials.page-title', ['title' => 'User', 'subtitle' => 'Profile'])


    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title">My Profile</h5>
            <button id="editProfileBtn" class="btn btn-sm btn-outline-primary">Edit Profile</button>
        </div>

        <div class="card-body">
            {{-- Success message --}}
            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            {{-- VIEW MODE --}}
            <div id="profileView">
                <div class="d-flex align-items-center mb-3">
                    <img src="{{ $user->image_path ? asset($user->image_path) : asset('images/users/avatar-6.jpg') }}"
                        alt="Profile Image" class="rounded-circle me-3" style="width:80px;height:80px;object-fit:cover;">
                    <div>
                        <h6 class="mb-1">Name: {{ $user->name }}</h6>
                        <p class="mb-0 text-muted">Email: {{ $user->email }}</p>
                        <small class="text-secondary">Role: {{ ucfirst($user->type) }}</small>
                    </div>
                </div>
                <p><strong>Joined:</strong> {{ $user->created_at->format('d M Y') }}</p>
            </div>

            {{-- EDIT MODE (hidden initially) --}}
            <div id="profileEdit" style="display: none;">
                <form action="{{ route('admin.profile.update') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <div class="mb-3">
                        <label for="name" class="form-label">Name</label>
                        <input type="text" name="name" value="{{ old('name', $user->name) }}"
                            class="form-control @error('name') is-invalid @enderror">
                        @error('name')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" name="email" value="{{ old('email', $user->email) }}"
                            class="form-control @error('email') is-invalid @enderror">
                        @error('email')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">New Password (optional)</label>
                        <input type="password" name="password" class="form-control @error('password') is-invalid @enderror">
                        @error('password')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="password_confirmation" class="form-label">Confirm New Password</label>
                        <input type="password" name="password_confirmation" class="form-control">
                    </div>

                    <div class="mb-3">
                        <label for="image_path" class="form-label">Profile Picture</label>
                        <input type="file" name="image_path"
                            class="form-control @error('image_path') is-invalid @enderror">
                        @error('image_path')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    @if ($user->image_path)
                        <div class="mb-3">
                            <img src="{{ asset($user->image_path) }}" alt="Profile Image" class="rounded"
                                style="width:100px;height:100px;object-fit:cover;">
                        </div>
                    @endif

                    <button type="submit" class="btn btn-primary">Save Changes</button>
                    <button type="button" id="cancelEditBtn" class="btn btn-secondary">Cancel</button>
                </form>
            </div>
        </div>
    </div>

    {{-- Script to toggle view/edit --}}
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const editBtn = document.getElementById("editProfileBtn");
            const cancelBtn = document.getElementById("cancelEditBtn");
            const viewSection = document.getElementById("profileView");
            const editSection = document.getElementById("profileEdit");

            editBtn.addEventListener("click", function() {
                viewSection.style.display = "none";
                editSection.style.display = "block";
                editBtn.style.display = "none"; // hide edit button
            });

            cancelBtn.addEventListener("click", function() {
                viewSection.style.display = "block";
                editSection.style.display = "none";
                editBtn.style.display = "inline-block";
            });
        });

        const successAlert = document.querySelector('.alert-success');
        if (successAlert) {
            setTimeout(() => {
                successAlert.style.transition = "opacity 0.5s ease";
                successAlert.style.opacity = "0";
                setTimeout(() => successAlert.remove(), 500); // remove after fade
            }, 3000); // 3 seconds
        }
    </script>
@endsection
