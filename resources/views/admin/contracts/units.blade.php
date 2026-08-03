@extends('layouts.vertical', ['subtitle' => 'Unit Master'])

@section('content')
    @include('layouts.partials.page-title', ['title' => 'Contracts', 'subtitle' => 'Unit Master'])

    <style>
        .ct-stat-card { border: none; border-radius: 12px; box-shadow: 0 1px 3px rgba(15,42,67,0.06); }
        .ct-view-toggle { border: 1px solid #e7e9ee; border-radius: 8px; overflow: hidden; }
        .ct-view-toggle a { padding: 6px 14px; font-size: 13px; color: #6b7280; text-decoration: none; display: inline-flex; align-items: center; gap: 5px; }
        .ct-view-toggle a.active { background: #2E5AAC; color: #fff; }
        .ct-list-table { border-collapse: separate; border-spacing: 0; width: 100%; font-size: 13px; }
        .ct-list-table thead th {
            background: #F5F7FA; color: #56606f; font-weight: 600; padding: 10px 12px;
            border-bottom: 1px solid #e7e9ee; white-space: nowrap;
        }
        .ct-list-table thead th a { color: inherit; text-decoration: none; }
        .ct-list-table thead th a:hover { color: #2E5AAC; }
        .ct-list-table tbody td { padding: 10px 12px; border-bottom: 1px solid #f0f1f4; vertical-align: middle; }
        .ct-list-table tbody tr:hover { background: #FAFBFD; }
        .ct-filter-select { font-size: 12px; padding: 3px 6px; border-radius: 6px; border: 1px solid #e7e9ee; background: #fff; max-width: 140px; }
        .ct-status-pill { display: inline-block; padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 700; color: #fff; }
    </style>

    {{-- View toggle + search + New Project --}}
    <form method="GET" action="{{ route('admin.contracts.index') }}" id="filterForm" class="row g-2 mb-3 align-items-end">
        <div class="col-auto">
            <div class="ct-view-toggle d-flex">
                <a href="{{ route('admin.contracts.index', ['view' => 'grid']) }}">
                    <iconify-icon icon="solar:widget-outline"></iconify-icon> Grid
                </a>
                <a href="{{ route('admin.contracts.index', ['view' => 'list']) }}">
                    <iconify-icon icon="solar:list-outline"></iconify-icon> List
                </a>
                <a href="{{ route('admin.contracts.index', ['view' => 'units']) }}" class="active">
                    <iconify-icon icon="solar:widget-5-outline"></iconify-icon> Unit Master
                </a>
            </div>
        </div>
        <input type="hidden" name="view" value="units">
        <div class="col-auto">
            <input type="text" name="unit_search" id="searchInput" class="form-control" style="width: 220px;"
                placeholder="Search by unit ID or project..." value="{{ request('unit_search') }}" form="filterForm">
        </div>
        @if(request()->hasAny(['unit_search', 'unit_type', 'elevator_type', 'brand', 'route_id', 'engineer_id', 'contract_status']))
            <div class="col-auto">
                <a href="{{ route('admin.contracts.index', ['view' => 'units']) }}" class="btn btn-outline-secondary" title="Clear filters">
                    <iconify-icon icon="solar:close-circle-outline"></iconify-icon>
                </a>
            </div>
        @endif
        <div class="col-auto ms-auto">
            <a href="{{ route('admin.contracts.create') }}" class="btn btn-primary">
                <iconify-icon icon="solar:add-circle-outline" style="margin-right:4px;"></iconify-icon> New Project
            </a>
        </div>
    </form>

    <div class="card ct-stat-card">
        <div class="table-responsive">
            <table class="ct-list-table">
                <thead>
                    <tr>
                        <th>Unit ID</th>
                        <th>
                            Unit Type
                            <div><select class="ct-filter-select filter-auto" name="unit_type" form="filterForm">
                                <option value="">All</option>
                                @foreach($unitTypes as $type)
                                    <option value="{{ $type }}" {{ request('unit_type') == $type ? 'selected' : '' }}>{{ $type }}</option>
                                @endforeach
                            </select></div>
                        </th>
                        <th>
                            Elevator Type
                            <div><select class="ct-filter-select filter-auto" name="elevator_type" form="filterForm">
                                <option value="">All</option>
                                @foreach($elevatorTypes as $type)
                                    <option value="{{ $type }}" {{ request('elevator_type') == $type ? 'selected' : '' }}>{{ $type }}</option>
                                @endforeach
                            </select></div>
                        </th>
                        <th>Speed</th>
                        <th>Capacity</th>
                        <th>Brand</th>
                        <th>Model</th>
                        <th>Project</th>
                        <th>Location</th>
                        <th>
                            Route
                            <div><select class="ct-filter-select filter-auto" name="route_id" form="filterForm">
                                <option value="">All</option>
                                @foreach($routes as $route)
                                    <option value="{{ $route->id }}" {{ request('route_id') == $route->id ? 'selected' : '' }}>{{ $route->route_no }}</option>
                                @endforeach
                            </select></div>
                        </th>
                        <th>
                            Engineer
                            <div><select class="ct-filter-select filter-auto" name="engineer_id" form="filterForm">
                                <option value="">All</option>
                                @foreach($engineers as $engineer)
                                    <option value="{{ $engineer->id }}" {{ request('engineer_id') == $engineer->id ? 'selected' : '' }}>{{ $engineer->name }}</option>
                                @endforeach
                            </select></div>
                        </th>
                        <th>
                            Contract Status
                            <div><select class="ct-filter-select filter-auto" name="contract_status" form="filterForm">
                                <option value="">All</option>
                                <option value="active" {{ request('contract_status') == 'active' ? 'selected' : '' }}>Active</option>
                                <option value="expired" {{ request('contract_status') == 'expired' ? 'selected' : '' }}>Expired</option>
                                <option value="cancelled" {{ request('contract_status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                            </select></div>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($units as $unit)
                        @php
                            $statusColor = match(optional($unit->contract)->status) {
                                'active' => '#2E9E5B', 'expired' => '#F0A202', 'cancelled' => '#D64545', default => '#8792a2',
                            };
                        @endphp
                        <tr>
                            <td class="fw-semibold">{{ $unit->identification_no }}</td>
                            <td>{{ $unit->unit_type }}</td>
                            <td>{{ $unit->elevator_type ?? '—' }}</td>
                            <td>{{ $unit->speed ?? '—' }}</td>
                            <td>{{ $unit->capacity ?? '—' }}</td>
                            <td>{{ $unit->brand ?? '—' }}</td>
                            <td>{{ $unit->model ?? '—' }}</td>
                            <td>
                                @if($unit->contract)
                                    <a href="{{ route('admin.contracts.show', $unit->contract->id) }}" class="text-dark fw-semibold text-decoration-none">
                                        {{ $unit->contract->project_name }}
                                    </a>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>{{ optional($unit->contract)->location ?? '—' }}</td>
                            <td>{{ optional(optional($unit->contract)->route)->route_no ?? '—' }}</td>
                            <td>{{ optional(optional($unit->contract)->engineer)->name ?? '—' }}</td>
                            <td><span class="ct-status-pill" style="background:{{ $statusColor }};">{{ ucfirst(optional($unit->contract)->status ?? '—') }}</span></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="text-center text-muted py-5">
                                <iconify-icon icon="solar:widget-5-outline" style="font-size: 32px; opacity: 0.4;"></iconify-icon>
                                <p class="mb-0 mt-2">No units match these filters.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="d-flex justify-content-end mt-2">
        {{ $units->links() }}
    </div>

    <script>
        document.querySelectorAll('.filter-auto').forEach(select => {
            select.addEventListener('change', function() {
                document.getElementById('filterForm').submit();
            });
        });

        let searchTimeout;
        document.getElementById('searchInput').addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                document.getElementById('filterForm').submit();
            }, 600);
        });
    </script>
@endsection