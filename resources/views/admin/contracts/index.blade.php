@extends('layouts.vertical', ['subtitle' => 'Projects'])

@section('content')
    @include('layouts.partials.page-title', ['title' => 'Contracts', 'subtitle' => 'Projects'])

    <style>
        .ct-stat-card { border: none; border-radius: 12px; box-shadow: 0 1px 3px rgba(15,42,67,0.06); }
        .ct-stat-icon {
            width: 44px; height: 44px; border-radius: 10px;
            display: flex; align-items: center; justify-content: center; font-size: 22px; flex-shrink: 0;
        }
        .ct-project-card {
            border: none; border-radius: 14px; box-shadow: 0 1px 3px rgba(15,42,67,0.06);
 
            transition: box-shadow .15s ease, transform .15s ease;
        }
        .ct-project-card:hover { box-shadow: 0 6px 16px rgba(15,42,67,0.10); transform: translateY(-2px); }
        .ct-project-title { font-size: 16px; font-weight: 600; color: #16233b; margin: 0; }
        .ct-meta-row { display: flex; align-items: center; gap: 6px; font-size: 13px; color: #6b7280; }
        .ct-progress-track { height: 5px; border-radius: 4px; background: #eef0f4; overflow: hidden; }
        .ct-progress-fill { height: 100%; border-radius: 4px; }
        .ct-info-pill {
            background: #F5F7FA; border-radius: 8px; padding: 6px 10px; text-align: center; flex: 1;
        }
        .ct-info-pill .val { font-size: 15px; font-weight: 600; color: #16233b; line-height: 1.2; }
        .ct-info-pill .lbl { font-size: 11px; color: #8792a2; text-transform: uppercase; letter-spacing: 0.03em; }
        .ct-icon-btn {
            width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center;
            border: 1px solid #e7e9ee; color: #6b7280; transition: all .15s ease;
        }
        .ct-icon-btn:hover { background: #F5F7FA; }
        .ct-icon-btn.danger:hover { background: #fdeeee; color: #d64545; border-color: #f6d7d7; }
        .ct-icon-btn.primary:hover { background: #eaf0fb; color: #2E5AAC; border-color: #cfe0f6; }
    </style>

    {{-- Quick status strip --}}
    <div class="row g-3 mb-3">
        <div class="col-6 col-md-3">
            <div class="card ct-stat-card">
                <div class="card-body d-flex align-items-center gap-2">
                    <div class="ct-stat-icon" style="background: rgba(46,90,172,0.10); color: #2E5AAC;">
                        <iconify-icon icon="solar:buildings-outline"></iconify-icon>
                    </div>
                    <div>
                        <div class="fw-bold" style="font-size:20px; color:#16233b;">{{ $contracts->total() }}</div>
                        <div class="small text-muted">Total Projects</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card ct-stat-card">
                <div class="card-body d-flex align-items-center gap-2">
                    <div class="ct-stat-icon" style="background: rgba(46,158,91,0.10); color: #2E9E5B;">
                        <iconify-icon icon="solar:check-circle-outline"></iconify-icon>
                    </div>
                    <div>
                        <div class="fw-bold" style="font-size:20px; color:#16233b;">{{ $contracts->where('status','active')->count() }}</div>
                        <div class="small text-muted">Active</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card ct-stat-card">
                <div class="card-body d-flex align-items-center gap-2">
                    <div class="ct-stat-icon" style="background: rgba(240,162,2,0.10); color: #F0A202;">
                        <iconify-icon icon="solar:clock-circle-outline"></iconify-icon>
                    </div>
                    <div>
                        <div class="fw-bold" style="font-size:20px; color:#16233b;">{{ $contracts->where('status','expired')->count() }}</div>
                        <div class="small text-muted">Expired</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card ct-stat-card">
                <div class="card-body d-flex align-items-center gap-2">
                    <div class="ct-stat-icon" style="background: rgba(214,69,69,0.10); color: #D64545;">
                        <iconify-icon icon="solar:close-circle-outline"></iconify-icon>
                    </div>
                    <div>
                        <div class="fw-bold" style="font-size:20px; color:#16233b;">{{ $contracts->where('status','cancelled')->count() }}</div>
                        <div class="small text-muted">Cancelled</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters + New Project --}}
    <form method="GET" action="{{ route('admin.contracts.index') }}" id="filterForm" class="row g-2 mb-3 justify-content-end align-items-end">
        <div class="col-auto">
            <input type="text" name="search" id="searchInput" class="form-control" style="width: 240px;"
                placeholder="Search by project, location..." value="{{ request('search') }}">
        </div>
        <div class="col-auto">
            <select name="status" class="form-select filter-auto" style="width: 160px;">
                <option value="">All Statuses</option>
                <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                <option value="expired" {{ request('status') == 'expired' ? 'selected' : '' }}>Expired</option>
                <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
            </select>
        </div>
        @if(request()->hasAny(['search', 'status']))
            <div class="col-auto">
                <a href="{{ route('admin.contracts.index') }}" class="btn btn-outline-secondary" title="Clear filters">
                    <iconify-icon icon="solar:close-circle-outline"></iconify-icon>
                </a>
            </div>
        @endif
        <div class="col-auto">
            <a href="{{ route('admin.contracts.create') }}" class="btn btn-primary">
                <iconify-icon icon="solar:add-circle-outline" style="margin-right:4px;"></iconify-icon> New Project
            </a>
        </div>
    </form>

    {{-- Project cards --}}
    <div class="row">
        @forelse($contracts as $contract)
            @php
                $statusMap = [
                    'active'    => ['color' => '#2E9E5B', 'bg' => 'rgba(46,158,91,0.10)', 'label' => 'Active'],
                    'expired'   => ['color' => '#F0A202', 'bg' => 'rgba(240,162,2,0.10)', 'label' => 'Expired'],
                    'cancelled' => ['color' => '#D64545', 'bg' => 'rgba(214,69,69,0.10)', 'label' => 'Cancelled'],
                ];
                $s = $statusMap[$contract->status] ?? ['color' => '#8792a2', 'bg' => 'rgba(135,146,162,0.10)', 'label' => ucfirst($contract->status)];
                $progress = $contract->progressPercent();
                $daysLeft = $contract->daysRemaining();
            @endphp
            <div class="col-md-4 mb-4">
                <div class="card ct-project-card h-100" style="--ct-accent: {{ $s['color'] }};">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <p class="ct-project-title">{{ $contract->project_name }}</p>
                            <span class="badge" style="background: {{ $s['bg'] }}; color: {{ $s['color'] }}; font-weight: 600;">
                                {{ $s['label'] }}
                            </span>
                        </div>

                        <div class="ct-meta-row mb-1">
                            <iconify-icon icon="solar:map-point-outline"></iconify-icon> {{ $contract->location }}
                        </div>
                        <div class="ct-meta-row mb-3">
                            <iconify-icon icon="solar:hashtag-outline"></iconify-icon> {{ $contract->contract_number }}
                        </div>

                        {{-- Timeline progress --}}
                        <div class="d-flex justify-content-between mb-1" style="font-size: 12px; color:#8792a2;">
                            <span>{{ $contract->contract_start_date->format('d M Y') }}</span>
                            <span>{{ $contract->contract_end_date->format('d M Y') }}</span>
                        </div>
                        <div class="ct-progress-track mb-1">
                            <div class="ct-progress-fill" style="width: {{ $progress }}%; background-color: {{ $s['color'] }};"></div>
                        </div>
                        <p class="small mb-3" style="color: {{ $s['color'] }};">
                            @if($contract->status === 'active' && $daysLeft > 0)
                                <iconify-icon icon="solar:hourglass-outline" style="font-size:13px;"></iconify-icon> {{ $daysLeft }} days remaining
                            @elseif($contract->status === 'active')
                                Ending very soon
                            @else
                                {{ $s['label'] }}
                            @endif
                        </p>

                        {{-- Quick facts --}}
                        <div class="d-flex gap-2 mb-3">
                            <div class="ct-info-pill">
                                <div class="val">{{ $contract->elevatorUnits->count() }}</div>
                                <div class="lbl">Units</div>
                            </div>
                            <div class="ct-info-pill">
                                <div class="val" style="font-size:13px;">{{ optional($contract->engineer)->name ?? '—' }}</div>
                                <div class="lbl">Engineer</div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center">
                            <a href="{{ route('admin.contracts.show', $contract->id) }}" class="btn btn-sm" style="background: {{ $s['bg'] }}; color: {{ $s['color'] }}; font-weight: 600; border: none;">
                                View Details
                            </a>
                            <div class="d-flex gap-2">
                                <a href="{{ route('admin.contracts.edit', $contract->id) }}" class="ct-icon-btn primary" title="Edit">
                                    <iconify-icon icon="solar:pen-2-outline" style="font-size: 16px;"></iconify-icon>
                                </a>
                                <button type="button" class="ct-icon-btn danger border-0 bg-transparent delete-contract"
                                    data-id="{{ $contract->id }}" title="Delete">
                                    <iconify-icon icon="solar:trash-bin-minimalistic-outline" style="font-size: 16px;"></iconify-icon>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="card ct-stat-card">
                    <div class="card-body text-center text-muted py-5">
                        <iconify-icon icon="solar:buildings-outline" style="font-size: 40px; opacity: 0.4;"></iconify-icon>
                        <p class="mb-2 mt-2">No projects yet.</p>
                        <a href="{{ route('admin.contracts.create') }}" class="btn btn-primary btn-sm">Create your first project</a>
                    </div>
                </div>
            </div>
        @endforelse
    </div>

    <div class="d-flex justify-content-end">
        {{ $contracts->links() }}
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

        document.querySelectorAll('.delete-contract').forEach(button => {
            button.addEventListener('click', function() {
                let contractId = this.dataset.id;
                Swal.fire({
                    title: 'Are you sure?',
                    text: "This will permanently delete the project and all its units.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        fetch("{{ url('admin/contracts') }}/" + contractId, {
                                method: 'DELETE',
                                headers: {
                                    'X-CSRF-TOKEN': "{{ csrf_token() }}",
                                    'Accept': 'application/json'
                                }
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    Swal.fire('Deleted!', data.message, 'success').then(() => location.reload());
                                } else {
                                    Swal.fire('Error!', data.message, 'error');
                                }
                            });
                    }
                });
            });
        });
    </script>
@endsection