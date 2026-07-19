@extends('layouts.vertical', ['subtitle' => 'Admin Create'])

@section('content')
    @include('layouts.partials.page-title', ['title' => 'Admin', 'subtitle' => 'Create'])

    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">New User</h5>
        </div>

        <div class="card-body">
            <div id="message"></div>

            <form id="createUserForm" action="{{ route('admin.users.store') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="name" class="form-label">Full Name</label>
                        <input type="text" id="name" name="name" class="form-control" value="{{ old('name') }}"
                            placeholder="Ex: John Doe" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" id="email" name="email" class="form-control" value="{{ old('email') }}"
                            placeholder="Ex: admin@gmail.com" required>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" id="password" name="password" class="form-control" placeholder="Password"
                            required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="password_confirmation" class="form-label">Re-enter Password</label>
                        <input type="password" id="password_confirmation" name="password_confirmation"
                            placeholder="Re-enter Password" class="form-control" required>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="type" class="form-label">User Type</label>
                        <select id="type" name="type" class="form-select" required>
                            <option value="">Select Type</option>
                            <option value="Super Admin" {{ old('type') == 'Super Admin' ? 'selected' : '' }}>Super Admin</option>
                            <option value="Admin" {{ old('type') == 'Admin' ? 'selected' : '' }}>Admin</option>

                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="image_path" class="form-label">Profile Image</label>
                        <input type="file" id="image_path" name="image_path" class="form-control" accept="image/*">
                    </div>
                </div>

                <div class="d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary">Create User</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.getElementById('createUserForm').addEventListener('submit', function(e) {
            e.preventDefault();
            let form = this;
            let formData = new FormData(form);

            fetch(form.action, {
                    method: "POST",
                    body: formData,
                    headers: {
                        "X-CSRF-TOKEN": document.querySelector('input[name="_token"]').value
                    }
                })
                .then(response => response.json())
                .then(data => {
                    let messageBox = document.getElementById('message');
                    if (data.success) {
                        messageBox.innerHTML = `<div class="alert alert-success">${data.message}</div>`;
                        form.reset();
                        setTimeout(() => { messageBox.innerHTML = ""; }, 3000);
                    } else {
                        let errors = Object.values(data.errors).flat().join('<br>');
                        messageBox.innerHTML = `<div class="alert alert-danger">${errors}</div>`;
                    }
                })
                .catch(error => {
                    document.getElementById('message').innerHTML = `<div class="alert alert-danger">Error: ${error}</div>`;
                });
        });
    </script>
@endsection