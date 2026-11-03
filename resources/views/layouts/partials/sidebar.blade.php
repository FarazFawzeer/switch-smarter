@php
    $authType = auth()->user()?->type;
    $isAdmin = in_array($authType, ['Super Admin', 'Admin']);
    $isManager = in_array($authType, ['Super Admin', 'Admin', 'Manager']);

    $sidebarShutdownCount = \App\Models\ElevatorUnit::where('operational_status', 'shutdown')->count();
    $sidebarPendingLeave = \App\Models\LeaveRequest::where('status', 'pending')->count();

    $timesOpen = request()->routeIs('admin.tracking.*', 'admin.timesheet-approval.*', 'admin.leave-requests.*', 'admin.holidays.*', 'admin.attendance.*');

    // [route name, label, icon, active patterns]
    $menu = [
        ['dashboard', 'Dashboard', 'home', ['dashboard']],
        $isAdmin ? ['admin.users.index', 'Admin', 'shield', ['admin.users.*']] : null,
        ['admin.team.index', 'Team', 'users', ['admin.team.*']],
        ['admin.contracts.index', 'Master List', 'database', ['admin.contracts.*']],
        ['admin.scheduling.index', 'Scheduling', 'calendar', ['admin.scheduling.*']],
        ['admin.monthly-maintenance.index', 'Preventive Maintenance', 'clipboard', ['admin.monthly-maintenance.*']],
        ['admin.job-types.breakdown', 'Breakdown', 'alert', ['admin.job-types.breakdown']],
        ['admin.shutdown-units.index', 'Shutdown Units', 'power', ['admin.shutdown-units.*'], $sidebarShutdownCount],
        ['admin.job-types.repair', 'Repair', 'wrench', ['admin.job-types.repair']],
        ['admin.job-types.casual-job', 'Casual Jobs', 'briefcase', ['admin.job-types.casual-job*']],
    ];
@endphp

<div class="app-sidebar ss-sidebar">
    {{-- Brand --}}
    <div class="logo-box ss-logo">
        <a href="{{ route('dashboard') }}" class="ss-logo-link">
            <img src="{{ asset('images/switch-logo-light.png') }}" class="logo-lg ss-logo-full" alt="Switch Smarter">
            <img src="{{ asset('images/switch-mark.png') }}" class="logo-sm ss-logo-mark" alt="Switch">
        </a>
    </div>

    <div class="scrollbar" data-simplebar>
        <ul class="navbar-nav" id="navbar-nav">

            @foreach (array_filter($menu) as $item)
                @php
                    [$routeName, $label, $icon, $patterns] = $item;
                    $badge = $item[4] ?? 0;
                    $active = request()->routeIs(...$patterns);
                @endphp
                <li class="nav-item">
                    <a class="nav-link {{ $active ? 'active' : '' }}" href="{{ route($routeName) }}">
                        <span class="nav-icon"><x-ui-icon :name="$icon" size="20" /></span>
                        <span class="nav-text">{{ $label }}</span>
                        @if ($badge > 0)
                            <span class="badge bg-danger rounded-pill">{{ $badge }}</span>
                        @endif
                    </a>
                </li>
            @endforeach

            <li class="nav-item">
                <a class="nav-link menu-arrow {{ $timesOpen ? 'active' : '' }}" href="#sidebarTimesheets" data-bs-toggle="collapse"
                    role="button" aria-expanded="{{ $timesOpen ? 'true' : 'false' }}" aria-controls="sidebarTimesheets">
                    <span class="nav-icon"><x-ui-icon name="clock" size="20" /></span>
                    <span class="nav-text">Timesheets</span>
                    @if ($sidebarPendingLeave > 0)
                        <span class="badge bg-warning text-dark rounded-pill me-3" title="Pending leave requests">{{ $sidebarPendingLeave }}</span>
                    @endif
                </a>
                <div class="collapse {{ $timesOpen ? 'show' : '' }}" id="sidebarTimesheets">
                    <ul class="nav sub-navbar-nav">
                        <li class="sub-nav-item">
                            <a class="sub-nav-link {{ request()->routeIs('admin.tracking.*') ? 'active' : '' }}" href="{{ route('admin.tracking.index') }}">Sheets</a>
                        </li>
                        <li class="sub-nav-item">
                            <a class="sub-nav-link {{ request()->routeIs('admin.timesheet-approval.*') ? 'active' : '' }}" href="{{ route('admin.timesheet-approval.index') }}">Approval</a>
                        </li>
                        <li class="sub-nav-item">
                            <a class="sub-nav-link {{ request()->routeIs('admin.leave-requests.*') ? 'active' : '' }}" href="{{ route('admin.leave-requests.index') }}">
                                Leave Requests
                                @if ($sidebarPendingLeave > 0)
                                    <span class="badge bg-warning text-dark rounded-pill">{{ $sidebarPendingLeave }}</span>
                                @endif
                            </a>
                        </li>
                        @if ($isManager)
                            <li class="sub-nav-item">
                                <a class="sub-nav-link {{ request()->routeIs('admin.holidays.*') ? 'active' : '' }}" href="{{ route('admin.holidays.index') }}">Holidays</a>
                            </li>
                        @endif
                    </ul>
                </div>
            </li>

            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.profile.*') ? 'active' : '' }}" href="{{ route('admin.profile.edit') }}">
                    <span class="nav-icon"><x-ui-icon name="user" size="20" /></span>
                    <span class="nav-text">Profile</span>
                </a>
            </li>
        </ul>

        {{-- Brand card --}}
        <div class="ss-brand-card">
            <img src="{{ asset('images/switch-mark.png') }}" alt="" class="ss-brand-logo">
            <div class="ss-brand-text">
                <strong>SWITCH CMMS</strong>
                <span>Smarter Maintenance</span>
                <span>Better Operations</span>
            </div>
        </div>
    </div>

    {{-- decorative waves (as in the reference design) --}}
    <svg class="ss-waves" viewBox="0 0 250 120" preserveAspectRatio="none" aria-hidden="true">
        <path d="M0 70 C 60 30, 120 110, 250 50" />
        <path d="M0 85 C 70 45, 130 120, 250 65" />
        <path d="M0 100 C 80 60, 140 125, 250 80" />
        <path d="M0 112 C 90 75, 150 128, 250 95" />
    </svg>
</div>
