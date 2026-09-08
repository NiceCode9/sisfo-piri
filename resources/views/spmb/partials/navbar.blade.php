{{-- TODO: ganti dengan data asli sekolah --}}
@php
    $schoolName = 'SMP Harapan Bangsa'; // ganti dengan nama sekolah asli
    $schoolLogo = 'https://via.placeholder.com/120x120?text=Logo'; // ganti dengan logo asli
@endphp

<nav id="navbar" class="fixed top-0 left-0 right-0 z-50 transition-all duration-300">
    <div class="container mx-auto px-4">
        <div class="flex items-center justify-between h-20">
            
            {{-- Logo & Brand --}}
            <div class="flex items-center space-x-3">
                <img src="{{ $schoolLogo }}" alt="Logo {{ $schoolName }}" class="h-12 w-12 object-contain">
                <div class="hidden md:block">
                    <h1 class="text-lg font-bold text-white navbar-text">{{ $schoolName }}</h1>
                    <p class="text-xs text-white/80 navbar-text">Penerimaan Murid Baru</p>
                </div>
            </div>

            {{-- Desktop Menu --}}
            <div class="hidden lg:flex items-center space-x-8">
                <a href="{{ route('spmb.home') }}#beranda" class="text-sm font-medium text-white navbar-text hover:text-secondary-400 transition">Beranda</a>
                <a href="{{ route('spmb.home') }}#tentang" class="text-sm font-medium text-white navbar-text hover:text-secondary-400 transition">Tentang SPMB</a>
                <a href="{{ route('spmb.home') }}#jalur" class="text-sm font-medium text-white navbar-text hover:text-secondary-400 transition">Jalur Pendaftaran</a>
                <a href="{{ route('spmb.home') }}#biaya" class="text-sm font-medium text-white navbar-text hover:text-secondary-400 transition">Biaya</a>
                <a href="{{ route('spmb.pengumuman.index') }}" class="text-sm font-medium text-white navbar-text hover:text-secondary-400 transition">Pengumuman</a>
            </div>

            {{-- CTA Buttons --}}
            <div class="hidden lg:flex items-center space-x-3">
                <a href="{{ route('login') }}" class="px-4 py-2 text-sm font-medium text-white border-2 border-white rounded-xl hover:bg-white hover:text-primary-600 transition">
                    Login
                </a>
                <a href="{{ route('spmb.pendaftaran') }}" class="px-5 py-2 text-sm font-semibold text-white bg-secondary-500 rounded-xl hover:bg-secondary-600 transition shadow-lg">
                    Daftar Sekarang
                </a>
            </div>

            {{-- Mobile Hamburger --}}
            <button id="hamburger" class="lg:hidden flex flex-col space-y-1.5 p-2" aria-label="Menu">
                <span class="block w-6 h-0.5 bg-white navbar-text transition"></span>
                <span class="block w-6 h-0.5 bg-white navbar-text transition"></span>
                <span class="block w-6 h-0.5 bg-white navbar-text transition"></span>
            </button>

        </div>
    </div>

    {{-- Mobile Menu --}}
    <div id="mobile-menu" class="lg:hidden hidden bg-white shadow-lg">
        <div class="container mx-auto px-4 py-6 space-y-4">
            <a href="{{ route('spmb.home') }}#beranda" class="block text-sm font-medium text-gray-700 hover:text-primary-600 transition">Beranda</a>
            <a href="{{ route('spmb.home') }}#tentang" class="block text-sm font-medium text-gray-700 hover:text-primary-600 transition">Tentang SPMB</a>
            <a href="{{ route('spmb.home') }}#jalur" class="block text-sm font-medium text-gray-700 hover:text-primary-600 transition">Jalur Pendaftaran</a>
            <a href="{{ route('spmb.home') }}#biaya" class="block text-sm font-medium text-gray-700 hover:text-primary-600 transition">Biaya</a>
            <a href="{{ route('spmb.pengumuman.index') }}" class="block text-sm font-medium text-gray-700 hover:text-primary-600 transition">Pengumuman</a>
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
    // Navbar scroll behavior - transparan ke solid
    const navbar = document.getElementById('navbar');
    const navbarTexts = document.querySelectorAll('.navbar-text');
    
    window.addEventListener('scroll', () => {
        if (window.scrollY > 50) {
            navbar.classList.add('bg-white', 'shadow-md');
            navbar.classList.remove('bg-transparent');
            navbarTexts.forEach(el => {
                el.classList.remove('text-white');
                el.classList.add('text-gray-800');
            });
        } else {
            navbar.classList.remove('bg-white', 'shadow-md');
            navbar.classList.add('bg-transparent');
            navbarTexts.forEach(el => {
                el.classList.remove('text-gray-800');
                el.classList.add('text-white');
            });
        }
    });

    // Mobile menu toggle
    const hamburger = document.getElementById('hamburger');
    const mobileMenu = document.getElementById('mobile-menu');
    
    hamburger.addEventListener('click', () => {
        mobileMenu.classList.toggle('hidden');
    });

    // Close mobile menu when clicking a link
    document.querySelectorAll('#mobile-menu a').forEach(link => {
        link.addEventListener('click', () => {
            mobileMenu.classList.add('hidden');
        });
    });
</script>
