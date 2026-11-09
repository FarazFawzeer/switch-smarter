@extends('layouts.vertical', ['subtitle' => 'Shutdown Units'])

@section('content')
    @include('layouts.partials.page-title', ['title' => 'Breakdown', 'subtitle' => 'Shutdown Units'])

    <style>
        .sd-card { border: none; border-radius: 14px; box-shadow: 0 1px 3px rgba(15,42,67,0.06); }
        .sd-hero { background: linear-gradient(135deg, #B23030, #D64545); color: #fff; border-radius: 14px; padding: 18px 20px; display: flex; align-items: center; gap: 16px; margin-bottom: 16px; }
        .sd-hero .num { font-size: 34px; font-weight: 800; line-height: 1; }
        .sd-hero iconify-icon { font-size: 40px; opacity: .9; }
        .sd-table { border-collapse: separate; border-spacing: 0; width: 100%; font-size: 13px; }
        .sd-table thead th { background: #F5F7FA; color: #56606f; font-weight: 600; padding: 10px 12px; border-bottom: 1px solid #e7e9ee; white-space: nowrap; }
        .sd-table tbody td { padding: 11px 12px; border-bottom: 1px solid #f0f1f4; vertical-align: middle; }
        .sd-table tbody tr { border-left: 3px solid #D64545; }
        .sd-days { display: inline-block; padding: 3px 10px; border-radius: 6px; font-weight: 700; font-size: 12px; }
        .sd-days.critical { background: #D64545; color: #fff; }
        .sd-days.warn { background: rgba(240,162,2,0.15); color: #B57900; }
        .sd-days.new { background: rgba(214,69,69,0.10); color: #B23030; }
    </style>

    <div class="sd-hero">
        <iconify-icon icon="solar:danger-triangle-bold"></iconify-icon>
        <div>
            <div class="num">{{ $units->count() }}</div>
            <div class="fw-semibold">Elevator{{ $units->count() === 1 ? '' : 's' }} currently shut down — priority</div>
            <div class="small" style="opacity:.85;">Longest shutdown first. A unit returns to Working when a technician closes a breakdown/repair as "Working", or with "Mark Working" below.</div>
        </div>
    </div>

    <form method="GET" action="{{ route('admin.shutdown-units.index') }}" id="filterForm"></form>
    <x-active-filters :filters="[
        'search' => 'Search',
        'unit' => 'Unit ID',
        'unit_no' => 'Unit No',
        'project' => 'Project',
        'route_id' => ['Route', $routeOptions],
        'engineer_id' => ['Engineer', $engineerOptions],
        'reason' => 'Reason',
    ]" />

    <div class="card sd-card">
        <div class="table-responsive">
            <table class="sd-table">
                <thead>
                    <tr>
                        <th><x-th-filter label="Unit ID" name="unit" /></th>
                        <th><x-th-filter label="Unit No" name="unit_no" /></th>
                        <th><x-th-filter label="Project / Location" name="project" /></th>
                        <th><x-th-filter label="Route" name="route_id" type="select" :options="$routeOptions" /></th>
                        <th><x-th-filter label="Engineer" name="engineer_id" type="select" :options="$engineerOptions" /></th>
                        <th>Shutdown Since</th>
                        <th>Down For</th>
                        <th><x-th-filter label="Reason" name="reason" /></th>
                        <th>Reported In</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($units as $unit)
                        @php
                            $days = $unit->shutdown_since ? (int) $unit->shutdown_since->copy()->startOfDay()->diffInDays(now()->startOfDay()) : null;
                            $dayClass = $days === null ? 'new' : ($days >= 7 ? 'critical' : ($days >= 2 ? 'warn' : 'new'));
                        @endphp
                        <tr>
                            <td class="fw-bold">{{ $unit->identification_no }}</td>
                            <td>{{ $unit->unit_number ?? '—' }}</td>
                            <td>
                                <div class="fw-semibold">{{ optional($unit->contract)->project_name ?? '—' }}</div>
                                <div class="text-muted" style="font-size:12px;">{{ optional($unit->contract)->location }}</div>
                            </td>
                            <td>{{ optional(optional($unit->contract)->route)->route_no ?? '—' }}</td>
                            <td>{{ optional(optional($unit->contract)->engineer)->name ?? '—' }}</td>
                            <td class="text-nowrap">{{ $unit->shutdown_since?->format('d M Y, h:i A') ?? '—' }}</td>
                            <td>
                                <span class="sd-days {{ $dayClass }}">
                                    {{ $days === null ? '—' : ($days === 0 ? 'Today' : $days . ' day' . ($days === 1 ? '' : 's')) }}
                                </span>
                            </td>
                            <td style="max-width: 260px;">{{ $unit->shutdown_reason ?? '—' }}</td>
                            <td>
                                @if($unit->shutdownJob)
                                    <a href="{{ route('admin.job-types.show', $unit->shutdownJob->id) }}" class="fw-semibold">
                                        {{ $unit->shutdownJob->job_code ?? 'View job' }}
                                    </a>
                                    <div class="text-muted" style="font-size:11px;">{{ optional($unit->shutdownJob->technician)->name }}</div>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-outline-success mark-working-btn"
                                    data-url="{{ route('admin.shutdown-units.mark-working', $unit->id) }}"
                                    data-unit="{{ $unit->identification_no }}">Mark Working</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center text-muted py-5">
                                <iconify-icon icon="solar:check-circle-outline" style="font-size: 36px; color:#2E9E5B;"></iconify-icon>
                                <p class="mb-0 mt-2">No elevators are shut down — everything is running.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <script>
        document.querySelectorAll('.mark-working-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const url = this.dataset.url;
                Swal.fire({
                    title: `Mark ${this.dataset.unit} as working?`,
                    text: 'Use this when the elevator is running again. It will be removed from the shutdown list.',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#2E9E5B',
                    confirmButtonText: 'Yes, it is working'
                }).then(result => {
                    if (!result.isConfirmed) return;
                    fetch(url, { method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' } })
                        .then(r => r.json())
                        .then(data => Swal.fire(data.success ? 'Done' : 'Not changed', data.message, data.success ? 'success' : 'error')
                            .then(() => location.reload()));
                });
            });
        });
    </script>
@endsection
