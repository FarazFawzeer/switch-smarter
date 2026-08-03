@extends('layouts.vertical', ['subtitle' => 'Job Tracking'])

@section('content')
    @include('layouts.partials.page-title', ['title' => 'Tracking', 'subtitle' => 'Job Tracking'])

    <style>
        .tr-card { border: none; border-radius: 14px; box-shadow: 0 1px 3px rgba(15,42,67,0.06); }
        .tr-table { border-collapse: separate; border-spacing: 0; width: 100%; font-size: 13px; }
        .tr-table thead th {
            background: #F5F7FA; color: #56606f; font-weight: 600; padding: 10px 12px;
            border-bottom: 1px solid #e7e9ee; white-space: nowrap;
        }
        .tr-table tbody td { padding: 10px 12px; border-bottom: 1px solid #f0f1f4; vertical-align: middle; }
        .tr-table tbody tr:hover { background: #FAFBFD; }
        .tr-pill { display: inline-block; padding: 3px 10px; border-radius: 6px; font-size: 11px; font-weight: 700; color: #fff; }
        .tr-location-link { font-size: 12px; color: #2E5AAC; text-decoration: none; }
        .tr-location-link:hover { text-decoration: underline; }
    </style>

    <form method="GET" action="{{ route('admin.tracking.index') }}" id="filterForm" class="row g-2 mb-3 justify-content-end align-items-end">
        <div class="col-auto">
            <input type="text" name="search" class="form-control" style="width: 200px;"
                placeholder="Search project or unit..." value="{{ request('search') }}">
        </div>
        <div class="col-auto">
            <select name="technician_id" class="form-select filter-auto" style="width: 170px;">
                <option value="">All Technicians</option>
                @foreach($technicians as $tech)
                    <option value="{{ $tech->id }}" {{ request('technician_id') == $tech->id ? 'selected' : '' }}>{{ $tech->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-auto">
            <select name="status" class="form-select filter-auto" style="width: 150px;">
                <option value="">All Statuses</option>
                <option value="in_progress" {{ request('status') == 'in_progress' ? 'selected' : '' }}>In Progress</option>
                <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
            </select>
        </div>
        <div class="col-auto">
            <input type="date" name="from" class="form-control form-control-sm" value="{{ request('from') }}" title="From date">
        </div>
        <div class="col-auto">
            <input type="date" name="to" class="form-control form-control-sm" value="{{ request('to') }}" title="To date">
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-primary btn-sm">Apply</button>
        </div>
        @if(request()->hasAny(['search', 'technician_id', 'status', 'from', 'to']))
            <div class="col-auto">
                <a href="{{ route('admin.tracking.index') }}" class="btn btn-outline-secondary btn-sm" title="Clear filters">
                    <iconify-icon icon="solar:close-circle-outline"></iconify-icon>
                </a>
            </div>
        @endif
    </form>

    <div class="card tr-card">
        <div class="table-responsive">
            <table class="tr-table">
                <thead>
                    <tr>
                        <th>Technician</th>
                        <th>Project / Unit</th>
                        <th>Scheduled</th>
                        <th>Check-in</th>
                        <th>Check-out</th>
                        <th>Status</th>
                        <th class="text-center">Details</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($jobs as $job)
                        @php
                            $statusColor = $job->status === 'completed' ? '#2E9E5B' : '#17A2B8';
                        @endphp
                        <tr>
                            <td>{{ optional($job->technician)->name ?? '—' }}</td>
                            <td>
                                <div class="fw-semibold">{{ optional($job->contract)->project_name ?? '—' }}</div>
                                <div class="text-muted" style="font-size:12px;">{{ optional($job->unit)->identification_no ?? '—' }}</div>
                            </td>
                            <td>{{ $job->scheduled_date->format('d M Y') }}</td>
                           <td>
    @if($job->check_in_time)
        {{ $job->check_in_time->format('h:i A') }}
        @if($job->check_in_lat && $job->check_in_lng)
            <br>
            <span class="tr-location-link geocode-address" data-lat="{{ $job->check_in_lat }}" data-lng="{{ $job->check_in_lng }}">
                <iconify-icon icon="solar:map-point-outline" style="font-size:12px;"></iconify-icon> Loading location...
            </span>
        @endif
    @else
        <span class="text-muted">—</span>
    @endif
</td>
<td>
    @if($job->check_out_time)
        {{ $job->check_out_time->format('h:i A') }}
        @if($job->check_out_lat && $job->check_out_lng)
            <br>
            <span class="tr-location-link geocode-address" data-lat="{{ $job->check_out_lat }}" data-lng="{{ $job->check_out_lng }}">
                <iconify-icon icon="solar:map-point-outline" style="font-size:12px;"></iconify-icon> Loading location...
            </span>
        @endif
    @else
        <span class="text-muted">Not yet</span>
    @endif
</td>
                            <td><span class="tr-pill" style="background:{{ $statusColor }};">{{ ucfirst(str_replace('_',' ', $job->status)) }}</span></td>
                            <td class="text-center">
                                <a href="{{ route('admin.tracking.show', $job->id) }}" class="btn btn-sm btn-outline-primary">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">
                                <iconify-icon icon="solar:map-point-outline" style="font-size: 32px; opacity: 0.4;"></iconify-icon>
                                <p class="mb-0 mt-2">No job activity found for these filters.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="d-flex justify-content-end mt-2">
        {{ $jobs->links() }}
    </div>

    <script>
        document.querySelectorAll('.filter-auto').forEach(select => {
            select.addEventListener('change', function() {
                document.getElementById('filterForm').submit();
            });
        });

        
    </script>

    <script>
    document.querySelectorAll('.filter-auto').forEach(select => {
        select.addEventListener('change', function() {
            document.getElementById('filterForm').submit();
        });
    });

    // Reverse-geocode every location pin on the page into a readable address
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

        el.innerHTML = `<iconify-icon icon="solar:map-point-outline" style="font-size:12px;"></iconify-icon> <a href="https://www.google.com/maps?q=${lat},${lng}" target="_blank" class="tr-location-link">${address}</a>`;
    });
</script>
@endsection