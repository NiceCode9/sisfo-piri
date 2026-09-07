<!doctype html>
<html lang="id" data-theme="light">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="Nexus Admin — Modern Bootstrap 5 Dashboard Template" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>@yield('title', 'Nexus Admin — Dashboard')</title>

    {{-- Set theme sebelum paint untuk menghindari FOUC (selaras dengan script.js: localStorage nexus-theme) --}}
    <script>
        (function () {
            try {
                document.documentElement.setAttribute('data-theme', localStorage.getItem('nexus-theme') || 'light');
            } catch (e) {}
        })();
    </script>

    <!-- Bootstrap 5 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" />
    <!-- FontAwesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    <!-- Nexus custom CSS -->
    <link rel="stylesheet" href="{{ asset('assets/style.css') }}" />

    @stack('styles')
</head>
<body>
    <div class="layout-wrapper">

        {{-- SIDEBAR (desktop) + MOBILE SIDEBAR (offcanvas) --}}
        @include('layouts.partials.sidebar')

        <!-- MAIN CONTENT -->
        <div id="mainContent" class="main-content">

            {{-- TOP NAVBAR --}}
            @include('layouts.partials.topbar')

            <!-- PAGE CONTENT -->
            <main class="page-content">
                @yield('content')
            </main>

            {{-- FOOTER --}}
            @include('layouts.partials.footer')
        </div>
    </div>

    <!-- TOASTS (global; hook script.js: #welcomeToast, #liveToast) -->
    <div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 9999">
        <div id="welcomeToast" class="toast align-items-center" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body d-flex align-items-center gap-3">
                    <div style="width: 34px; height: 34px; background: rgba(79,70,229,0.12); border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #4f46e5; flex-shrink: 0;"><i class="fa-solid fa-hexagon"></i></div>
                    <div><div style="font-weight: 700; font-size: 13px; color: var(--text-primary);">Welcome back{{ auth()->check() ? ', '.auth()->user()->name : '' }}!</div><div style="font-size: 12px; color: var(--text-muted);">You have 4 unread notifications.</div></div>
                </div>
                <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
        <div id="liveToast" class="toast align-items-center" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body d-flex align-items-center gap-3">
                    <div style="width: 34px; height: 34px; background: rgba(34,197,94,0.12); border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #22c55e; flex-shrink: 0;"><i class="fa-solid fa-circle-check"></i></div>
                    <div><div style="font-weight: 700; font-size: 13px; color: var(--text-primary);">Action Successful!</div><div style="font-size: 12px; color: var(--text-muted);">Changes have been saved.</div></div>
                </div>
                <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    </div>

    @stack('modals')

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Nexus custom JS -->
    <script src="{{ asset('assets/script.js') }}"></script>

    @stack('scripts')
</body>
</html>
