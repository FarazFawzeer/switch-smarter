<header class="app-topbar technician-topbar">
    <div class="container-fluid">
        <div class="navbar-header">

            {{-- Left: Logo --}}
            <a href="{{ route('technician.jobs.index') }}" class="technician-logo d-flex align-items-center text-decoration-none">
                <img src="{{ asset('images/switch-logo-light.png') }}" alt="Switch Smarter" class="switch-logo">
                <div class="technician-app-name">
                    <span>Technician App</span>
                </div>
            </a>

            {{-- Right: Notification --}}
            <div class="d-flex align-items-center">
                <div class="topbar-item">
                    <button type="button" class="topbar-button notification-button" aria-label="Notifications">
                        <x-ui-icon name="bell" />
                    </button>
                </div>
            </div>

        </div>
    </div>
</header>

{{-- Bottom Navigation --}}
<div class="tech-bottomnav">
    <a href="{{ route('technician.jobs.index') }}" class="{{ request()->routeIs('technician.jobs.index') ? 'active' : '' }}">
        <x-ui-icon name="grid" />
        <span>Dashboard</span>
    </a>
    <a href="{{ route('technician.jobs.browse') }}" class="{{ request()->routeIs('technician.jobs.browse') ? 'active' : '' }}">
        <x-ui-icon name="clipboard" />
        <span>Jobs</span>
    </a>
    <a href="{{ route('technician.schedule') }}" class="{{ request()->routeIs('technician.schedule') ? 'active' : '' }}">
        <x-ui-icon name="calendar" />
        <span>Schedule</span>
    </a>
    <a href="{{ route('technician.leave.index') }}" class="{{ request()->routeIs('technician.leave.*') ? 'active' : '' }}">
        <x-ui-icon name="file-text" />
        <span>Leave</span>
    </a>
    <a href="{{ route('technician.profile') }}" class="{{ request()->routeIs('technician.profile') ? 'active' : '' }}">
        <x-ui-icon name="user" />
        <span>Profile</span>
    </a>
</div>

<style>
    :root {
        --tech-navy: #071A3D;
        --tech-navy-light: #0F2A5C;
    }

    body {
        padding-bottom: 66px;
        background: #F5F7FA;
    }

    /* =========================
       TOP HEADER
    ========================= */
    .technician-topbar {
        background: var(--tech-navy);
        min-height: 64px;
        box-shadow: 0 1px 4px rgba(15, 42, 67, 0.15);
    }

    .technician-topbar .navbar-header {
        min-height: 64px;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    /* Logo */
    .technician-logo {
        display: flex;
        align-items: center;
    }

    .switch-logo-badge {
        background: #fff;
        border-radius: 8px;
        padding: 5px 10px;
        display: flex;
        align-items: center;
        box-shadow: 0 1px 3px rgba(0,0,0,0.15);
    }

    .switch-logo {
        height: 30px;
        width: auto;
        object-fit: contain;
        display: block;
    }

    .technician-app-name {
        color: #AEBBD0;
        font-size: 11px;
        font-weight: 500;
        margin-left: 10px;
        letter-spacing: 0.2px;
    }

    /* Notification */
    .notification-button {
        width: 50px !important;
        height: 50px !important;
        padding: 0;
        border: 0;
        background: transparent !important;
        color: #ffffff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 22px;
    }

    .notification-button:hover,
    .notification-button:focus {
        background: rgba(255,255,255,0.08);
        color: #ffffff;
        box-shadow: none;
        border-radius: 50%;
    }

    /* =========================
       BOTTOM NAVIGATION
    ========================= */
    .tech-bottomnav {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        height: 66px;
        background: var(--tech-navy) !important;
        border-top: 1px solid rgba(255,255,255,0.06);
        display: flex;
        z-index: 1000;
    }

    .tech-bottomnav a {
        flex: 1;
        text-align: center;
        padding: 9px 0 6px;
        color: #AEBBD0 !important;
        text-decoration: none;
        font-size: 11px;
        line-height: 1.2;
        transition: color .15s ease;
    }

    .tech-bottomnav a.active {
        color: #FFFFFF !important;
        font-weight: 700;
    }

    
    .tech-bottomnav .ui-icon {
        width: 22px; height: 22px;
        display: block;
        margin: 0 auto 3px;
    }

    .tech-bottomnav span {
        display: block;
    }

    /* =========================
       MOBILE
    ========================= */
    @media (max-width: 767.98px) {
        .technician-topbar { min-height: 60px; }
        .technician-topbar .navbar-header { min-height: 60px; }
        .switch-logo { height: 28px; }
        .technician-app-name { font-size: 10px; }
    }
</style>