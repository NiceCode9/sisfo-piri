@php
    // $profileSekolah dishare otomatis via View composer (spmb.*); fallback bila baris profil belum ada.
    $schoolName = $profileSekolah->nama_sekolah ?? 'SMP Harapan Bangsa';
    $schoolLogo = ! empty($profileSekolah->logo_path)
        ? Storage::disk('public')->url($profileSekolah->logo_path)
        : 'https://via.placeholder.com/120x120?text=Logo';

    // Set true HANYA jika section persis di bawah navbar adalah hero berwarna gelap
    // (mis. bg-gradient-primary). Untuk halaman biasa (list, detail, form tanpa hero gelap),
    // biarkan false/default supaya navbar solid sejak awal & tetap terbaca.
    $transparent = $transparent ?? false;
@endphp

<nav id="navbar"
     data-transparent="{{ $transparent ? 'true' : 'false' }}"
     class="fixed top-0 left-0 right-0 z-50 transition-all duration-300 {{ $transparent ? 'bg-transparent' : 'bg-white shadow-md' }}">
    <div class="container mx-auto px-4">
        <div class="flex items-center justify-between h-20">

            {{-- Logo & Brand --}}
            <div class="flex items-center space-x-3">
                <img src="{{ $schoolLogo }}" alt="Logo {{ $schoolName }}" class="h-12 w-12 object-contain">
                <div class="hidden md:block">
                    <h1 class="text-lg font-bold navbar-text {{ $transparent ? 'text-white' : 'text-gray-800' }}">{{ $schoolName }}</h1>
                    <p class="text-xs navbar-text {{ $transparent ? 'text-white/80' : 'text-gray-500' }}">Penerimaan Murid Baru</p>
                </div>
            </div>

            {{-- Desktop Menu --}}
            <div class="hidden lg:flex items-center space-x-8">
                <a href="{{ route('spmb.home') }}#beranda" class="text-sm font-medium navbar-text {{ $transparent ? 'text-white' : 'text-gray-700' }} hover:text-secondary-500 transition">Beranda</a>
                {{-- <a href="{{ route('spmb.home') }}#tentang" class="text-sm font-medium navbar-text {{ $transparent ? 'text-white' : 'text-gray-700' }} hover:text-secondary-500 transition">Tentang SPMB</a> --}}
                {{-- <a href="{{ route('spmb.home') }}#jalur" class="text-sm font-medium navbar-text {{ $transparent ? 'text-white' : 'text-gray-700' }} hover:text-secondary-500 transition">Jalur Pendaftaran</a> --}}
                {{-- <a href="{{ route('spmb.home') }}#biaya" class="text-sm font-medium navbar-text {{ $transparent ? 'text-white' : 'text-gray-700' }} hover:text-secondary-500 transition">Biaya</a> --}}
                <a href="{{ route('spmb.pengumuman.index') }}" class="text-sm font-medium navbar-text {{ $transparent ? 'text-white' : 'text-gray-700' }} hover:text-secondary-500 transition">Pengumuman</a>
                <a href="{{ route('spmb.about') }}" class="text-sm font-medium navbar-text {{ $transparent ? 'text-white' : 'text-gray-700' }} hover:text-secondary-500 transition">Tentang</a>
            </div>

            {{-- CTA Buttons --}}
            <div class="hidden lg:flex items-center space-x-3">
                <a href="{{ route('login') }}"
                   class="px-4 py-2 text-sm font-medium rounded-xl border-2 transition navbar-text {{ $transparent ? 'text-white border-white hover:bg-white hover:text-primary-600' : 'text-primary-600 border-primary-600 hover:bg-primary-50' }}">
                    Login
                </a>
                <a href="{{ route('spmb.pendaftaran') }}" class="px-5 py-2 text-sm font-semibold text-white bg-secondary-500 rounded-xl hover:bg-secondary-600 transition shadow-lg">
                    Daftar Sekarang
                </a>
            </div>

            {{-- Mobile Hamburger --}}
            <button id="hamburger" class="lg:hidden flex flex-col space-y-1.5 p-2" aria-label="Menu">
                <span class="block w-6 h-0.5 navbar-text {{ $transparent ? 'bg-white' : 'bg-gray-800' }} transition"></span>
                <span class="block w-6 h-0.5 navbar-text {{ $transparent ? 'bg-white' : 'bg-gray-800' }} transition"></span>
                <span class="block w-6 h-0.5 navbar-text {{ $transparent ? 'bg-white' : 'bg-gray-800' }} transition"></span>
            </button>

        </div>
    </div>

    {{-- Mobile Menu --}}
    <div id="mobile-menu" class="lg:hidden hidden bg-white shadow-lg">
        <div class="container mx-auto px-4 py-6 space-y-4">
            <a href="{{ route('spmb.home') }}#beranda" class="block text-sm font-medium text-gray-700 hover:text-primary-600 transition">Beranda</a>
            {{-- <a href="{{ route('spmb.home') }}#tentang" class="block text-sm font-medium text-gray-700 hover:text-primary-600 transition">Tentang SPMB</a>
            <a href="{{ route('spmb.home') }}#jalur" class="block text-sm font-medium text-gray-700 hover:text-primary-600 transition">Jalur Pendaftaran</a>
            <a href="{{ route('spmb.home') }}#biaya" class="block text-sm font-medium text-gray-700 hover:text-primary-600 transition">Biaya</a> --}}
            <a href="{{ route('spmb.pengumuman.index') }}" class="block text-sm font-medium text-gray-700 hover:text-primary-600 transition">Pengumuman</a>
            <a href="{{ route('spmb.about') }}" class="block text-sm font-medium text-gray-700 hover:text-primary-600 transition">Tentang</a>
            <div class="pt-4 space-y-2 border-t">
                <a href="{{ route('login') }}" class="block w-full px-4 py-2.5 text-sm font-medium text-center text-primary-600 border-2 border-primary-600 rounded-xl hover:bg-primary-50 transition">
                    Login
                </a>
                <a href="{{ route('spmb.pendaftaran') }}" class="block w-full px-4 py-2.5 text-sm font-semibold text-center text-white bg-secondary-500 rounded-xl hover:bg-secondary-600 transition">
                    Daftar Sekarang
                </a>
            </div>
        </div>
    </div>
</nav>

<script>
    (function () {
        const navbar = document.getElementById('navbar');
        const isTransparent = navbar.dataset.transparent === 'true';
        const navbarTexts = document.querySelectorAll('.navbar-text');

        // Hanya aktifkan efek transparan->solid kalau navbar memang dipasang mode transparan
        // (di atas hero berwarna). Halaman lain sudah solid dari awal, tidak perlu listener ini.
        if (isTransparent) {
            window.addEventListener('scroll', () => {
                if (window.scrollY > 50) {
                    navbar.classList.add('bg-white', 'shadow-md');
                    navbar.classList.remove('bg-transparent');
                    navbarTexts.forEach((el) => {
                        el.classList.remove('text-white', 'text-white/80', 'border-white');
                        el.classList.add('text-gray-800');
                    });
                } else {
                    navbar.classList.remove('bg-white', 'shadow-md');
                    navbar.classList.add('bg-transparent');
                    navbarTexts.forEach((el) => {
                        el.classList.remove('text-gray-800');
                        el.classList.add('text-white');
                    });
                }
            });
        }

        // Mobile menu toggle
        const hamburger = document.getElementById('hamburger');
        const mobileMenu = document.getElementById('mobile-menu');

        hamburger.addEventListener('click', () => {
            mobileMenu.classList.toggle('hidden');
        });

        document.querySelectorAll('#mobile-menu a').forEach((link) => {
            link.addEventListener('click', () => {
                mobileMenu.classList.add('hidden');
            });
        });
    })();
</script>
