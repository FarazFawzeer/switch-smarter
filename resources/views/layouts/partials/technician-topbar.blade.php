<header class="app-topbar">
    <div class="container-fluid">
        <div class="navbar-header">
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('technician.jobs.index') }}" class="d-flex align-items-center">
                    <img src="/images/switch-smarter.png" alt="Switch Smarter" style="height: 36px;">
                </a>
            </div>

            <div class="d-flex align-items-center gap-2">
                <div class="topbar-item">
                    <button type="button" class="topbar-button" id="light-dark-mode">
                        <iconify-icon icon="solar:moon-outline" class="fs-22 align-middle light-mode"></iconify-icon>
                        <iconify-icon icon="solar:sun-2-outline" class="fs-22 align-middle dark-mode"></iconify-icon>
                    </button>
                </div>

                <div class="dropdown topbar-item">
                    <a type="button" class="topbar-button" id="page-header-user-dropdown" data-bs-toggle="dropdown"
                        aria-haspopup="true" aria-expanded="false">
                        <span class="d-flex align-items-center">
                            <img class="rounded-circle" width="32"
                                src="{{ Auth::user()?->image_path ? asset(Auth::user()->image_path) : asset('images/users/avatar-6.jpg') }}"
                                alt="avatar">
                        </span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end">
                        <h6 class="dropdown-header">{{ Auth::user()->name }}</h6>
                        <div class="dropdown-divider my-1"></div>
                        <form action="{{ route('logout') }}" method="POST" class="m-0">
                            @csrf
                            <button type="submit" class="dropdown-item text-danger">
                                <iconify-icon icon="solar:logout-3-outline" class="align-middle me-2 fs-18"></iconify-icon>
                                <span class="align-middle">Logout</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>

<div class="tech-bottomnav">
    <a href="{{ route('technician.jobs.index') }}" class="{{ request()->routeIs('technician.jobs.*') ? 'active' : '' }}">
        <iconify-icon icon="solar:clipboard-list-outline"></iconify-icon> My Jobs
    </a>
    <a href="{{ route('technician.timesheet') }}" class="{{ request()->routeIs('technician.timesheet') ? 'active' : '' }}">
        <iconify-icon icon="solar:clock-circle-outline"></iconify-icon> Timesheet
    </a>
</div>

<style>
    body { padding-bottom: 66px; }
    .tech-bottomnav {
        position: fixed; bottom: 0; left: 0; right: 0; background: var(--bs-body-bg, #fff);
        border-top: 1px solid var(--bs-border-color, #e7e9ee); display: flex; z-index: 1000;
    }
    .tech-bottomnav a { flex: 1; text-align: center; padding: 10px 0; color: #8792a2; text-decoration: none; font-size: 12px; }
    .tech-bottomnav a.active { color: var(--bs-primary, #2E5AAC); font-weight: 700; }
    .tech-bottomnav iconify-icon { font-size: 22px; display: block; margin: 0 auto 2px; }
</style>