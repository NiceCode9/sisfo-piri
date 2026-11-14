@use('App\Support\RuteBeranda')

@php
    $sidebarUser = auth()->user();
    $sidebarName = $sidebarUser?->name ?? 'Guest';
    $sidebarRole = $sidebarUser?->getRoleNames()->first() ?? 'Tamu';
    $sidebarInitial = strtoupper(mb_substr($sidebarName, 0, 1));

    // Link brand harus ikut ke dashboard yang sama dengan dispatcher `/admin`.
    // Keduanya lewat RuteBeranda supaya tidak bisa berbeda.
    $sidebarBeranda = RuteBeranda::untuk($sidebarUser);

    // Logo & nama sekolah dari profil (fallback ke default Nexus).
    $sidebarSekolah = $profileSekolah ?? null;
    $sidebarLogo = !empty($sidebarSekolah?->logo_path)
        ? \Illuminate\Support\Facades\Storage::disk('public')->url($sidebarSekolah->logo_path)
        : null;
    $sidebarNamaSekolah = trim((string) ($sidebarSekolah?->nama_sekolah ?? ''));
    if ($sidebarNamaSekolah === '' || preg_match('/^\s*(GANTI|TODO|ISI|DUMMY)\b/i', $sidebarNamaSekolah)) {
        $sidebarNamaSekolah = 'Nexus';
    }
@endphp

<!-- SIDEBAR -->
<aside id="sidebar" class="sidebar" aria-label="Main Navigation">
    <a href="{{ route($sidebarBeranda) }}" class="sidebar-brand text-decoration-none">
        @if ($sidebarLogo)
            <img src="{{ $sidebarLogo }}" alt="Logo {{ $sidebarNamaSekolah }}" class="brand-logo-img" style="width:32px;height:32px;object-fit:contain;border-radius:6px;">
        @else
            <div class="brand-logo"><i class="fa-solid fa-hexagon"></i></div>
        @endif
        <div class="brand-text">
            <span class="brand-name">{{ $sidebarNamaSekolah }}</span>
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
            @if ($sidebarLogo)
                <img src="{{ $sidebarLogo }}" alt="Logo {{ $sidebarNamaSekolah }}" style="width:28px;height:28px;object-fit:contain;border-radius:6px;">
            @else
                <div class="brand-logo"><i class="fa-solid fa-hexagon"></i></div>
            @endif
            <span class="brand-name text-white fw-bold">{{ $sidebarNamaSekolah }}</span>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body p-0">
        <ul class="sidebar-nav">
            @include('layouts.partials.menu-items', ['menus' => $adminMenus ?? collect(), 'idPrefix' => 'm-'])
        </ul>
    </div>
</div>
