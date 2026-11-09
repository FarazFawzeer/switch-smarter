@php
    use App\Models\MaterialRequest;

    $authType = auth()->user()?->type;
    $isAdmin = in_array($authType, ['Super Admin', 'Admin'], true);
    $isManager = in_array($authType, ['Super Admin', 'Admin', 'Manager'], true);
    $isStorekeeper = $authType === 'Storekeeper';

    // ---- badges ----
    $shutdownCount = $isStorekeeper ? 0 : \App\Models\ElevatorUnit::where('operational_status', 'shutdown')->count();
    $pendingLeave = $isStorekeeper ? 0 : \App\Models\LeaveRequest::where('status', 'pending')->count();

    $canStores = in_array($authType, MaterialRequest::STORE_ROLES, true);
    $canRequestMaterial = in_array($authType, MaterialRequest::REQUESTER_ROLES, true);
    $storeBadge = 0;
    if (in_array($authType, MaterialRequest::APPROVER_ROLES, true)) {
        $storeBadge += MaterialRequest::where('status', 'pending')->count();
    }
    if (in_array($authType, MaterialRequest::ISSUER_ROLES, true)) {
        $storeBadge += MaterialRequest::where('status', 'approved')->count();
    }
    $lowStock = $canStores ? \App\Models\InventoryItem::lowStock()->count() : 0;

    /*
     | Menu structure: sections → items. An item is a link, or a group with children.
     | link:  ['label', 'icon', 'route', [active patterns], badge?, badgeClass?]
     | group: ['label', 'icon', 'children' => [[label, route, [patterns], badge?, badgeClass?], ...], 'badge' => n]
     */
    $sections = [];

    if (! $isStorekeeper) {
        $sections[] = ['title' => null, 'items' => [
            ['label' => 'Dashboard', 'icon' => 'home', 'route' => 'dashboard', 'active' => ['dashboard']],
        ]];

        $sections[] = ['title' => 'Operations', 'items' => [
            ['label' => 'Maintenance', 'icon' => 'calendar', 'id' => 'navMaintenance', 'children' => [
                ['Scheduling', 'admin.scheduling.index', ['admin.scheduling.*']],
                ['Preventive Maintenance', 'admin.monthly-maintenance.index', ['admin.monthly-maintenance.*']],
            ]],
            ['label' => 'Work Orders', 'icon' => 'wrench', 'id' => 'navWorkOrders', 'children' => [
                ['Breakdown', 'admin.job-types.breakdown', ['admin.job-types.breakdown']],
                ['Repair', 'admin.job-types.repair', ['admin.job-types.repair']],
                ['Casual Jobs', 'admin.job-types.casual-job', ['admin.job-types.casual-job*']],
            ]],
            ['label' => 'Shutdown Units', 'icon' => 'power', 'route' => 'admin.shutdown-units.index', 'active' => ['admin.shutdown-units.*'],
                'badge' => $shutdownCount, 'badgeClass' => 'bg-danger'],
        ]];

        $sections[] = ['title' => 'Assets', 'items' => [
            ['label' => 'Master List', 'icon' => 'database', 'route' => 'admin.contracts.index', 'active' => ['admin.contracts.*']],
        ]];

        $sections[] = ['title' => 'Workforce', 'items' => [
            ['label' => 'Team', 'icon' => 'users', 'route' => 'admin.team.index', 'active' => ['admin.team.*']],
            ['label' => 'Timesheets', 'icon' => 'clock', 'id' => 'navTimesheets', 'badge' => $pendingLeave, 'children' => [
                ['Sheets', 'admin.tracking.index', ['admin.tracking.*']],
                ['Approval', 'admin.timesheet-approval.index', ['admin.timesheet-approval.*']],
                ['Idle / Travel Time', 'admin.idle-time.index', ['admin.idle-time.*']],
                ['Leave Requests', 'admin.leave-requests.index', ['admin.leave-requests.*'], $pendingLeave],
            ]],
        ]];
    }

    if ($canStores || $canRequestMaterial) {
        $storeChildren = [['Material Requests', 'admin.stores.requests.index', ['admin.stores.requests.*'], $storeBadge]];
        if ($canStores) {
            $storeChildren[] = ['Inventory', 'admin.stores.inventory.index', ['admin.stores.inventory.*'], $lowStock, 'bg-danger'];
            $storeChildren[] = ['Analytics', 'admin.stores.analytics', ['admin.stores.analytics']];
        }
        $sections[] = ['title' => 'Inventory', 'items' => [
            ['label' => 'Stores', 'icon' => 'package', 'id' => 'navStores', 'badge' => $storeBadge, 'open' => $isStorekeeper, 'children' => $storeChildren],
        ]];
    }

    if ($isManager) {
        $adminChildren = [];
        if ($isAdmin) {
            $adminChildren[] = ['Users & Access', 'admin.users.index', ['admin.users.*']];
        }
        $adminChildren[] = ['Holidays', 'admin.holidays.index', ['admin.holidays.*']];
        $sections[] = ['title' => 'Administration', 'items' => [
            ['label' => 'Settings', 'icon' => 'settings', 'id' => 'navSettings', 'children' => $adminChildren],
        ]];
    }

    $isActive = fn(array $patterns) => request()->routeIs(...$patterns);
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
            @foreach ($sections as $section)
                @if ($section['title'])
                    <li class="menu-title ss-menu-title">{{ $section['title'] }}</li>
                @endif

                @foreach ($section['items'] as $item)
                    @if (isset($item['children']))
                        @php
                            $groupActive = collect($item['children'])->contains(fn($c) => $isActive($c[2]));
                            $open = $groupActive || ($item['open'] ?? false);
                        @endphp
                        <li class="nav-item">
                            <a class="nav-link menu-arrow {{ $groupActive ? 'active' : '' }}" href="#{{ $item['id'] }}" data-bs-toggle="collapse"
                                role="button" aria-expanded="{{ $open ? 'true' : 'false' }}" aria-controls="{{ $item['id'] }}">
                                <span class="nav-icon"><x-ui-icon :name="$item['icon']" size="20" /></span>
                                <span class="nav-text">{{ $item['label'] }}</span>
                                @if (($item['badge'] ?? 0) > 0)
                                    <span class="badge bg-warning text-dark rounded-pill ss-nav-badge">{{ $item['badge'] }}</span>
                                @endif
                            </a>
                            <div class="collapse {{ $open ? 'show' : '' }}" id="{{ $item['id'] }}">
                                <ul class="nav sub-navbar-nav">
                                    @foreach ($item['children'] as $child)
                                        @php [$cLabel, $cRoute, $cPatterns] = $child; $cBadge = $child[3] ?? 0; $cBadgeClass = $child[4] ?? 'bg-warning text-dark'; @endphp
                                        <li class="sub-nav-item">
                                            <a class="sub-nav-link {{ $isActive($cPatterns) ? 'active' : '' }}" href="{{ route($cRoute) }}">
                                                <span>{{ $cLabel }}</span>
                                                @if ($cBadge > 0)<span class="badge {{ $cBadgeClass }} rounded-pill">{{ $cBadge }}</span>@endif
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </li>
                    @else
                        <li class="nav-item">
                            <a class="nav-link {{ $isActive($item['active']) ? 'active' : '' }}" href="{{ route($item['route']) }}">
                                <span class="nav-icon"><x-ui-icon :name="$item['icon']" size="20" /></span>
                                <span class="nav-text">{{ $item['label'] }}</span>
                                @if (($item['badge'] ?? 0) > 0)
                                    <span class="badge {{ $item['badgeClass'] ?? 'bg-danger' }} rounded-pill ss-nav-badge">{{ $item['badge'] }}</span>
                                @endif
                            </a>
                        </li>
                    @endif
                @endforeach
            @endforeach
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
