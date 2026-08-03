@extends('layouts.vertical', ['subtitle' => 'Job Detail'])

@section('content')
    @include('layouts.partials.page-title', ['title' => 'Tracking', 'subtitle' => 'Job Detail'])

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">{{ optional($job->contract)->project_name ?? 'Unknown project' }}</h5>
                    <span class="badge" style="background:{{ $job->status === 'completed' ? '#2E9E5B' : '#17A2B8' }}; color:#fff;">
                        {{ ucfirst(str_replace('_',' ', $job->status)) }}
                    </span>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <p class="text-muted small mb-0">Unit</p>
                            <p class="fw-semibold mb-0">{{ optional($job->unit)->identification_no ?? '—' }}</p>
                        </div>
                        <div class="col-md-6">
                            <p class="text-muted small mb-0">Scheduled Date</p>
                            <p class="fw-semibold mb-0">{{ $job->scheduled_date->format('d M Y') }}</p>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <p class="text-muted small mb-0">Technician</p>
                            <p class="fw-semibold mb-0">{{ optional($job->technician)->name ?? '—' }}</p>
                        </div>
                        <div class="col-md-6">
                            <p class="text-muted small mb-0">Assigned By</p>
                            <p class="fw-semibold mb-0">{{ optional($job->assignedBy)->name ?? 'System' }}</p>
                        </div>
                    </div>

                    <hr>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <p class="text-muted small mb-1">Check-in</p>
                            @if($job->check_in_time)
                                <p class="mb-1">{{ $job->check_in_time->format('h:i A, d M Y') }}</p>
                                @if($job->check_in_lat)
                                    <p class="small geocode-address mb-0" data-lat="{{ $job->check_in_lat }}" data-lng="{{ $job->check_in_lng }}">
                                        <iconify-icon icon="solar:map-point-outline"></iconify-icon> Loading location...
                                    </p>
                                @endif
                            @else
                                <p class="text-muted mb-0">Not started yet</p>
                            @endif
                        </div>
                        <div class="col-md-6">
                            <p class="text-muted small mb-1">Check-out</p>
                            @if($job->check_out_time)
                                <p class="mb-1">{{ $job->check_out_time->format('h:i A, d M Y') }}</p>
                                @if($job->check_out_lat)
                                    <p class="small geocode-address mb-0" data-lat="{{ $job->check_out_lat }}" data-lng="{{ $job->check_out_lng }}">
                                        <iconify-icon icon="solar:map-point-outline"></iconify-icon> Loading location...
                                    </p>
                                @endif
                            @else
                                <p class="text-muted mb-0">Not completed yet</p>
                            @endif
                        </div>
                    </div>

                    @if($job->check_in_time && $job->check_out_time)
                        <div class="alert alert-light d-flex align-items-center gap-2 mb-3">
                            <iconify-icon icon="solar:clock-circle-outline"></iconify-icon>
                            Duration: {{ $job->check_in_time->diffForHumans($job->check_out_time, true) }}
                        </div>
                    @endif

                    @if($job->work_notes)
                        <p class="text-muted small mb-1">Work Notes</p>
                        <p class="mb-0">{{ $job->work_notes }}</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header"><h6 class="card-title mb-0">Completion Photo</h6></div>
                <div class="card-body">
                    @if($job->completion_photo)
                        <img src="{{ storage_asset($job->completion_photo) }}" class="img-fluid rounded" alt="Completion photo">
                    @else
                        <p class="text-muted small mb-0">No photo uploaded yet.</p>
                    @endif
                </div>
            </div>

            <a href="{{ route('admin.tracking.index') }}" class="btn btn-secondary w-100">Back to Tracking</a>
        </div>
    </div>

    @push('scripts')
    <script>
        async function reverseGeocode(lat, lng) {
            try {
                const response = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&zoom=16&addressdetails=1`);
                const data = await response.json();
                return data.display_name || `${lat}, ${lng}`;
            } catch (err) {
                return `${lat}, ${lng}`;
            }
        }

        document.querySelectorAll('.geocode-address').forEach(async (el) => {
            const lat = el.dataset.lat;
            const lng = el.dataset.lng;
            const address = await reverseGeocode(lat, lng);

            el.innerHTML = `<iconify-icon icon="solar:map-point-outline"></iconify-icon> <a href="https://www.google.com/maps?q=${lat},${lng}" target="_blank">${address}</a>`;
        });
    </script>
    @endpush
@endsection