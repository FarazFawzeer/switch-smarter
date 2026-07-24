@extends('layouts.vertical', ['subtitle' => 'Team'])

@section('content')
    @include('layouts.partials.page-title', ['title' => 'Team', 'subtitle' => 'Team'])

    <style>
        .tm-stat-card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(15, 42, 67, 0.06);
        }

        .tm-stat-icon {
            width: 44px;
            height: 44px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            flex-shrink: 0;
        }

        .tm-card {
            border: none;
            border-radius: 14px;
            box-shadow: 0 1px 3px rgba(15, 42, 67, 0.06);
        }

        .tm-section-label {
            font-size: 13px;
            font-weight: 600;
            letter-spacing: 0.03em;
            text-transform: uppercase;
            color: #8792a2;
        }

        .tm-row {
            padding: 12px 4px;
        }

        .tm-row+.tm-row {
            border-top: 1px solid #eef0f4;
        }

        .tm-name {
            font-weight: 600;
            color: #16233b;
        }

        .tm-name:hover {
            color: #2E5AAC;
        }

        .tm-sub {
            font-size: 12px;
            color: #8792a2;
        }

        .tm-accordion-btn {
            border: none !important;
            background: #fff !important;
            box-shadow: none !important;
            border-radius: 12px !important;
            padding: 14px 16px;
        }

        .tm-accordion-item {
            border: none;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(15, 42, 67, 0.06);
            margin-bottom: 10px;
            overflow: hidden;
        }

        .tm-accordion-item .accordion-body {
            background: #FAFBFD;
            border-top: 1px solid #eef0f4;
        }

        .tm-pill {
            background: #F5F7FA;
            border-radius: 20px;
            padding: 4px 12px;
            font-size: 12px;
            font-weight: 600;
            color: #6b7280;
            display: inline-block;
        }

        .tm-icon-btn {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid #e7e9ee;
            color: #6b7280;
            transition: all .15s ease;
        }

        .tm-icon-btn.danger:hover {
            background: #fdeeee;
            color: #d64545;
            border-color: #f6d7d7;
        }

        .tm-icon-btn.primary:hover {
            background: #eaf0fb;
            color: #2E5AAC;
            border-color: #cfe0f6;
        }

        .supervisor-toggle .supervisor-chevron {
            transition: transform 0.15s ease;
            font-size: 14px;
            color: #8792a2;
        }

        .supervisor-toggle[aria-expanded="true"] .supervisor-chevron {
            transform: rotate(90deg);
        }

        .tm-view-toggle {
            border: 1px solid #e7e9ee;
            border-radius: 8px;
            overflow: hidden;
        }

        .tm-view-toggle a {
            padding: 6px 14px;
            font-size: 13px;
            color: #6b7280;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .tm-view-toggle a.active {
            background: #2E5AAC;
            color: #fff;
        }

        .tm-master-table {
            border-collapse: separate;
            border-spacing: 0;
            width: 100%;
            font-size: 13px;
        }

        .tm-master-table thead th {
            background: #F5F7FA;
            color: #56606f;
            font-weight: 600;
            padding: 10px 12px;
            border-bottom: 1px solid #e7e9ee;
            white-space: nowrap;
        }

        .tm-master-table thead th a {
            color: inherit;
            text-decoration: none;
        }

        .tm-master-table thead th a:hover {
            color: #2E5AAC;
        }

        .tm-master-table tbody td {
            padding: 10px 12px;
            border-bottom: 1px solid #f0f1f4;
            vertical-align: middle;
        }

        .tm-master-table tbody tr:hover {
            background: #FAFBFD;
        }

        .tm-status-pill {
            display: inline-block;
            padding: 3px 12px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 700;
            background: #2E9E5B;
            color: #fff;
        }
    </style>

    {{-- Quick role stats --}}
    <div class="row g-3 mb-3">
        <div class="col-6 col-md-3">
            <div class="card tm-stat-card">
                <div class="card-body d-flex align-items-center gap-2">
                    <div class="tm-stat-icon" style="background: rgba(46,90,172,0.10); color: #2E5AAC;">
                        <iconify-icon icon="solar:user-check-outline"></iconify-icon>
                    </div>
                    <div>
                        <div class="fw-bold" style="font-size:20px; color:#16233b;">{{ $roleCounts['Manager'] }}</div>
                        <div class="small text-muted">Manager</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card tm-stat-card">
                <div class="card-body d-flex align-items-center gap-2">
                    <div class="tm-stat-icon" style="background: rgba(23,162,184,0.10); color: #17A2B8;">
                        <iconify-icon icon="solar:settings-outline"></iconify-icon>
                    </div>
                    <div>
                        <div class="fw-bold" style="font-size:20px; color:#16233b;">{{ $roleCounts['Engineer'] }}</div>
                        <div class="small text-muted">Engineers</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card tm-stat-card">
                <div class="card-body d-flex align-items-center gap-2">
                    <div class="tm-stat-icon" style="background: rgba(240,162,2,0.10); color: #F0A202;">
                        <iconify-icon icon="solar:users-group-rounded-outline"></iconify-icon>
                    </div>
                    <div>
                        <div class="fw-bold" style="font-size:20px; color:#16233b;">{{ $roleCounts['Supervisor'] }}</div>
                        <div class="small text-muted">Supervisors</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card tm-stat-card">
                <div class="card-body d-flex align-items-center gap-2">
                    <div class="tm-stat-icon" style="background: rgba(46,158,91,0.10); color: #2E9E5B;">
                        <iconify-icon icon="solar:user-id-outline"></iconify-icon>
                    </div>
                    <div>
                        <div class="fw-bold" style="font-size:20px; color:#16233b;">{{ $roleCounts['Technician'] }}</div>
                        <div class="small text-muted">Technicians</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Toolbar: view toggle + filters + Add --}}
    <form method="GET" action="{{ route('admin.team.index') }}" id="filterForm" class="row g-2 mb-3 align-items-end">
        <div class="col-auto">
            <div class="tm-view-toggle d-flex">
                <a href="{{ request()->fullUrlWithQuery(['view' => 'organization']) }}"
                    class="{{ $view === 'organization' ? 'active' : '' }}">
                    <iconify-icon icon="solar:sitemap-outline"></iconify-icon> Team Organization
                </a>
                <a href="{{ request()->fullUrlWithQuery(['view' => 'master']) }}"
                    class="{{ $view === 'master' ? 'active' : '' }}">
                    <iconify-icon icon="solar:list-outline"></iconify-icon> Employee Master
                </a>
            </div>
        </div>
        <input type="hidden" name="view" value="{{ $view }}">
        <div class="col-auto">
            <input type="text" name="search" id="searchInput" class="form-control" style="width: 220px;"
                placeholder="Search by name, email, or employee ID..." value="{{ request('search') }}" form="filterForm">
        </div>
        <div class="col-auto">
            <select name="type" class="form-select filter-auto" style="width: 160px;" form="filterForm">
                <option value="">All Roles</option>
                <option value="Manager" {{ request('type') == 'Manager' ? 'selected' : '' }}>Manager</option>
                <option value="Engineer" {{ request('type') == 'Engineer' ? 'selected' : '' }}>Engineer</option>
                <option value="Supervisor" {{ request('type') == 'Supervisor' ? 'selected' : '' }}>Supervisor</option>
                <option value="Technician" {{ request('type') == 'Technician' ? 'selected' : '' }}>Technician</option>
            </select>
        </div>
        @if (request()->hasAny(['search', 'type']))
            <div class="col-auto">
                <a href="{{ route('admin.team.index') }}?view={{ $view }}" class="btn btn-outline-secondary"
                    title="Clear filters">
                    <iconify-icon icon="solar:close-circle-outline"></iconify-icon>
                </a>
            </div>
        @endif
        <div class="col-auto ms-auto">
            <a href="{{ route('admin.team.create') }}" class="btn btn-primary">
                <iconify-icon icon="solar:user-plus-outline" style="margin-right:4px;"></iconify-icon> Add Team Member
            </a>
        </div>
    </form>

    @if ($view === 'master')
        {{-- ===== EMPLOYEE MASTER: flat spreadsheet-style list ===== --}}
        <div class="card tm-card">
            <div class="table-responsive">
                <table class="tm-master-table">
                    <thead>
                        <tr>
                            <th>Employee ID</th>
                            <th>Name</th>
                            <th>Role</th>
                            <th>Reports To</th>
                            <th>Route</th>
                            <th>Status</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($team as $member)
                            @php
                                $roleColor = match ($member->type) {
                                    'Manager' => ['#2E5AAC', 'rgba(46,90,172,0.10)'],
                                    'Engineer' => ['#17A2B8', 'rgba(23,162,184,0.10)'],
                                    'Supervisor' => ['#F0A202', 'rgba(240,162,2,0.10)'],
                                    'Technician' => ['#2E9E5B', 'rgba(46,158,91,0.10)'],
                                    default => ['#8792a2', 'rgba(135,146,162,0.10)'],
                                };
                                $reportsTo = match ($member->type) {
                                    'Supervisor' => optional($member->engineer)->name,
                                    'Technician' => optional($member->supervisor)->name,
                                    default => null,
                                };
                            @endphp
                            <tr>
                                <td class="fw-semibold">{{ $member->employee_id ?? '—' }}</td>
                                <td>
                                    <a href="{{ route('admin.team.show', $member->id) }}"
                                        class="tm-name text-decoration-none">{{ $member->name }}</a>
                                </td>
                                <td><span class="badge"
                                        style="background:{{ $roleColor[1] }}; color:{{ $roleColor[0] }}; font-weight:600;">{{ $member->type }}</span>
                                </td>
                                <td>{{ $reportsTo ?? '—' }}</td>
                                <td>
                                    @forelse($member->routes as $route)
                                        <span class="tm-pill me-1">{{ $route->route_no }}</span>
                                    @empty
                                        <span class="text-muted">—</span>
                                    @endforelse
                                </td>
                                <td><span class="tm-status-pill">Active</span></td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-2">
                                        <a href="{{ route('admin.team.show', $member->id) }}" class="tm-icon-btn"
                                            title="View">
                                            <iconify-icon icon="solar:eye-outline" style="font-size: 15px;"></iconify-icon>
                                        </a>
                                        <a href="{{ route('admin.team.edit', $member->id) }}" class="tm-icon-btn primary"
                                            title="Edit">
                                            <iconify-icon icon="solar:pen-2-outline"
                                                style="font-size: 15px;"></iconify-icon>
                                        </a>
                                        <button type="button"
                                            class="tm-icon-btn danger border-0 bg-transparent delete-team"
                                            data-id="{{ $member->id }}" title="Delete">
                                            <iconify-icon icon="solar:trash-bin-minimalistic-outline"
                                                style="font-size: 15px;"></iconify-icon>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-5">No team members found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="d-flex justify-content-end mt-2">
            {{ $team->links() }}
        </div>
    @elseif($isFiltering)
        {{-- ===== ORGANIZATION VIEW, SEARCH RESULT: flat table ===== --}}
        <div class="card tm-card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-centered mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Employee ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Routes</th>
                                <th>Reports To</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($team as $member)
                                @php
                                    $roleColor = match ($member->type) {
                                        'Manager' => ['#2E5AAC', 'rgba(46,90,172,0.10)'],
                                        'Engineer' => ['#17A2B8', 'rgba(23,162,184,0.10)'],
                                        'Supervisor' => ['#F0A202', 'rgba(240,162,2,0.10)'],
                                        'Technician' => ['#2E9E5B', 'rgba(46,158,91,0.10)'],
                                        default => ['#8792a2', 'rgba(135,146,162,0.10)'],
                                    };
                                @endphp
                                <tr>
                                    <td>{{ $member->employee_id ?? '—' }}</td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <img src="{{ storage_asset($member->image_path) ?? asset('/images/users/avatar-6.jpg') }}"
                                                class="avatar-sm rounded-circle">
                                            <a href="{{ route('admin.team.show', $member->id) }}"
                                                class="tm-name">{{ $member->name }}</a>
                                        </div>
                                    </td>
                                    <td class="text-muted">{{ $member->email }}</td>
                                    <td><span class="badge"
                                            style="background:{{ $roleColor[1] }}; color:{{ $roleColor[0] }}; font-weight:600;">{{ $member->type }}</span>
                                    </td>
                                    <td>
                                        @forelse($member->routes as $route)
                                            <span class="tm-pill me-1">{{ $route->route_no }}</span>
                                        @empty
                                            <span class="text-muted small">—</span>
                                        @endforelse
                                    </td>
                                    <td class="text-muted">
                                        @if ($member->type === 'Supervisor')
                                            {{ optional($member->engineer)->name ?? '—' }}
                                        @elseif($member->type === 'Technician')
                                            {{ optional($member->supervisor)->name ?? '—' }}
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex justify-content-center gap-2">
                                            <a href="{{ route('admin.team.edit', $member->id) }}"
                                                class="tm-icon-btn primary" title="Edit">
                                                <iconify-icon icon="solar:pen-2-outline"
                                                    style="font-size: 15px;"></iconify-icon>
                                            </a>
                                            <button type="button"
                                                class="tm-icon-btn danger border-0 bg-transparent delete-team"
                                                data-id="{{ $member->id }}" title="Delete">
                                                <iconify-icon icon="solar:trash-bin-minimalistic-outline"
                                                    style="font-size: 15px;"></iconify-icon>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">No matching team members found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="d-flex justify-content-end mt-2">
            {{ $team->links() }}
        </div>
    @else
        {{-- ===== ORGANIZATION VIEW, DEFAULT: org tree ===== --}}

        @if ($managers->count())
            <div class="card tm-card mb-3">
                <div class="card-body">
                    <p class="tm-section-label mb-2">Management</p>
                    @foreach ($managers as $manager)
                        <div class="tm-row d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center gap-2">
                                <img src="{{ storage_asset($manager->image_path) ?? asset('/images/users/avatar-6.jpg') }}"
                                    class="avatar-sm rounded-circle">
                                <div>
                                    <a href="{{ route('admin.team.show', $manager->id) }}"
                                        class="tm-name d-block">{{ $manager->name }}</a>
                                    <span class="tm-sub">{{ $manager->email }}</span>
                                </div>
                            </div>
                            <span class="tm-pill">Oversees organization</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="d-flex align-items-center justify-content-between mb-2 mt-4">
            <p class="tm-section-label mb-0">Engineering Teams</p>
            <span class="small text-muted">Click to expand each level</span>
        </div>

        <div class="accordion" id="engineerAccordion">
            @forelse($engineers as $engineer)
                <div class="accordion-item tm-accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed tm-accordion-btn" type="button"
                            data-bs-toggle="collapse" data-bs-target="#engineer-{{ $engineer->id }}">
                            <div class="d-flex align-items-center justify-content-between w-100 me-2">
                                <div class="d-flex align-items-center gap-3">
                                    <img src="{{ storage_asset($engineer->image_path) ?? asset('/images/users/avatar-6.jpg') }}"
                                        class="avatar-sm rounded-circle">
                                    <div>
                                        <span class="tm-name">{{ $engineer->name }}</span>
                                        <span class="badge ms-2"
                                            style="background: rgba(23,162,184,0.10); color:#17A2B8; font-weight:600;">Engineer</span>
                                        @foreach ($engineer->routes as $route)
                                            <span class="tm-pill ms-1">{{ $route->route_no }}</span>
                                        @endforeach
                                    </div>
                                </div>
                                <span class="tm-pill me-3">{{ $engineer->supervisors_count }} supervisor(s)</span>
                            </div>
                        </button>
                    </h2>
                    <div id="engineer-{{ $engineer->id }}" class="accordion-collapse collapse"
                        data-bs-parent="#engineerAccordion">
                        <div class="accordion-body">
                            <div class="d-flex justify-content-end mb-2">
                                <a href="{{ route('admin.team.show', $engineer->id) }}" class="small">View full profile
                                    →</a>
                            </div>

                            @forelse($engineer->supervisors as $supervisor)
                                <div class="border rounded mb-2" style="background:#fff;">
                                    <div class="d-flex justify-content-between align-items-center p-2 supervisor-toggle"
                                        data-bs-toggle="collapse" data-bs-target="#supervisor-{{ $supervisor->id }}"
                                        style="cursor:pointer;">
                                        <div class="d-flex align-items-center gap-2">
                                            <iconify-icon icon="solar:alt-arrow-right-outline"
                                                class="supervisor-chevron"></iconify-icon>
                                            <img src="{{ storage_asset($supervisor->image_path) ?? asset('/images/users/avatar-6.jpg') }}"
                                                class="avatar-sm rounded-circle">
                                            <div>
                                                <a href="{{ route('admin.team.show', $supervisor->id) }}"
                                                    class="tm-name d-block"
                                                    onclick="event.stopPropagation()">{{ $supervisor->name }}</a>
                                                <div class="d-flex align-items-center gap-1 flex-wrap">
                                                    <span class="badge"
                                                        style="background: rgba(240,162,2,0.10); color:#F0A202; font-weight:600;">Supervisor</span>
                                                    @foreach ($supervisor->routes as $route)
                                                        <span class="tm-pill">{{ $route->route_no }}</span>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                        <span class="tm-pill">{{ $supervisor->technicians_count }} technician(s)</span>
                                    </div>

                                    <div id="supervisor-{{ $supervisor->id }}" class="collapse">
                                        <div class="px-3 pb-3">
                                            @forelse($supervisor->technicians as $technician)
                                                <div class="tm-row d-flex justify-content-between align-items-center">
                                                    <div class="d-flex align-items-center gap-2">
                                                        <img src="{{ storage_asset($technician->image_path) ?? asset('/images/users/avatar-6.jpg') }}"
                                                            class="avatar-sm rounded-circle">
                                                        <div>
                                                            <a href="{{ route('admin.team.show', $technician->id) }}"
                                                                class="tm-name d-block">{{ $technician->name }}</a>
                                                            <span class="tm-sub">{{ $technician->email }}</span>
                                                        </div>
                                                    </div>
                                                    <div class="d-flex align-items-center gap-2">
                                                        <span class="badge"
                                                            style="background: rgba(46,158,91,0.10); color:#2E9E5B; font-weight:600;">Technician</span>
                                                        @if ($technician->routes->first())
                                                            <span
                                                                class="tm-pill">{{ $technician->routes->first()->route_no }}</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            @empty
                                                <p class="text-muted small mb-0 py-2">No technicians assigned to this
                                                    supervisor yet.</p>
                                            @endforelse
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <p class="text-muted small mb-0 py-2">No supervisors assigned to this engineer yet.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            @empty
                <div class="card tm-card">
                    <div class="card-body text-center text-muted py-5">
                        <iconify-icon icon="solar:users-group-rounded-outline"
                            style="font-size: 36px; opacity: 0.4;"></iconify-icon>
                        <p class="mb-2 mt-2">No engineers added yet.</p>
                        <a href="{{ route('admin.team.create') }}" class="btn btn-primary btn-sm">Add your first one</a>
                    </div>
                </div>
            @endforelse
        </div>

        @if ($unassignedTechnicianCount > 0)
            <div class="alert d-flex align-items-center gap-2 mt-3 mb-0"
                style="background: rgba(240,162,2,0.08); border: 1px solid rgba(240,162,2,0.25); color: #8a6116; border-radius: 10px;">
                <iconify-icon icon="solar:danger-triangle-outline" style="font-size: 20px; color:#F0A202;"></iconify-icon>
                <span>{{ $unassignedTechnicianCount }} technician(s) are not yet assigned to a supervisor.</span>
                <a href="{{ route('admin.team.index') }}?type=Technician" class="ms-1 fw-semibold">View them</a>
            </div>
        @endif
    @endif

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

        document.querySelectorAll('.supervisor-toggle').forEach(el => {
            const targetId = el.getAttribute('data-bs-target');
            const target = document.querySelector(targetId);
            if (target) {
                target.addEventListener('shown.bs.collapse', () => el.setAttribute('aria-expanded', 'true'));
                target.addEventListener('hidden.bs.collapse', () => el.setAttribute('aria-expanded', 'false'));
            }
        });

        document.querySelectorAll('.delete-team').forEach(button => {
            button.addEventListener('click', function() {
                let memberId = this.dataset.id;

                Swal.fire({
                    title: 'Are you sure?',
                    text: "You won't be able to revert this!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        fetch("{{ url('admin/team') }}/" + memberId, {
                                method: 'DELETE',
                                headers: {
                                    'X-CSRF-TOKEN': "{{ csrf_token() }}",
                                    'Accept': 'application/json'
                                }
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    Swal.fire('Deleted!', data.message, 'success').then(() =>
                                        location.reload());
                                } else {
                                    Swal.fire('Error!', data.message || 'Something went wrong!',
                                        'error');
                                }
                            })
                            .catch(error => {
                                Swal.fire('Error!', 'Something went wrong!', 'error');
                            });
                    }
                });
            });
        });
    </script>
@endsection
