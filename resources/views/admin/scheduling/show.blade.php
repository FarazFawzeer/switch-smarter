@extends('layouts.vertical', ['subtitle' => 'PPM Schedule'])

@section('content')
    @include('layouts.partials.page-title', ['title' => 'Scheduling', 'subtitle' => 'PPM Schedule'])

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <h5 class="card-title mb-0">{{ $contract->project_name }}</h5>
                <p class="card-subtitle">{{ $contract->location }} — Contract No: {{ $contract->contract_number }}</p>
            </div>
            <span class="badge badge-soft-success">Scheduled</span>
        </div>
        <div class="card-body">
            <div class="row mb-4">
                <div class="col-md-3">
                    <span class="text-muted small">Engineer</span>
                    <p class="mb-0">{{ optional($contract->engineer)->name ?? '—' }}</p>
                </div>
                <div class="col-md-3">
                    <span class="text-muted small">Supervisor</span>
                    <p class="mb-0">{{ optional($contract->supervisor)->name ?? '—' }}</p>
                </div>
                <div class="col-md-3">
                    <span class="text-muted small">Route</span>
                    <p class="mb-0">{{ optional($contract->route)->route_no ?? '—' }}</p>
                </div>
                <div class="col-md-3">
                    <span class="text-muted small">PPM Start Date</span>
                    <p class="mb-0">{{ $contract->ppm_start_date->format('d M Y') }}</p>
                </div>
            </div>

            @if ($contract->renewals->count())
                <div class="alert alert-info d-flex align-items-center gap-2 mb-4">
                    <iconify-icon icon="solar:refresh-outline" style="font-size: 18px;"></iconify-icon>
                    This contract has been renewed {{ $contract->renewals->count() }} time(s).
                    Latest renewal: {{ $contract->renewals->first()->new_start_date->format('d M Y') }} –
                    {{ $contract->renewals->first()->new_end_date->format('d M Y') }}.
                </div>
            @endif

            {{-- Filter controls --}}
      {{-- Filter controls --}}
<form method="GET" action="{{ route('admin.scheduling.show', $contract->id) }}" id="jobFilterForm" class="row g-2 mb-3 align-items-end">
    {{-- <div class="col-auto">
        <label class="form-label small mb-1">Month</label>
        <select name="month" class="form-select form-select-sm" style="width: 140px;">
            <option value="">All Months</option>
            @foreach([
                1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
                5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
                9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
            ] as $num => $name)
                <option value="{{ $num }}" {{ request('month') == $num ? 'selected' : '' }}>{{ $name }}</option>
            @endforeach
        </select>
    </div> --}}
    {{-- <div class="col-auto">
        <label class="form-label small mb-1">Year</label>
        <select name="year" class="form-select form-select-sm" style="width: 110px;">
            <option value="">All Years</option>
            @foreach($yearOptions as $year)
                <option value="{{ $year }}" {{ request('year') == $year ? 'selected' : '' }}>{{ $year }}</option>
            @endforeach
        </select>
    </div> --}}
    {{-- <div class="col-auto text-center">
        <span class="text-muted small">— or —</span>
    </div> --}}
    <div class="col-auto">
        <label class="form-label small mb-1">From date</label>
        <input type="date" name="from" class="form-control form-control-sm" value="{{ request('from') }}">
    </div>
    <div class="col-auto">
        <label class="form-label small mb-1">To date</label>
        <input type="date" name="to" class="form-control form-control-sm" value="{{ request('to') }}">
    </div>
    <div class="col-auto">
        <button type="submit" class="btn btn-primary btn-sm">Apply</button>
    </div>
    @if($isFiltering)
        <div class="col-auto">
            <a href="{{ route('admin.scheduling.show', $contract->id) }}" class="btn btn-outline-secondary btn-sm">
                <iconify-icon icon="solar:close-circle-outline"></iconify-icon> Reset to Default
            </a>
        </div>
    @endif
</form>
            <div class="d-flex justify-content-between align-items-center mb-2">
                <p class="text-muted small mb-0">
                    @if($isFiltering)
                        Showing {{ $visibleJobs->count() }} of {{ $totalJobsCount }} total scheduled visits (filtered).
                    @else
                        Showing visits through {{ now()->format('F Y') }}
                        ({{ $visibleJobs->count() }} of {{ $totalJobsCount }} total scheduled visits).
                    @endif
                </p>
            </div>

            <div class="table-responsive">
                <table class="table table-hover table-centered">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Scheduled Date</th>
                            <th>Technician</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($visibleJobs as $index => $job)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $job->scheduled_date->format('d M Y') }}</td>
                                <td>{{ optional($job->technician)->name ?? 'Unassigned' }}</td>
                                <td>
                                    @php
                                        $statusClass = match ($job->status) {
                                            'completed' => 'badge-soft-success',
                                            'in_progress' => 'badge-soft-info',
                                            'overdue' => 'badge-soft-danger',
                                            'cancelled' => 'badge-soft-secondary',
                                            default => $job->scheduled_date->isPast() ? 'badge-soft-danger' : 'badge-soft-warning',
                                        };
                                        $statusLabel = $job->status === 'pending' && $job->scheduled_date->isPast()
                                            ? 'Overdue'
                                            : ucfirst(str_replace('_', ' ', $job->status));
                                    @endphp
                                    <span class="badge {{ $statusClass }}">{{ $statusLabel }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-3">
                                    {{ $isFiltering ? 'No visits found for this filter.' : 'No visits scheduled up to this month yet.' }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <a href="{{ route('admin.scheduling.index') }}" class="btn btn-secondary mt-3">Back to List</a>
        </div>
    </div>

    <script>
      // If the user picks month/year, clear the from/to fields (mutually exclusive filter modes)
document.querySelector('select[name="month"]').addEventListener('change', clearDateRange);
document.querySelector('select[name="year"]').addEventListener('change', clearDateRange);

function clearDateRange() {
    document.querySelector('input[name="from"]').value = '';
    document.querySelector('input[name="to"]').value = '';
}

// If the user types from/to, clear the month/year dropdowns
['from', 'to'].forEach(name => {
    document.querySelector(`input[name="${name}"]`).addEventListener('input', function() {
        if (this.value) {
            document.querySelector('select[name="month"]').value = '';
            document.querySelector('select[name="year"]').value = '';
        }
    });
});
    </script>
@endsection