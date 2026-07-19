@extends('layouts.vertical', ['subtitle' => 'Site Edit'])

@section('content')
    @include('layouts.partials.page-title', ['title' => 'Sites', 'subtitle' => 'Edit'])

    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Edit Site</h5>
        </div>

        <div class="card-body">
            <div id="message"></div>

            <form id="editSiteForm" action="{{ route('admin.sites.update', $site->id) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="contract_id" class="form-label">Contract</label>
                        <select id="contract_id" name="contract_id" class="form-select" required>
                            @foreach($contracts as $contract)
                                <option value="{{ $contract->id }}" {{ $site->contract_id == $contract->id ? 'selected' : '' }}>
                                    {{ $contract->project_name }} — {{ $contract->client_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="site_name" class="form-label">Site Name</label>
                        <input type="text" id="site_name" name="site_name" class="form-control"
                            value="{{ $site->site_name }}" required>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12 mb-3">
                        <label for="address" class="form-label">Address (optional)</label>
                        <input type="text" id="address" name="address" class="form-control"
                            value="{{ $site->address }}">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="latitude" class="form-label">Latitude</label>
                        <input type="text" id="latitude" name="latitude" class="form-control"
                            value="{{ $site->latitude }}" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="longitude" class="form-label">Longitude</label>
                        <input type="text" id="longitude" name="longitude" class="form-control"
                            value="{{ $site->longitude }}" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="radius_meters" class="form-label">Check-in Radius (meters)</label>
                        <input type="number" id="radius_meters" name="radius_meters" class="form-control"
                            value="{{ $site->radius_meters }}" min="50" max="5000" required>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="elevator_count" class="form-label">Number of Elevators</label>
                        <input type="number" id="elevator_count" name="elevator_count" class="form-control"
                            value="{{ $site->elevator_count }}" min="1" max="50" required>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('admin.sites.index') }}" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Update Site</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.getElementById('editSiteForm').addEventListener('submit', function(e) {
            e.preventDefault();
            let form = this;
            let formData = new FormData(form);
            formData.append('_method', 'PUT');

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
                            window.location.href = "{{ route('admin.sites.index') }}";
                        }, 1200);
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