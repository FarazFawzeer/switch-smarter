@php
    $topUser = Auth::user();
    $topShutdown = \App\Models\ElevatorUnit::where('operational_status', 'shutdown')->count();
    $topLeave = \App\Models\LeaveRequest::where('status', 'pending')->count();
    $topOpenBreakdowns = \App\Models\JobRecord::whereNull('parent_job_id')->where('job_type', 'breakdown')
        ->whereIn('status', ['pending', 'in_progress', 'on_hold'])->count();
    // Stores (Rev 8)
    $topType = $topUser?->type;
    $topStoreOnly = $topType === 'Storekeeper';
    $topMrPending = in_array($topType, \App\Models\MaterialRequest::APPROVER_ROLES, true) ? \App\Models\MaterialRequest::where('status', 'pending')->count() : 0;
    $topMrToIssue = in_array($topType, \App\Models\MaterialRequest::ISSUER_ROLES, true) ? \App\Models\MaterialRequest::where('status', 'approved')->count() : 0;
    $topLowStock = in_array($topType, ['Storekeeper', 'Super Admin', 'Admin'], true) ? \App\Models\InventoryItem::lowStock()->count() : 0;
    $topAlerts = ($topStoreOnly ? 0 : $topShutdown + $topLeave) + $topMrPending + $topMrToIssue + $topLowStock;
    $topAvatar = user_avatar($topUser?->image_path);
@endphp

<header class="app-topbar ss-topbar">
    <div class="container-fluid">
        <div class="navbar-header">
            <div class="d-flex align-items-center gap-2 flex-grow-1">
                <div class="topbar-item">
                    <button type="button" class="button-toggle-menu topbar-button" aria-label="Toggle menu">
                        <x-ui-icon name="menu" size="22" />
                    </button>
                </div>

                <form class="ss-search d-none {{ $topStoreOnly ? '' : 'd-md-flex' }}" method="GET" action="{{ route('admin.contracts.index') }}" role="search">
                    <x-ui-icon name="search" size="18" />
                    <input type="search" name="search" placeholder="Search units, projects, job no…" autocomplete="off"
                        value="{{ request()->routeIs('admin.contracts.*') ? request('search') : '' }}">
                </form>
            </div>

            <div class="d-flex align-items-center gap-2">
                {{-- Light / dark --}}
                <div class="topbar-item">
                    <button type="button" class="topbar-button" id="light-dark-mode" aria-label="Switch light / dark mode">
                        <x-ui-icon name="moon" size="20" class="light-mode" />
                        <x-ui-icon name="sun" size="20" class="dark-mode" />
                    </button>
                </div>

                {{-- Notifications --}}
                <div class="dropdown topbar-item">
                    <button type="button" class="topbar-button position-relative" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Notifications">
                        <x-ui-icon name="bell" size="20" />
                        @if ($topAlerts > 0)
                            <span class="ss-bell-badge">{{ $topAlerts > 9 ? '9+' : $topAlerts }}</span>
                        @endif
                    </button>
                    <div class="dropdown-menu dropdown-menu-end ss-notify">
                        <div class="ss-notify-head">Notifications</div>
                        @if ($topMrPending > 0)
                            <a class="ss-notify-item" href="{{ route('admin.stores.requests.index', ['status' => 'pending']) }}">
                                <span class="ss-notify-icon" style="--c:#2E6BD8;"><x-ui-icon name="file-text" size="16" /></span>
                                <span><strong>{{ $topMrPending }}</strong> material request{{ $topMrPending === 1 ? '' : 's' }} to approve</span>
                            </a>
                        @endif
                        @if ($topMrToIssue > 0)
                            <a class="ss-notify-item" href="{{ route('admin.stores.requests.index', ['status' => 'approved']) }}">
                                <span class="ss-notify-icon" style="--c:#7A6FD0;"><x-ui-icon name="package" size="16" /></span>
                                <span><strong>{{ $topMrToIssue }}</strong> approved request{{ $topMrToIssue === 1 ? '' : 's' }} to issue</span>
                            </a>
                        @endif
                        @if ($topLowStock > 0)
                            <a class="ss-notify-item" href="{{ route('admin.stores.inventory.index', ['stock' => 'low']) }}">
                                <span class="ss-notify-icon" style="--c:#E08A00;"><x-ui-icon name="alert" size="16" /></span>
                                <span><strong>{{ $topLowStock }}</strong> item{{ $topLowStock === 1 ? '' : 's' }} at low stock</span>
                            </a>
                        @endif
                        @unless ($topStoreOnly)
                        <a class="ss-notify-item" href="{{ route('admin.shutdown-units.index') }}">
                            <span class="ss-notify-icon" style="--c:#D64545;"><x-ui-icon name="power" size="16" /></span>
                            <span><strong>{{ $topShutdown }}</strong> elevator{{ $topShutdown === 1 ? '' : 's' }} shut down</span>
                        </a>
                        <a class="ss-notify-item" href="{{ route('admin.job-types.breakdown') }}">
                            <span class="ss-notify-icon" style="--c:#7B4FD6;"><x-ui-icon name="alert" size="16" /></span>
                            <span><strong>{{ $topOpenBreakdowns }}</strong> open breakdown{{ $topOpenBreakdowns === 1 ? '' : 's' }}</span>
                        </a>
                        <a class="ss-notify-item" href="{{ route('admin.leave-requests.index') }}">
                            <span class="ss-notify-icon" style="--c:#E08A00;"><x-ui-icon name="calendar" size="16" /></span>
                            <span><strong>{{ $topLeave }}</strong> leave request{{ $topLeave === 1 ? '' : 's' }} awaiting approval</span>
                        </a>
                        @endunless
                    </div>
                </div>

                {{-- User --}}
                <div class="dropdown topbar-item">
                    <a class="ss-user" data-bs-toggle="dropdown" href="#" role="button" aria-expanded="false">
                        <img src="{{ $topAvatar }}" alt="" class="ss-user-avatar">
                        <span class="ss-user-meta d-none d-lg-flex">
                            <strong>{{ $topUser?->name }}</strong>
                            <span>{{ $topUser?->type }}</span>
                        </span>
                        <x-ui-icon name="chevron-down" size="16" class="d-none d-lg-inline" />
                    </a>
                    <div class="dropdown-menu dropdown-menu-end">
                        <h6 class="dropdown-header">Signed in as {{ $topUser?->name }}</h6>
                        <a class="dropdown-item d-flex align-items-center gap-2" href="{{ route('admin.profile.edit') }}">
                            <x-ui-icon name="user" size="16" /> My Profile
                        </a>
                        <div class="dropdown-divider my-1"></div>
                        <form method="POST" action="{{ route('logout') }}" class="m-0">
                            @csrf
                            <button type="submit" class="dropdown-item text-danger d-flex align-items-center gap-2">
                                <x-ui-icon name="logout" size="16" /> Logout
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>
