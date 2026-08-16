@extends('layouts.vertical', ['subtitle' => 'Attendance'])

@section('content')
    @include('layouts.partials.page-title', ['title' => 'Attendance', 'subtitle' => 'Technician Attendance'])

    <style>
        .at-card { border: none; border-radius: 12px; box-shadow: 0 1px 3px rgba(15,42,67,0.06); }
        .at-table { border-collapse: separate; border-spacing: 0; width: 100%; font-size: 13px; }
        .at-table thead th {
            background: #F5F7FA; color: #56606f; font-weight: 600; padding: 10px 12px;
            border-bottom: 1px solid #e7e9ee; white-space: nowrap;
        }
        .at-table tbody td { padding: 10px 12px; border-bottom: 1px solid #f0f1f4; vertical-align: middle; }
        .at-table tbody tr:hover { background: #FAFBFD; }
        .ot-badge { background: rgba(214,69,69,0.10); color: #D64545; font-weight: 700; font-size: 11px; padding: 2px 8px; border-radius: 5px; }
    </style>

    {{-- Summary stats --}}
    <div class="row g-3 mb-3">
        <div class="col-6 col-md-4">
            <div class="card at-card">
                <div class="card-body d-flex align-items-center gap-2">
                    <div style="width:44px; height:44px; border-radius:10px; background:rgba(46,90,172,0.10); color:#2E5AAC; display:flex; align-items:center; justify-content:center; font-size:22px;">
                        <iconify-icon icon="solar:calendar-outline"></iconify-icon>
                    </div>
                    <div>
                        <div class="fw-bold" style="font-size:20px; color:#16233b;">{{ $totalDays }}</div>
                        <div class="small text-muted">Attendance Records</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4">
            <div class="card at-card">
                <div class="card-body d-flex align-items-center gap-2">
                    <div style="width:44px; height:44px; border-radius:10px; background:rgba(46,158,91,0.10); color:#2E9E5B; display:flex; align-items:center; justify-content:center; font-size:22px;">
                        <iconify-icon icon="solar:clock-circle-outline"></iconify-icon>
                    </div>
                    <div>
                        <div class="fw-bold" style="font-size:20px; color:#16233b;">{{ number_format($totalHours, 1) }}</div>
                        <div class="small text-muted">Total Hours</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4">
            <div class="card at-card">
                <div class="card-body d-flex align-items-center gap-2">
                    <div style="width:44px; height:44px; border-radius:10px; background:rgba(214,69,69,0.10); color:#D64545; display:flex; align-items:center; justify-content:center; font-size:22px;">
                        <iconify-icon icon="solar:danger-triangle-outline"></iconify-icon>
                    </div>
                    <div>
                        <div class="fw-bold" style="font-size:20px; color:#16233b;">{{ number_format($totalOt, 1) }}</div>
                        <div class="small text-muted">OT Hours</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <form method="GET" action="{{ route('admin.attendance.index') }}" id="filterForm" class="row g-2 mb-3 justify-content-end align-items-end">
        <div class="col-auto">
            <select name="technician_id" class="form-select filter-auto" style="width: 180px;">
                <option value="">All Technicians</option>
                @foreach($technicians as $tech)
                    <option value="{{ $tech->id }}" {{ request('technician_id') == $tech->id ? 'selected' : '' }}>
                        {{ $tech->name }} @if($tech->employee_id) ({{ $tech->employee_id }}) @endif
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-auto">
            <select name="month" class="form-select filter-auto" style="width: 140px;">
                <option value="">All Months</option>
                @foreach(['January','February','March','April','May','June','July','August','September','October','November','December'] as $i => $name)
                    <option value="{{ $i + 1 }}" {{ request('month') == $i + 1 ? 'selected' : '' }}>{{ $name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-auto">
            <select name="year" class="form-select filter-auto" style="width: 110px;">
                <option value="">All Years</option>
                @for($y = now()->year; $y >= now()->year - 3; $y--)
                    <option value="{{ $y }}" {{ request('year') == $y ? 'selected' : '' }}>{{ $y }}</option>
                @endfor
            </select>
        </div>
        @if(request()->hasAny(['technician_id', 'month', 'year', 'from', 'to']))
            <div class="col-auto">
                <a href="{{ route('admin.attendance.index') }}" class="btn btn-outline-secondary btn-sm" title="Clear filters">
                    <iconify-icon icon="solar:close-circle-outline"></iconify-icon>
                </a>
            </div>
        @endif
    </form>

    <div class="card at-card">
        <div class="table-responsive">
            <table class="at-table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Technician</th>
                        <th>Date</th>
                        <th>Shift</th>
                        <th>Check-in</th>
                        <th>Check-out</th>
                        <th>Total Hours</th>
                        <th>OT Hours</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($records as $record)
                        <tr>
                            <td>{{ $records->firstItem() + $loop->index }}</td>
                            <td>
                                {{ optional($record->technician)->name ?? '—' }}
                                @if(optional($record->technician)->employee_id)
                                    <div class="text-muted" style="font-size:12px;">{{ $record->technician->employee_id }}</div>
                                @endif
                            </td>
                            <td>{{ $record->work_date->format('d M Y') }}</td>
                            <td>{{ optional($record->shift)->name ?? '—' }}</td>
                            <td>{{ $record->check_in_time?->format('h:i A') ?? '—' }}</td>
                            <td>{{ $record->check_out_time?->format('h:i A') ?? '—' }}</td>
                            <td class="fw-semibold">{{ $record->total_hours ?? '—' }}</td>
                            <td>
                                @if($record->ot_hours > 0)
                                    <span class="ot-badge">+{{ $record->ot_hours }} hrs</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">
                                <iconify-icon icon="solar:clock-circle-outline" style="font-size: 32px; opacity: 0.4;"></iconify-icon>
                                <p class="mb-0 mt-2">No attendance records found for these filters.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="d-flex justify-content-end mt-2">
        {{ $records->links() }}
    </div>

    <script>
        document.querySelectorAll('.filter-auto').forEach(select => {
            select.addEventListener('change', function() {
                document.getElementById('filterForm').submit();
            });
        });
    </script>
@endsection