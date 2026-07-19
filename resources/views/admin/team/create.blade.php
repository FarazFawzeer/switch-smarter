@extends('layouts.vertical', ['subtitle' => 'Team Create'])

@section('content')
    @include('layouts.partials.page-title', ['title' => 'Team', 'subtitle' => 'Create'])

    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">New Team Member</h5>
        </div>

        <div class="card-body">
            <div id="message"></div>

            <form id="createTeamForm" action="{{ route('admin.team.store') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="name" class="form-label">Full Name</label>
                        <input type="text" id="name" name="name" class="form-control" value="{{ old('name') }}"
                            placeholder="Ex: Kasun Perera" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" id="email" name="email" class="form-control" value="{{ old('email') }}"
                            placeholder="Ex: kasun@company.com" required>
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
                        <label for="type" class="form-label">Role</label>
                        <select id="type" name="type" class="form-select" required>
                            <option value="">Select Role</option>
                            <option value="Manager">Manager</option>
                            <option value="Engineer">Engineer</option>
                            <option value="Supervisor">Supervisor</option>
                            <option value="Technician">Technician</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="image_path" class="form-label">Profile Image</label>
                        <input type="file" id="image_path" name="image_path" class="form-control" accept="image/*">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3" id="engineerField" style="display:none;">
                        <label for="engineer_id" class="form-label">Reports to (Engineer)</label>
                        <select id="engineer_id" name="engineer_id" class="form-select">
                            <option value="">Select Engineer</option>
                            @foreach($engineers as $engineer)
                                <option value="{{ $engineer->id }}">{{ $engineer->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6 mb-3" id="supervisorField" style="display:none;">
                        <label for="supervisor_id" class="form-label">Reports to (Supervisor)</label>
                        <select id="supervisor_id" name="supervisor_id" class="form-select">
                            <option value="">Select Supervisor</option>
                            @foreach($supervisors as $supervisor)
                                <option value="{{ $supervisor->id }}">{{ $supervisor->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary">Create Team Member</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.getElementById('type').addEventListener('change', function() {
            const engineerField = document.getElementById('engineerField');
            const supervisorField = document.getElementById('supervisorField');

            engineerField.style.display = 'none';
            supervisorField.style.display = 'none';
            document.getElementById('engineer_id').value = '';
            document.getElementById('supervisor_id').value = '';

            if (this.value === 'Supervisor') {
                engineerField.style.display = 'block';
            } else if (this.value === 'Technician') {
                supervisorField.style.display = 'block';
            }
        });

        document.getElementById('createTeamForm').addEventListener('submit', function(e) {
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
                        document.getElementById('engineerField').style.display = 'none';
                        document.getElementById('supervisorField').style.display = 'none';
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