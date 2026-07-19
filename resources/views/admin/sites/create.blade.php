@extends('layouts.vertical', ['subtitle' => 'Site Create'])

@section('content')
    @include('layouts.partials.page-title', ['title' => 'Sites', 'subtitle' => 'Create'])

    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">New Site</h5>
        </div>

        <div class="card-body">
            <div id="message"></div>

            <form id="createSiteForm" action="{{ route('admin.sites.store') }}" method="POST">
                @csrf

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="contract_id" class="form-label">Contract</label>
                        <select id="contract_id" name="contract_id" class="form-select" required>
                            <option value="">Select Contract</option>
                            @foreach($contracts as $contract)
                                <option value="{{ $contract->id }}">{{ $contract->project_name }} — {{ $contract->client_name }}</option>
                            @endforeach
                        </select>
                        <div class="form-text">Only active contracts are shown.</div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="site_name" class="form-label">Site Name</label>
                        <input type="text" id="site_name" name="site_name" class="form-control"
                            placeholder="Ex: Liberty Plaza — Elevator A" required>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12 mb-3">
                        <label for="address" class="form-label">Address (optional)</label>
                        <input type="text" id="address" name="address" class="form-control"
                            placeholder="Ex: 282 R.A. De Mel Mawatha, Colombo 03">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="latitude" class="form-label">Latitude</label>
                        <input type="text" id="latitude" name="latitude" class="form-control"
                            placeholder="Ex: 6.9147" required>
                        <div class="form-text">Right-click the location on Google Maps to copy coordinates.</div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="longitude" class="form-label">Longitude</label>
                        <input type="text" id="longitude" name="longitude" class="form-control"
                            placeholder="Ex: 79.8489" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="radius_meters" class="form-label">Check-in Radius (meters)</label>
                        <input type="number" id="radius_meters" name="radius_meters" class="form-control"
                            value="1000" min="50" max="5000" required>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="elevator_count" class="form-label">Number of Elevators</label>
                        <input type="number" id="elevator_count" name="elevator_count" class="form-control"
                            value="1" min="1" max="50" required>
                    </div>
                </div>

                <div class="d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary">Create Site</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.getElementById('createSiteForm').addEventListener('submit', function(e) {
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
                        document.getElementById('radius_meters').value = 1000;
                        document.getElementById('elevator_count').value = 1;
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