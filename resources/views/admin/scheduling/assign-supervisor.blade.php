@extends('layouts.vertical', ['subtitle' => 'Assign Supervisor'])

@section('content')
    @include('layouts.partials.page-title', ['title' => 'Scheduling', 'subtitle' => 'Assign Supervisor'])

    <div id="message"></div>

    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">{{ $contract->project_name }}</h5>
            <p class="card-subtitle">{{ $contract->location }} — Contract No: {{ $contract->contract_number }}</p>
        </div>
        <div class="card-body">
            <form id="assignSupervisorForm" action="{{ route('admin.scheduling.assign-supervisor.store', $contract->id) }}" method="POST">
                @csrf
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Select Supervisor</label>
                        <select name="assigned_supervisor_id" class="form-select" required>
                            <option value="">Choose from your team</option>
                            @foreach($supervisors as $supervisor)
                                <option value="{{ $supervisor->id }}">{{ $supervisor->name }}</option>
                            @endforeach
                        </select>
                        <div class="form-text">This supervisor will handle PPM scheduling and technician assignment for this contract.</div>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('admin.scheduling.index') }}" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Assign Contract</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.getElementById('assignSupervisorForm').addEventListener('submit', function(e) {
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
                        setTimeout(() => { window.location.href = data.redirect; }, 1000);
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