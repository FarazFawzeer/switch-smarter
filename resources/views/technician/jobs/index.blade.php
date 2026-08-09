@extends('layouts.technician', ['subtitle' => 'My Jobs'])

@section('content')
    @include('layouts.partials.page-title', ['title' => 'Dashboard', 'subtitle' => 'My Jobs'])

    <style>
        .unit-search-box { position: relative; margin-bottom: 14px; }
        .unit-search-box iconify-icon { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #8792a2; font-size: 18px; }
        .unit-search-box input { padding-left: 38px; }
        #jobListContainer { min-height: 200px; position: relative; }
        #jobListContainer.loading { opacity: 0.5; pointer-events: none; transition: opacity .15s ease; }
        .find-unit-banner {
            background: linear-gradient(135deg, #2E5AAC, #17A2B8); border-radius: 12px; padding: 16px;
            color: #fff; margin-bottom: 16px; display: flex; align-items: center; justify-content: space-between; gap: 10px;
        }
    </style>

    {{-- Quick stats --}}
    <div class="row g-2 mb-3">
        <div class="col-4">
            <div class="card text-center mb-0">
                <div class="card-body py-3">
                    <p class="text-muted small mb-1">Completed</p>
                    <p class="fw-bold mb-0 fs-22" style="color:#2E9E5B;">{{ $completedCount }}</p>
                </div>
            </div>
        </div>
        <div class="col-4">
            <div class="card text-center mb-0">
                <div class="card-body py-3">
                    <p class="text-muted small mb-1">Pending</p>
                    <p class="fw-bold mb-0 fs-22" style="color:#F0A202;">{{ $pendingCount }}</p>
                </div>
            </div>
        </div>
        <div class="col-4">
            <div class="card text-center mb-0">
                <div class="card-body py-3">
                    <p class="text-muted small mb-1">Overdue</p>
                    <p class="fw-bold mb-0 fs-22" style="color:#D64545;">{{ $overdueCount }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Today's attendance --}}
    <div class="card">
        <div class="card-body">
            <p class="text-muted small mb-2 fw-semibold">Today's Attendance</p>
            @if($todayAttendance && $todayAttendance->check_in_time)
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted small">Checked in</span>
                        <p class="mb-0 fw-semibold">{{ $todayAttendance->check_in_time->format('h:i A') }}</p>
                    </div>
                    <div>
                        <span class="text-muted small">Checked out</span>
                        <p class="mb-0 fw-semibold">{{ $todayAttendance->check_out_time?->format('h:i A') ?? '—' }}</p>
                    </div>
                    @if($todayAttendance->ot_hours > 0)
                        <span class="badge" style="background:#D64545;">+{{ $todayAttendance->ot_hours }} OT</span>
                    @endif
                </div>
            @else
                <p class="text-muted small mb-0">No attendance recorded yet today. Starting your first job will check you in.</p>
            @endif
        </div>
    </div>

    {{-- Quick access to Find Unit --}}
    <a href="{{ route('technician.jobs.find-unit') }}" class="text-decoration-none">
        <div class="find-unit-banner">
            <div>
                <div class="fw-bold">Know the unit ID?</div>
                <div class="small" style="opacity:0.9;">Search and jump straight to that job</div>
            </div>
            <iconify-icon icon="solar:qr-code-outline" style="font-size:28px;"></iconify-icon>
        </div>
    </a>

    <p class="fw-semibold mb-2">This Month's Jobs</p>

    {{-- Search by unit ID (within this month's list) --}}
    <div class="unit-search-box">
        <iconify-icon icon="solar:magnifer-outline"></iconify-icon>
        <input type="text" id="unitSearchInput" class="form-control" placeholder="Search by unit ID (e.g. LP-A-01)">
    </div>

    {{-- Job list — current month only, swapped via AJAX --}}
    <div id="jobListContainer">
        {{-- Initial load happens via JS on page ready --}}
    </div>

    @push('scripts')
    <script>
        const container = document.getElementById('jobListContainer');
        const searchInput = document.getElementById('unitSearchInput');
        let searchTimeout;

        function loadJobs(page = 1) {
            container.classList.add('loading');

            const params = new URLSearchParams({
                unit_search: searchInput.value,
                page: page,
            });

            fetch(`{{ route('technician.jobs.list') }}?${params.toString()}`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(response => response.text())
                .then(html => {
                    container.innerHTML = html;
                    container.classList.remove('loading');
                    attachPaginationHandlers();
                })
                .catch(() => {
                    container.classList.remove('loading');
                });
        }

        function attachPaginationHandlers() {
            container.querySelectorAll('.pagination a').forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const url = new URL(this.href);
                    const page = url.searchParams.get('page') || 1;
                    loadJobs(page);
                    window.scrollTo({ top: container.offsetTop - 20, behavior: 'smooth' });
                });
            });
        }

        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => loadJobs(1), 500);
        });

        loadJobs(1);
    </script>
    @endpush
@endsection