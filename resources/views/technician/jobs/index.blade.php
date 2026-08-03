@extends('layouts.technician', ['subtitle' => 'My Jobs'])

@section('content')
    @include('layouts.partials.page-title', ['title' => 'Dashboard', 'subtitle' => 'My Jobs'])

    <style>
        .tech-tab-nav { display: flex; gap: 6px; background: #F5F7FA; border-radius: 10px; padding: 4px; margin-bottom: 14px; }
        .tech-tab-nav button { flex: 1; text-align: center; padding: 8px 0; border-radius: 8px; font-size: 13px; font-weight: 600; color: #6b7280; background: none; border: none; }
        .tech-tab-nav button.active { background: #fff; color: #2E5AAC; box-shadow: 0 1px 3px rgba(15,42,67,0.08); }
        .unit-search-box { position: relative; margin-bottom: 14px; }
        .unit-search-box iconify-icon { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #8792a2; font-size: 18px; }
        .unit-search-box input { padding-left: 38px; }
        #jobListContainer { min-height: 200px; position: relative; }
        #jobListContainer.loading { opacity: 0.5; pointer-events: none; transition: opacity .15s ease; }
    </style>

    {{-- Quick stats (loaded once, stays static) --}}
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

    {{-- Today's attendance (loaded once, stays static) --}}
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

    {{-- Search by unit ID --}}
    <div class="unit-search-box">
        <iconify-icon icon="solar:magnifer-outline"></iconify-icon>
        <input type="text" id="unitSearchInput" class="form-control" placeholder="Search by unit ID (e.g. LP-A-01)">
    </div>

    {{-- Previous / Current / Upcoming tabs --}}
    <div class="tech-tab-nav">
        <button type="button" class="period-tab active" data-period="previous">Previous Month</button>
        <button type="button" class="period-tab" data-period="current">This Month</button>
        <button type="button" class="period-tab" data-period="upcoming">Upcoming</button>
    </div>

    {{-- Job list — swapped via AJAX, rest of the page stays put --}}
    <div id="jobListContainer">
        {{-- Initial load happens via JS on page ready --}}
    </div>

    @push('scripts')
    <script>
        const container = document.getElementById('jobListContainer');
        const searchInput = document.getElementById('unitSearchInput');
        const tabs = document.querySelectorAll('.period-tab');
        let currentPeriod = 'current';
        let searchTimeout;

        function loadJobs(page = 1) {
            container.classList.add('loading');

            const params = new URLSearchParams({
                period: currentPeriod,
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

        // Intercept pagination link clicks so they also load via AJAX, not full page reload
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

        tabs.forEach(tab => {
            tab.addEventListener('click', function() {
                tabs.forEach(t => t.classList.remove('active'));
                this.classList.add('active');
                currentPeriod = this.dataset.period;
                loadJobs(1);
            });
        });

        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => loadJobs(1), 500);
        });

        // Set "This Month" as the visually active tab and load it first
        document.querySelector('.period-tab[data-period="current"]').click();
    </script>
    @endpush
@endsection