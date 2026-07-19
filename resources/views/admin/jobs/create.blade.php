@extends('layouts.vertical', ['subtitle' => 'Job Create'])

@section('content')
    @include('layouts.partials.page-title', ['title' => 'Jobs', 'subtitle' => 'Create'])

    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">New Job</h5>
        </div>

        <div class="card-body">
            <div id="message"></div>

            <form id="createJobForm" action="{{ route('admin.jobs.store') }}" method="POST">
                @csrf

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="job_type" class="form-label">Job Type</label>
                        <select id="job_type" name="job_type" class="form-select" required>
                            <option value="">Select Type</option>
                            <option value="ppm">PPM (Scheduled Maintenance)</option>
                            <option value="repair">Repair</option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="site_id" class="form-label">Site</label>
                        <select id="site_id" name="site_id" class="form-select" required>
                            <option value="">Select Site</option>
                            @foreach($sites as $site)
                                <option value="{{ $site->id }}">
                                    {{ $site->site_name }} ({{ optional($site->contract)->project_name ?? 'No contract' }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="scheduled_date" class="form-label">Scheduled Date</label>
                        <input type="date" id="scheduled_date" name="scheduled_date" class="form-control" required>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="assigned_technician_id" class="form-label">Assign Technician (optional)</label>
                        <select id="assigned_technician_id" name="assigned_technician_id" class="form-select">
                            <option value="">Assign later</option>
                            @foreach($technicians as $technician)
                                <option value="{{ $technician->id }}">{{ $technician->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 mb-3" id="priorityField" style="display:none;">
                        <label for="priority" class="form-label">Priority</label>
                        <select id="priority" name="priority" class="form-select">
                            <option value="low">Low</option>
                            <option value="medium" selected>Medium</option>
                            <option value="high">High</option>
                            <option value="critical">Critical</option>
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12 mb-3">
                        <label for="description" class="form-label">Description / Notes (optional)</label>
                        <textarea id="description" name="description" class="form-control" rows="3"
                            placeholder="Ex: Elevator making unusual noise on 3rd floor, requested by building management"></textarea>
                    </div>
                </div>

                <div class="d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary">Create Job</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Priority is only meaningful for repair jobs, not routine PPM visits
        document.getElementById('job_type').addEventListener('change', function() {
            const priorityField = document.getElementById('priorityField');
            priorityField.style.display = this.value === 'repair' ? 'block' : 'none';
        });

        document.getElementById('createJobForm').addEventListener('submit', function(e) {
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
                        document.getElementById('priorityField').style.display = 'none';
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