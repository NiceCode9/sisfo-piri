@php
    $sidebarUser = auth()->user();
    $sidebarName = $sidebarUser?->name ?? 'Guest';
    $sidebarRole = $sidebarUser?->getRoleNames()->first() ?? 'Tamu';
    $sidebarInitial = strtoupper(mb_substr($sidebarName, 0, 1));
@endphp

<!-- SIDEBAR -->
<aside id="sidebar" class="sidebar" aria-label="Main Navigation">
    <a href="{{ route('admin.dashboard') }}" class="sidebar-brand text-decoration-none">
        <div class="brand-logo"><i class="fa-solid fa-hexagon"></i></div>
        <div class="brand-text">
            <span class="brand-name">Nexus</span>
            <span class="brand-tagline">Admin Dashboard</span>
        </div>
    </a>

    <ul class="sidebar-nav mt-2">
        @include('layouts.partials.menu-items', ['menus' => $adminMenus ?? collect(), 'idPrefix' => 'd-'])
    </ul>

    <div class="sidebar-footer">
        <div class="dropdown">
            <a href="#" class="sidebar-user dropdown-toggle" id="sidebarUserMenu" data-bs-toggle="dropdown" aria-expanded="false">
                <div class="avatar-wrapper">
                    <div class="avatar">{{ $sidebarInitial }}</div>
                    <span class="avatar-status status-online"></span>
                </div>
                <div class="user-info">
                    <div class="user-name">{{ $sidebarName }}</div>
                    <div class="user-role">{{ $sidebarRole }}</div>
                </div>
                <i class="fa-solid fa-ellipsis-vertical ms-auto text-muted" style="font-size: 13px"></i>
            </a>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="sidebarUserMenu">
                <li class="dropdown-header">My Account</li>
                <li><a class="dropdown-item" href="#"><i class="fa-solid fa-user"></i> Profile</a></li>
                <li><a class="dropdown-item" href="#"><i class="fa-solid fa-gear"></i> Preferences</a></li>
                <li><hr class="dropdown-divider" /></li>
                <li><a class="dropdown-item text-danger" href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();"><i class="fa-solid fa-right-from-bracket"></i> Sign Out</a></li>
            </ul>
        </div>
    </div>
</aside>

<!-- MOBILE SIDEBAR -->
<div class="offcanvas offcanvas-start" tabindex="-1" id="mobileSidebar" aria-labelledby="mobileSidebarLabel">
    <div class="offcanvas-header border-bottom" style="border-color: rgba(255,255,255,0.05) !important; padding: 18px 20px;">
        <div class="d-flex align-items-center gap-2">
            <div class="brand-logo"><i class="fa-solid fa-hexagon"></i></div>
            <span class="brand-name text-white fw-bold">Nexus Admin</span>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body p-0">
        <ul class="sidebar-nav">
            @include('layouts.partials.menu-items', ['menus' => $adminMenus ?? collect(), 'idPrefix' => 'm-'])
        </ul>
    </div>
</div>
