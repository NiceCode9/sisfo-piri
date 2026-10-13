@php
    $topbarUser = auth()->user();
    $topbarName = $topbarUser?->name ?? 'Guest';
    $topbarEmail = $topbarUser?->email ?? 'guest@example.com';
    $topbarRole = $topbarUser?->getRoleNames()->first() ?? 'Tamu';
    $topbarInitial = strtoupper(mb_substr($topbarName, 0, 1));
@endphp

<!-- TOP NAVBAR -->
<nav class="top-navbar" aria-label="Top Navigation">
    <button class="sidebar-toggle-btn d-none d-lg-flex" id="sidebarToggleBtn" aria-label="Toggle Sidebar">
        <i class="fa-solid fa-outdent"></i>
    </button>
    <button class="sidebar-toggle-btn d-flex d-lg-none" id="mobileSidebarToggle" data-bs-toggle="offcanvas" data-bs-target="#mobileSidebar" aria-label="Open Sidebar">
        <i class="fa-solid fa-bars"></i>
    </button>

    <nav aria-label="breadcrumb" class="d-none d-md-flex">
        <ol class="breadcrumb mb-0" style="font-size: 12.5px">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-decoration-none" style="color: var(--text-muted)">Home</a></li>
            <li class="breadcrumb-item active" style="color: var(--text-secondary)">@yield('breadcrumb', 'Dashboard')</li>
        </ol>
    </nav>

    <div class="navbar-search d-none d-lg-block">
        <i class="fa-solid fa-magnifying-glass search-icon"></i>
        <input type="text" id="globalSearch" class="form-control" placeholder="Search anything..." aria-label="Global Search" />
        <span class="kbd-hint">⌘K</span>
    </div>

    <div class="navbar-actions">
        <div class="dark-toggle" data-theme-toggle role="button" aria-label="Toggle dark mode" tabindex="0">
            <div class="toggle-icon toggle-light active"><i class="fa-solid fa-sun"></i></div>
            <div class="toggle-icon toggle-dark"><i class="fa-solid fa-moon"></i></div>
        </div>

        <div class="dropdown">
            <button class="nav-action-btn" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Notifications">
                <i class="fa-solid fa-bell"></i>
                <span class="badge-dot"></span>
            </button>
            <div class="dropdown-menu dropdown-menu-end notif-dropdown p-0">
                <div class="d-flex align-items-center justify-content-between px-3 py-2 border-bottom" style="border-color: var(--border-color) !important">
                    <span class="fw-bold" style="font-size: 14px; color: var(--text-primary)">Notifikasi</span>
                </div>
                {{-- Belum ada sumber notifikasi di dalam aplikasi. Versi lama
                     menampilkan empat notifikasi contoh berbahasa Inggris
                     ("Sarah Johnson joined your platform", "Order #4821 has
                     been shipped") yang muncul di SETIAP halaman untuk semua
                     pengguna. Lebih baik kosong dan jujur daripada data karangan
                     yang terbaca seperti kenyataan. --}}
                <div class="text-center px-3 py-4">
                    <i class="fa-solid fa-bell-slash fa-2x text-muted mb-2"></i>
                    <div style="font-size: 13px; color: var(--text-secondary)">Belum ada notifikasi</div>
                </div>
            </div>
        </div>

        <button class="nav-action-btn d-none d-sm-flex" aria-label="Messages"><i class="fa-solid fa-comment-dots"></i></button>

        <div class="dropdown">
            <div class="navbar-user" data-bs-toggle="dropdown" aria-expanded="false" role="button" aria-label="User menu">
                <div class="user-meta d-none d-lg-block">
                    <div class="u-name">{{ $topbarName }}</div>
                    <div class="u-role">{{ $topbarRole }}</div>
                </div>
                <div class="avatar-wrapper">
                    <div class="avatar">{{ $topbarInitial }}</div>
                    <span class="avatar-status status-online"></span>
                </div>
            </div>
            <ul class="dropdown-menu dropdown-menu-end mt-2">
                <li><div class="px-3 py-2"><div class="fw-bold" style="font-size: 14px; color: var(--text-primary)">{{ $topbarName }}</div><div style="font-size: 12px; color: var(--text-muted)">{{ $topbarEmail }}</div></div></li>
                <li><hr class="dropdown-divider" /></li>
                <li><a class="dropdown-item" href="#"><i class="fa-solid fa-circle-user"></i> My Profile</a></li>
                <li><a class="dropdown-item" href="#"><i class="fa-solid fa-sliders"></i> Account Settings</a></li>
                <li><a class="dropdown-item" href="#"><i class="fa-solid fa-credit-card"></i> Billing &amp; Plans</a></li>
                <li><hr class="dropdown-divider" /></li>
                <li><a class="dropdown-item" href="#"><i class="fa-solid fa-life-ring"></i> Help Center</a></li>
                <li><a class="dropdown-item text-danger" href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();"><i class="fa-solid fa-right-from-bracket"></i> Sign Out</a></li>
            </ul>
        </div>
    </div>
</nav>
