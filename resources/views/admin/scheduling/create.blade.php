@extends('layouts.vertical', ['subtitle' => 'Schedule PPM'])

@section('content')
    @include('layouts.partials.page-title', ['title' => 'Scheduling', 'subtitle' => 'Schedule PPM'])

    <div id="message"></div>

    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">{{ $contract->project_name }}</h5>
            <p class="card-subtitle">{{ $contract->location }} — Contract No: {{ $contract->contract_number }}</p>
        </div>
        <div class="card-body">
            <div class="row mb-4">
                <div class="col-md-6">
                    <span class="text-muted small">Contract Start</span>
                    <p class="mb-0">{{ $contract->contract_start_date->format('d M Y') }}</p>
                </div>
                <div class="col-md-6">
                    <span class="text-muted small">Contract End</span>
                    <p class="mb-0">{{ $contract->contract_end_date->format('d M Y') }}</p>
                </div>
            </div>

            <form id="scheduleForm" action="{{ route('admin.scheduling.store', $contract->id) }}" method="POST">
                @csrf
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">PPM Start Date</label>
                        <input type="date" name="ppm_start_date" class="form-control"
                            min="{{ $contract->contract_start_date->format('Y-m-d') }}"
                            max="{{ $contract->contract_end_date->format('Y-m-d') }}" required>
                        <div class="form-text">
                            The first maintenance visit will happen on this date. A visit will then repeat every month on the same day, automatically, until the contract ends.
                        </div>
                    </div>

                    <div class="col-md-6 mb-3">
    <label class="form-label">Default Technician <span class="text-muted">(optional)</span></label>
    <select name="assigned_technician_id" class="form-select">
        <option value="">Assign later, per visit</option>
        @foreach($technicians as $technician)
            <option value="{{ $technician->id }}">{{ $technician->name }}</option>
        @endforeach
    </select>
    <div class="form-text">This technician will be pre-assigned to every monthly visit. You can still change it per visit later.</div>
</div>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('admin.scheduling.index') }}" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Generate Schedule</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.getElementById('scheduleForm').addEventListener('submit', function(e) {
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
                        setTimeout(() => {
                            window.location.href = data.redirect;
                        }, 1000);
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