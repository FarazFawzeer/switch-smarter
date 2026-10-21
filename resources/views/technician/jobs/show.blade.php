@extends('layouts.technician', ['subtitle' => 'Job Details'])

@section('content')
    @include('layouts.partials.page-title', ['title' => 'My Jobs', 'subtitle' => 'Job Details'])

    <div id="message"></div>

    <div class="card">
        <div class="card-body">
            <h5 class="mb-1">{{ optional($job->contract)->project_name ?? 'Unknown project' }}</h5>
            <p class="text-muted small mb-3">{{ optional($job->contract)->location }}</p>

            <div class="row mb-2">
                <div class="col-6">
                    <p class="text-muted small mb-0">Unit</p>
                    <p class="fw-semibold mb-0">{{ optional($job->unit)->identification_no ?? '—' }}{{ optional($job->unit)->unit_number ? ' (' . $job->unit->unit_number . ')' : '' }}</p>
                </div>
                <div class="col-6">
                    <p class="text-muted small mb-0">Scheduled Date</p>
                    <p class="fw-semibold mb-0">{{ $job->scheduled_date->format('d M Y') }}</p>
                </div>
            </div>

            @if ($job->unit)
                <div class="row">
                    <div class="col-6">
                        <p class="text-muted small mb-0">Type</p>
                        <p class="mb-0">{{ $job->unit->unit_type }}</p>
                    </div>
                    <div class="col-6">
                        <p class="text-muted small mb-0">Brand</p>
                        <p class="mb-0">{{ $job->unit->brand ?? '—' }}</p>
                    </div>
                </div>
            @endif
        </div>
    </div>

    @if (!$job->check_in_time)
    {{-- STEP 1: Not started --}}
    <div class="card">
        <div class="card-body">
            <label class="form-label fw-semibold">Your Current Location</label>
            <input type="text" id="checkInAddress" class="form-control mb-2" readonly
                placeholder="Detecting your location...">
            <p class="text-muted small mb-3">
                <iconify-icon icon="solar:danger-triangle-outline" style="font-size:13px;"></iconify-icon>
                You must be within 200m of where you started this job to complete it.
            </p>

            <button id="startBtn" class="btn btn-primary w-100 py-3 fs-16 fw-bold">
                <iconify-icon icon="solar:play-circle-bold"
                    style="font-size:20px; vertical-align:-4px; margin-right:6px;"></iconify-icon>
                Start Work
            </button>
        </div>
    </div>
    @elseif(!$job->check_out_time)
        {{-- STEP 2: In progress --}}
        <div class="alert alert-info d-flex align-items-center gap-2">
            <iconify-icon icon="solar:clock-circle-outline" style="font-size:20px;"></iconify-icon>
            Started at {{ $job->check_in_time->format('h:i A') }}
        </div>

        <div class="card">
            <div class="card-body">
                <form id="completeForm">
                    <label class="form-label fw-semibold">Completion Photo</label>
                    <p class="text-muted small mb-2">Take a photo directly with your camera — no gallery uploads.</p>
                    <input type="file" name="completion_photo" id="completionPhoto" accept="image/*"
                        capture="environment" class="form-control mb-2" required>
                    <div id="photoPreview" class="mb-3"></div>

                    <label class="form-label fw-semibold">Notes (optional)</label>
                    <textarea name="work_notes" class="form-control mb-3" rows="3" placeholder="Any notes about the work done..."></textarea>

                    <label class="form-label fw-semibold">Your Current Location</label>
                    <input type="text" id="checkOutAddress" class="form-control mb-3" readonly
                        placeholder="Detecting your location...">

                    <button type="submit" id="completeBtn" class="btn btn-success w-100 py-3 fs-16 fw-bold">
                        <iconify-icon icon="solar:check-circle-bold"
                            style="font-size:20px; vertical-align:-4px; margin-right:6px;"></iconify-icon>
                        Complete Job
                    </button>
                </form>
            </div>
        </div>
    @else
        {{-- STEP 3: Completed --}}
        <div class="card">
            <div class="card-body">
                <div class="alert alert-success d-flex align-items-center gap-2 mb-3">
                    <iconify-icon icon="solar:check-circle-bold" style="font-size:20px;"></iconify-icon>
                    Completed at {{ $job->check_out_time->format('h:i A, d M Y') }}
                </div>

                @if ($job->completion_photo)
                    <img src="{{ storage_asset($job->completion_photo) }}" class="img-fluid rounded mb-3"
                        alt="Completion photo">
                @endif

                @if ($job->work_notes)
                    <p class="text-muted small">{{ $job->work_notes }}</p>
                @endif
            </div>
        </div>
    @endif

    <a href="{{ route('technician.jobs.index') }}" class="btn btn-outline-secondary w-100 mt-2">
        <iconify-icon icon="solar:arrow-left-outline"></iconify-icon> Back to My Jobs
    </a>

    @push('scripts')
        <script>
            function getLocation() {
    return new Promise((resolve, reject) => {
        if (!navigator.geolocation) {
            reject('Location is not supported on this device.');
            return;
        }

        let bestReading = null;
        const maxWaitMs = 8000; // give the GPS chip up to 8 seconds to get a solid lock
        const goodEnoughAccuracy = 50; // meters — stop early if we get a reading this precise

        const watchId = navigator.geolocation.watchPosition(
            position => {
                const accuracy = position.coords.accuracy;

                if (!bestReading || accuracy < bestReading.accuracy) {
                    bestReading = {
                        latitude: position.coords.latitude,
                        longitude: position.coords.longitude,
                        accuracy: accuracy,
                    };
                }

                // Stop as soon as we get a genuinely precise reading — no need to wait the full timeout
                if (accuracy <= goodEnoughAccuracy) {
                    navigator.geolocation.clearWatch(watchId);
                    resolve(bestReading);
                }
            },
            error => {
                navigator.geolocation.clearWatch(watchId);
                reject('Could not access your location. Please allow location access and try again.');
            },
            { enableHighAccuracy: true, maximumAge: 0 }
        );

        // Safety timeout — use whatever the best reading was after maxWaitMs, even if imprecise
        setTimeout(() => {
            navigator.geolocation.clearWatch(watchId);
            if (bestReading) {
                resolve(bestReading);
            } else {
                reject('Could not get a location fix. Please make sure location services are enabled and try again.');
            }
        }, maxWaitMs);
    });
}

            // Free reverse geocoding — converts lat/lng into a human-readable address
            async function reverseGeocode(lat, lng) {
                try {
                    const response = await fetch(
                        `https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&zoom=18&addressdetails=1`
                        );
                    const data = await response.json();
                    return data.display_name || `${lat}, ${lng}`;
                } catch (err) {
                    return `${lat}, ${lng}`;
                }
            }

            const messageBox = document.getElementById('message');

            function showError(msg) {
                messageBox.innerHTML = `<div class="alert alert-danger">${msg}</div>`;
            }

            @if (!$job->check_in_time)
                let startLoc = null;

                document.getElementById('startBtn').addEventListener('click', async function() {
                    const btn = this;
                    btn.disabled = true;
                    btn.innerText = 'Getting precise location (this can take a few seconds)...';

                    try {
                        startLoc = await getLocation();
                        const address = await reverseGeocode(startLoc.latitude, startLoc.longitude);
                        document.getElementById('checkInAddress').value = address;

                        btn.innerText = 'Starting...';

                        const formData = new FormData();
                        formData.append('latitude', startLoc.latitude);
                        formData.append('longitude', startLoc.longitude);

                        const response = await fetch("{{ route('technician.jobs.check-in', $job->id) }}", {
                            method: 'POST',
                            body: formData,
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            }
                        });
                        const data = await response.json();

                        if (data.success) {
                            window.location.href = data.redirect;
                        } else {
                            showError(Object.values(data.errors).flat().join('<br>'));
                            btn.disabled = false;
                            btn.innerText = 'Start Work';
                        }
                    } catch (err) {
                        showError(err);
                        btn.disabled = false;
                        btn.innerText = 'Start Work';
                    }
                });
            @endif

            @if ($job->check_in_time && !$job->check_out_time)
                // Detect location as soon as the page loads for the completion screen
                (async function() {
                    try {
                        const loc = await getLocation();
                        const address = await reverseGeocode(loc.latitude, loc.longitude);
                        document.getElementById('checkOutAddress').value = address;
                        document.getElementById('checkOutAddress').dataset.lat = loc.latitude;
                        document.getElementById('checkOutAddress').dataset.lng = loc.longitude;
                    } catch (err) {
                        document.getElementById('checkOutAddress').value = 'Unable to detect location';
                    }
                })();

                document.getElementById('completionPhoto').addEventListener('change', function() {
                    const preview = document.getElementById('photoPreview');
                    if (this.files && this.files[0]) {
                        const reader = new FileReader();
                        reader.onload = e => {
                            preview.innerHTML =
                                `<img src="${e.target.result}" class="img-fluid rounded" style="max-height:200px;">`;
                        };
                        reader.readAsDataURL(this.files[0]);
                    }
                });

                document.getElementById('completeForm').addEventListener('submit', async function(e) {
                    e.preventDefault();
                    const btn = document.getElementById('completeBtn');
                    const addressField = document.getElementById('checkOutAddress');

                    if (!addressField.dataset.lat) {
                        showError('Still detecting your location — please wait a moment and try again.');
                        return;
                    }

                    btn.disabled = true;
                    btn.innerText = 'Submitting...';

                    const formData = new FormData(this);
                    formData.append('latitude', addressField.dataset.lat);
                    formData.append('longitude', addressField.dataset.lng);

                    try {
                        const response = await fetch("{{ route('technician.jobs.complete', $job->id) }}", {
                            method: 'POST',
                            body: formData,
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            }
                        });
                        const data = await response.json();

                        if (data.success) {
                            window.location.href = data.redirect;
                        } else {
                            showError(Object.values(data.errors).flat().join('<br>'));
                            btn.disabled = false;
                            btn.innerHTML =
                                '<iconify-icon icon="solar:check-circle-bold" style="font-size:20px; vertical-align:-4px; margin-right:6px;"></iconify-icon>Complete Job';
                        }
                    } catch (err) {
                        showError('Something went wrong. Please try again.');
                        btn.disabled = false;
                        btn.innerHTML =
                            '<iconify-icon icon="solar:check-circle-bold" style="font-size:20px; vertical-align:-4px; margin-right:6px;"></iconify-icon>Complete Job';
                    }
                });
            @endif
        </script>
    @endpush
@endsection
