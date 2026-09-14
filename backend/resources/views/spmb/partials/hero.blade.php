{{-- TODO: ganti dengan data asli tahun ajaran --}}
@php
    $tahunAjaran = '2026/2027'; // ganti dinamis sesuai kebutuhan
    $currentYear = date('Y');
@endphp

<section id="beranda" class="relative min-h-screen flex items-center bg-gradient-primary overflow-hidden">

    {{-- Decorative Background Shapes --}}
    <div class="absolute top-20 right-10 w-64 h-64 bg-secondary-400/20 blob-shape animate-float"></div>
    <div class="absolute bottom-32 left-10 w-80 h-80 bg-accent-400/20 blob-shape animate-float" style="animation-delay: 1s;"></div>
    <div class="absolute top-1/2 right-1/4 w-48 h-48 bg-white/10 blob-shape animate-float" style="animation-delay: 2s;"></div>

    <div class="container mx-auto px-4 py-20 relative z-10">
        <div class="grid lg:grid-cols-2 gap-12 items-center">

            {{-- Left Content --}}
            <div class="text-white space-y-6 animate-fade-in-up">
                <div class="inline-block px-4 py-2 bg-secondary-500 rounded-full text-sm font-semibold shadow-lg">
                    🎉 Pendaftaran Dibuka!
                </div>

                <h1 class="text-4xl md:text-5xl lg:text-6xl font-extrabold leading-tight">
                    Wujudkan Impianmu<br/>
                    <span class="text-secondary-300">Bersama Kami!</span>
                </h1>

                <p class="text-lg md:text-xl text-white/90 leading-relaxed">
                    Bergabunglah dalam Penerimaan Murid Baru Tahun Ajaran <span class="font-bold">{{ $tahunAjaran }}</span>.
                    Raih prestasi, kembangkan bakatmu, dan ciptakan masa depan cerah!
                </p>

                {{-- CTA Buttons --}}
                <div class="flex flex-col sm:flex-row gap-4 pt-4">
                    <a href="#alur" class="inline-flex items-center justify-center px-8 py-4 text-lg font-bold text-primary-600 bg-white rounded-2xl hover:bg-gray-50 transition shadow-xl hover:shadow-2xl hover:scale-105 transform">
                        <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        Mulai Pendaftaran
                    </a>
                    <a href="#" class="inline-flex items-center justify-center px-8 py-4 text-lg font-bold text-white border-2 border-white rounded-2xl hover:bg-white hover:text-primary-600 transition">
                        <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        Login Calon Murid
                    </a>
                </div>

                {{-- Stats --}}
                <div class="grid grid-cols-3 gap-4 pt-8">
                    <div class="text-center">
                        <div class="text-3xl md:text-4xl font-extrabold text-secondary-300">500+</div>
                        <div class="text-sm text-white/80">Siswa Aktif</div>
                    </div>
                    <div class="text-center">
                        <div class="text-3xl md:text-4xl font-extrabold text-secondary-300">25+</div>
                        <div class="text-sm text-white/80">Guru Berpengalaman</div>
                    </div>
                    <div class="text-center">
                        <div class="text-3xl md:text-4xl font-extrabold text-secondary-300">15+</div>
                        <div class="text-sm text-white/80">Tahun Berdiri</div>
                    </div>
                </div>
            </div>

            {{-- Right Illustration --}}
            <div class="relative animate-fade-in-up" style="animation-delay: 0.3s;">
                {{-- ganti dengan foto asli sekolah - siswa SMP yang ceria, bukan foto stok formal --}}
                <div class="relative z-10">
                    <img src="https://images.unsplash.com/photo-1524178232363-1fb2b075b655?q=80&w=1170&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D"
                         alt="Siswa SMP"
                         class="rounded-3xl shadow-2xl w-full h-auto object-cover">
                </div>

                {{-- Floating Card 1 --}}
                <div class="absolute -bottom-8 -left-8 z-20 bg-white rounded-2xl p-4 shadow-xl animate-float hidden md:block">
                    <div class="flex items-center space-x-3">
                        <div class="w-12 h-12 bg-accent-500 rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div>
                            <div class="text-sm font-bold text-gray-800">Terakreditasi A</div>
                            <div class="text-xs text-gray-500">Standar Nasional</div>
                        </div>
                    </div>
                </div>

                {{-- Floating Card 2 --}}
                <div class="absolute -top-8 -right-8 z-20 bg-white rounded-2xl p-4 shadow-xl animate-float hidden md:block" style="animation-delay: 1.5s;">
                    <div class="flex items-center space-x-3">
                        <div class="w-12 h-12 bg-secondary-500 rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                            </svg>
                        </div>
                        <div>
                            <div class="text-sm font-bold text-gray-800">Kurikulum Merdeka</div>
                            <div class="text-xs text-gray-500">Update & Inovatif</div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    {{-- Scroll Indicator --}}
    <div class="absolute bottom-8 left-1/2 transform -translate-x-1/2 animate-bounce">
        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
        </svg>
    </div>

</section>
