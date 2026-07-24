@extends('layouts.vertical', ['subtitle' => $team->name])

@section('content')
    @include('layouts.partials.page-title', ['title' => 'Team', 'subtitle' => $team->name])

    <style>
        .tm-show-label { font-size: 12px; color: #8792a2; text-transform: uppercase; letter-spacing: 0.03em; margin-bottom: 2px; }
        .tm-show-value { font-size: 14px; color: #16233b; font-weight: 500; }
        .tm-show-card { border: none; border-radius: 14px; box-shadow: 0 1px 3px rgba(15,42,67,0.06); }
        .tm-role-pill { display: inline-block; padding: 4px 14px; border-radius: 6px; font-size: 12px; font-weight: 700; color: #fff; }
        .tm-report-row { display: flex; align-items: center; gap: 10px; padding: 10px 0; }
        .tm-report-row + .tm-report-row { border-top: 1px solid #f0f1f4; }
    </style>

    @php
        $roleColorMap = [
            'Manager' => '#2E5AAC', 'Engineer' => '#17A2B8', 'Supervisor' => '#F0A202', 'Technician' => '#2E9E5B',
        ];
        $roleColor = $roleColorMap[$team->type] ?? '#8792a2';
    @endphp

    <div class="row">
        <div class="col-md-4">
            {{-- Identity card --}}
            <div class="card tm-show-card">
                <div class="card-body text-center">
                    <img src="{{ storage_asset($team->image_path) ?? asset('/images/users/avatar-6.jpg') }}"
                        alt="{{ $team->name }}" class="rounded-circle mb-3" style="width: 96px; height: 96px; object-fit: cover;">
                    <h5 class="mb-1">{{ $team->name }}</h5>
                    <span class="tm-role-pill mb-3" style="background: {{ $roleColor }};">{{ $team->type }}</span>

                    <div class="text-start mt-3">
                        <div class="mb-2">
                            <p class="tm-show-label mb-0">Employee ID</p>
                            <p class="tm-show-value">{{ $team->employee_id ?? '—' }}</p>
                        </div>
                        <div class="mb-2">
                            <p class="tm-show-label mb-0">Email</p>
                            <p class="tm-show-value">{{ $team->email }}</p>
                        </div>
                        <div class="mb-0">
                            <p class="tm-show-label mb-0">Contact No</p>
                            <p class="tm-show-value">{{ $team->contact_no ?: '—' }}</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Reports To --}}
            <div class="card tm-show-card">
                <div class="card-header"><h6 class="card-title mb-0">Reports To</h6></div>
                <div class="card-body">
                    @if($team->type === 'Supervisor' && $team->engineer)
                        <div class="d-flex align-items-center gap-2">
                            <img src="{{ storage_asset($team->engineer->image_path) ?? asset('/images/users/avatar-6.jpg') }}"
                                class="avatar-sm rounded-circle">
                            <div>
                                <a href="{{ route('admin.team.show', $team->engineer->id) }}" class="fw-semibold text-dark d-block">{{ $team->engineer->name }}</a>
                                <span class="small text-muted">Engineer</span>
                            </div>
                        </div>
                    @elseif($team->type === 'Technician' && $team->supervisor)
                        <div class="d-flex align-items-center gap-2">
                            <img src="{{ storage_asset($team->supervisor->image_path) ?? asset('/images/users/avatar-6.jpg') }}"
                                class="avatar-sm rounded-circle">
                            <div>
                                <a href="{{ route('admin.team.show', $team->supervisor->id) }}" class="fw-semibold text-dark d-block">{{ $team->supervisor->name }}</a>
                                <span class="small text-muted">Supervisor</span>
                            </div>
                        </div>
                    @else
                        <p class="text-muted small mb-0">This is a top-level role — does not report to anyone in the system.</p>
                    @endif
                </div>
            </div>

            {{-- Routes --}}
            <div class="card tm-show-card">
                <div class="card-header"><h6 class="card-title mb-0">Routes</h6></div>
                <div class="card-body">
                    @forelse($team->routes as $route)
                        <span class="badge badge-soft-secondary me-1 mb-1">{{ $route->route_no }}</span>
                    @empty
                        <p class="text-muted small mb-0">No routes assigned yet.</p>
                    @endforelse
                </div>
            </div>

            <div class="d-flex gap-2">
                <a href="{{ route('admin.team.edit', $team->id) }}" class="btn btn-primary flex-fill">Edit Details</a>
                <a href="{{ route('admin.team.index') }}" class="btn btn-secondary flex-fill">Back to List</a>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card tm-show-card">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        @if($team->type === 'Engineer')
                            Supervisors Under {{ $team->name }}
                        @elseif($team->type === 'Supervisor')
                            Technicians Under {{ $team->name }}
                        @else
                            Organization
                        @endif
                    </h6>
                </div>
                <div class="card-body">
                    @if($team->type === 'Engineer')
                        @forelse($reportees as $supervisor)
                            <div class="tm-report-row justify-content-between">
                                <div class="d-flex align-items-center gap-2">
                                    <img src="{{ storage_asset($supervisor->image_path) ?? asset('/images/users/avatar-6.jpg') }}"
                                        class="avatar-sm rounded-circle">
                                    <div>
                                        <a href="{{ route('admin.team.show', $supervisor->id) }}" class="fw-semibold text-dark d-block">{{ $supervisor->name }}</a>
                                        <div class="small text-muted">
                                            {{ $supervisor->employee_id ?? '—' }}
                                            @foreach($supervisor->routes as $route)
                                                <span class="badge badge-soft-secondary ms-1">{{ $route->route_no }}</span>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                                <span class="badge badge-soft-secondary">{{ $supervisor->technicians_count }} technician(s)</span>
                            </div>
                        @empty
                            <p class="text-muted small mb-0">No supervisors assigned to this engineer yet.</p>
                        @endforelse

                    @elseif($team->type === 'Supervisor')
                        @forelse($reportees as $technician)
                            <div class="tm-report-row justify-content-between">
                                <div class="d-flex align-items-center gap-2">
                                    <img src="{{ storage_asset($technician->image_path) ?? asset('/images/users/avatar-6.jpg') }}"
                                        class="avatar-sm rounded-circle">
                                    <div>
                                        <a href="{{ route('admin.team.show', $technician->id) }}" class="fw-semibold text-dark d-block">{{ $technician->name }}</a>
                                        <div class="small text-muted">
                                            {{ $technician->employee_id ?? '—' }} · {{ $technician->email }}
                                            @if($technician->routes->first())
                                                <span class="badge badge-soft-secondary ms-1">{{ $technician->routes->first()->route_no }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <p class="text-muted small mb-0">No technicians assigned to this supervisor yet.</p>
                        @endforelse

                    @else
                        <p class="text-muted small mb-0">
                            {{ $team->type === 'Technician' ? 'Technicians do not manage other team members.' : 'This role oversees the whole organization.' }}
                        </p>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection