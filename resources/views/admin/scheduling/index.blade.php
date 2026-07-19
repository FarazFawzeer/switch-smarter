@extends('layouts.vertical', ['subtitle' => 'PPM Scheduling'])

@section('content')
    @include('layouts.partials.page-title', ['title' => 'Scheduling', 'subtitle' => 'PPM Scheduling'])

    <form method="GET" action="{{ route('admin.scheduling.index') }}" id="filterForm" class="row g-2 mb-3 justify-content-end">
        <div class="col-auto">
            <input type="text" name="search" id="searchInput" class="form-control" style="width: 260px;"
                placeholder="Search by project or location..." value="{{ request('search') }}">
        </div>
    </form>

    <div class="row">
        @forelse($contracts as $contract)
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h5 class="mb-0">{{ $contract->project_name }}</h5>
                            @if(auth()->user()->type === 'Engineer')
                                @if($contract->assigned_supervisor_id)
                                    <span class="badge badge-soft-success">Assigned</span>
                                @else
                                    <span class="badge badge-soft-warning">Not Assigned</span>
                                @endif
                            @else
                                @if($contract->is_scheduled)
                                    <span class="badge badge-soft-success">Scheduled</span>
                                @else
                                    <span class="badge badge-soft-warning">Not Scheduled</span>
                                @endif
                            @endif
                        </div>
                        <p class="text-muted mb-2">
                            <iconify-icon icon="solar:map-point-outline"></iconify-icon> {{ $contract->location }}
                        </p>
                        <p class="small text-muted mb-3">
                            Contract No: {{ $contract->contract_number }}<br>
                            @if(auth()->user()->type === 'Engineer')
                                Supervisor: {{ optional($contract->supervisor)->name ?? '—' }}
                            @else
                                Engineer: {{ optional($contract->engineer)->name ?? '—' }}
                            @endif
                        </p>

                        @if(auth()->user()->type === 'Engineer')
                            @if($contract->assigned_supervisor_id)
                                <span class="text-muted small">Handed off — supervisor manages scheduling from here.</span>
                            @else
                                <a href="{{ route('admin.scheduling.assign-supervisor.form', $contract->id) }}" class="btn btn-sm btn-primary w-100">
                                    Assign to Supervisor
                                </a>
                            @endif
                        @else
                            @if($contract->is_scheduled)
                                <a href="{{ route('admin.scheduling.show', $contract->id) }}" class="btn btn-sm btn-outline-primary w-100">
                                    View Schedule
                                </a>
                            @else
                                <a href="{{ route('admin.scheduling.create', $contract->id) }}" class="btn btn-sm btn-primary w-100">
                                    Schedule Now
                                </a>
                            @endif
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="card">
                    <div class="card-body text-center text-muted py-5">
                        No active contracts assigned to you yet.
                    </div>
                </div>
            </div>
        @endforelse
    </div>

    <div class="d-flex justify-content-end">
        {{ $contracts->links() }}
    </div>

    <script>
        let searchTimeout;
        document.getElementById('searchInput').addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                document.getElementById('filterForm').submit();
            }, 600);
        });
    </script>
@endsection