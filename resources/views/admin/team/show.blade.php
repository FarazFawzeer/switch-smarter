@extends('layouts.vertical', ['subtitle' => $team->name])

@section('content')
    @include('layouts.partials.page-title', ['title' => 'Team', 'subtitle' => $team->name])

    @php
        $roleColor = match($team->type) {
            'Manager' => 'primary', 'Engineer' => 'info', 'Supervisor' => 'warning', 'Technician' => 'success', default => 'secondary',
        };
    @endphp

    <div class="row">
        <div class="col-md-4">
            <div class="card">
                <div class="card-body text-center">
                    <img src="{{ storage_asset($team->image_path) ?? asset('/images/users/avatar-6.jpg') }}"
                        alt="{{ $team->name }}" class="rounded-circle mb-3" style="width: 96px; height: 96px; object-fit: cover;">
                    <h5 class="mb-1">{{ $team->name }}</h5>
                    <span class="badge badge-soft-{{ $roleColor }} mb-3">{{ $team->type }}</span>
                    <p class="text-muted small mb-0">
                        <iconify-icon icon="solar:letter-outline"></iconify-icon> {{ $team->email }}
                    </p>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h6 class="card-title mb-0">Reports To</h6></div>
                <div class="card-body">
                    @if($team->type === 'Supervisor' && $team->engineer)
                        <div class="d-flex align-items-center gap-2">
                            <img src="{{ storage_asset($team->engineer->image_path) ?? asset('/images/users/avatar-6.jpg') }}"
                                class="avatar-sm rounded-circle">
                            <div>
                                <p class="mb-0 fw-semibold">{{ $team->engineer->name }}</p>
                                <span class="small text-muted">Engineer</span>
                            </div>
                        </div>
                    @elseif($team->type === 'Technician' && $team->supervisor)
                        <div class="d-flex align-items-center gap-2">
                            <img src="{{ storage_asset($team->supervisor->image_path) ?? asset('/images/users/avatar-6.jpg') }}"
                                class="avatar-sm rounded-circle">
                            <div>
                                <p class="mb-0 fw-semibold">{{ $team->supervisor->name }}</p>
                                <span class="small text-muted">Supervisor</span>
                            </div>
                        </div>
                    @else
                        <p class="text-muted small mb-0">This is a top-level role — does not report to anyone in the system.</p>
                    @endif
                </div>
            </div>

            <a href="{{ route('admin.team.index') }}" class="btn btn-secondary w-100">Back to Team List</a>
        </div>

        <div class="col-md-8">
            <div class="card">
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
                            <div class="d-flex justify-content-between align-items-center py-2 {{ !$loop->last ? 'border-bottom' : '' }}">
                                <div class="d-flex align-items-center gap-2">
                                    <img src="{{ storage_asset($supervisor->image_path) ?? asset('/images/users/avatar-6.jpg') }}"
                                        class="avatar-sm rounded-circle">
                                    <div>
                                        <a href="{{ route('admin.team.show', $supervisor->id) }}" class="fw-semibold text-dark">{{ $supervisor->name }}</a>
                                        <div class="small text-muted">Supervisor</div>
                                    </div>
                                </div>
                                <span class="badge badge-soft-secondary">{{ $supervisor->technicians_count }} technician(s)</span>
                            </div>
                        @empty
                            <p class="text-muted small mb-0">No supervisors assigned to this engineer yet.</p>
                        @endforelse

                    @elseif($team->type === 'Supervisor')
                        @forelse($reportees as $technician)
                            <div class="d-flex justify-content-between align-items-center py-2 {{ !$loop->last ? 'border-bottom' : '' }}">
                                <div class="d-flex align-items-center gap-2">
                                    <img src="{{ storage_asset($technician->image_path) ?? asset('/images/users/avatar-6.jpg') }}"
                                        class="avatar-sm rounded-circle">
                                    <div>
                                        <a href="{{ route('admin.team.show', $technician->id) }}" class="fw-semibold text-dark">{{ $technician->name }}</a>
                                        <div class="small text-muted">Technician</div>
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