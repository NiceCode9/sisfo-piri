@extends('layouts.spmb')

@section('title', 'Tentang Sekolah — ' . ($profileSekolah->nama_sekolah ?? 'SPMB'))

@php
    // ============================================================
    // TODO: Semua variabel di bawah ini SEMENTARA/DUMMY untuk keperluan
    // desain frontend. Ganti dengan data asli dari controller nanti
    // (mis. ProfileSekolahController, PrestasiController, dll).
    // ============================================================
    $profileSekolah = $profileSekolah ?? (object) [
        'nama_sekolah' => 'SMP Harapan Bangsa',
        'tahun_berdiri' => 2011,
        'akreditasi' => 'A',
        'alamat' => 'Jl. Pendidikan No. 123, Kota Bandung, Jawa Barat 40123',
        'telp' => '(022) 1234-5678',
        'email' => 'info@smpharapanbangsa.sch.id',
    ];

    $tahunBerjalan = date('Y') - $profileSekolah->tahun_berdiri;

    $stats = [
        ['value' => '500+', 'label' => 'Siswa Aktif'],
        ['value' => '35+', 'label' => 'Tenaga Pendidik'],
        ['value' => $tahunBerjalan . '+', 'label' => 'Tahun Berdiri'],
        ['value' => $profileSekolah->akreditasi, 'label' => 'Akreditasi'],
    ];

    $misiList = [
        'Menyelenggarakan pembelajaran yang aktif, kreatif, dan menyenangkan berbasis Kurikulum Merdeka',
        'Membentuk karakter siswa yang berakhlak mulia, disiplin, dan bertanggung jawab',
        'Mengembangkan potensi akademik dan non-akademik siswa secara seimbang',
        'Membangun lingkungan sekolah yang aman, inklusif, dan ramah anak',
        'Menjalin kemitraan aktif dengan orang tua dan masyarakat sekitar',
    ];

    $nilaiList = [
        ['icon' => 'heart', 'title' => 'Integritas', 'desc' => 'Jujur dan bertanggung jawab dalam setiap tindakan'],
        ['icon' => 'sparkles', 'title' => 'Kreativitas', 'desc' => 'Berani bereksplorasi dan berinovasi'],
        ['icon' => 'users', 'title' => 'Kolaborasi', 'desc' => 'Bekerja sama membangun lingkungan yang positif'],
        ['icon' => 'academic-cap', 'title' => 'Prestasi', 'desc' => 'Berusaha mencapai hasil terbaik secara konsisten'],
        ['icon' => 'globe', 'title' => 'Wawasan Global', 'desc' => 'Terbuka terhadap perkembangan dunia'],
        ['icon' => 'shield-check', 'title' => 'Karakter', 'desc' => 'Berakhlak mulia dan menghargai perbedaan'],
    ];

    $fasilitasList = [
        ['title' => 'Laboratorium IPA', 'desc' => 'Ruang praktikum sains lengkap dengan alat modern', 'image' => 'https://via.placeholder.com/500x400?text=Lab+IPA'],
        ['title' => 'Perpustakaan', 'desc' => 'Koleksi ribuan buku dan area baca yang nyaman', 'image' => 'https://via.placeholder.com/500x400?text=Perpustakaan'],
        ['title' => 'Lapangan Olahraga', 'desc' => 'Lapangan multifungsi untuk berbagai cabang olahraga', 'image' => 'https://via.placeholder.com/500x400?text=Lapangan'],
        ['title' => 'Ruang Komputer', 'desc' => 'Lab komputer dengan akses internet untuk pembelajaran digital', 'image' => 'https://via.placeholder.com/500x400?text=Lab+Komputer'],
        ['title' => 'Ruang Kesenian', 'desc' => 'Fasilitas untuk mengembangkan bakat seni dan musik siswa', 'image' => 'https://via.placeholder.com/500x400?text=Ruang+Kesenian'],
        ['title' => 'Masjid Sekolah', 'desc' => 'Tempat ibadah yang nyaman untuk kegiatan keagamaan', 'image' => 'https://via.placeholder.com/500x400?text=Masjid'],
    ];

    $kepalaSekolah = (object) [
        'nama' => 'Dr. Ahmad Fauzi, M.Pd.',
        'jabatan' => 'Kepala Sekolah',
        'foto' => 'https://via.placeholder.com/400x400?text=Foto+Kepala+Sekolah',
        'sambutan' => 'Selamat datang di ' . $profileSekolah->nama_sekolah . '. Kami berkomitmen menghadirkan pendidikan berkualitas yang membentuk generasi cerdas, berkarakter, dan siap menghadapi tantangan masa depan. Mari bergabung bersama kami.',
    ];

    $icons = [
        'heart' => ['M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z'],
        'sparkles' => ['M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-6.714 2.143L12 21l-2.286-6.857L3 12l6.714-2.143L12 3z'],
        'users' => ['M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zM7 7a3 3 0 11-6 0 3 3 0 016 0z'],
        'academic-cap' => ['M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14zm-4 6v-7.5l4-2.222'],
        'globe' => ['M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
        'shield-check' => ['M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z'],
        'check' => ['M5 13l4 4L19 7'],
        'flag' => ['M3 3v18M3 4h13l-2 4 2 4H3'],
        'target' => ['M12 8a4 4 0 100 8 4 4 0 000-8zm0-6a10 10 0 100 20 10 10 0 000-20zm0 3a7 7 0 100 14 7 7 0 000-14z'],
        'quote' => ['M9.983 3v7.391c0 5.704-3.731 9.57-8.983 10.609l-.995-2.151c2.432-.917 3.995-3.638 3.995-5.849h-4v-10h9.983zm14.017 0v7.391c0 5.704-3.748 9.57-9 10.609l-.996-2.151c2.433-.917 3.996-3.638 3.996-5.849h-4v-10h10z'],
        'bolt' => ['M13 10V3L4 14h7v7l9-11h-7z'],
    ];

    $renderIcon = fn ($key) => $icons[$key] ?? $icons['check'];
@endphp

@section('content')

    @include('spmb.partials.navbar', ['transparent' => true])

    {{-- ============ HERO ============ --}}
    <section class="relative bg-gradient-primary py-28 overflow-hidden">
        <div class="absolute inset-0 bg-black/10"></div>
        <div class="absolute top-10 right-10 w-72 h-72 bg-secondary-400/20 blob-shape animate-float"></div>
        <div class="absolute bottom-10 left-10 w-80 h-80 bg-accent-400/20 blob-shape animate-float" style="animation-delay: 1s;"></div>

        <div class="relative max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <div class="inline-flex items-center px-4 py-2 bg-white/10 backdrop-blur-sm rounded-full text-white text-sm mb-6 border border-white/20">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    @foreach ($renderIcon('flag') as $d)
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $d }}"/>
                    @endforeach
                </svg>
                Tentang Kami
            </div>

            <h1 class="text-4xl md:text-6xl font-extrabold text-white mb-6 leading-tight">
                Mengenal Lebih Dekat<br/>
                <span class="text-secondary-300">{{ $profileSekolah->nama_sekolah }}</span>
            </h1>

            <p class="text-lg md:text-xl text-white/90 max-w-2xl mx-auto mb-10 leading-relaxed">
                Sejak {{ $profileSekolah->tahun_berdiri }}, kami berkomitmen membentuk generasi cerdas, berkarakter, dan berprestasi untuk masa depan Indonesia yang gemilang.
            </p>

            {{-- Stats --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 md:gap-6">
                @foreach ($stats as $stat)
                    <div class="bg-white/10 backdrop-blur-sm border border-white/20 rounded-2xl py-6 px-3">
                        <div class="text-3xl md:text-4xl font-extrabold text-secondary-300">{{ $stat['value'] }}</div>
                        <div class="text-sm text-white/80 mt-1">{{ $stat['label'] }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ============ SEJARAH ============ --}}
    <section class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid lg:grid-cols-2 gap-12 items-center">
                <div class="relative">
                    {{-- TODO: ganti dengan foto asli gedung/kegiatan sekolah --}}
                    <img src="https://via.placeholder.com/600x500?text=Gedung+Sekolah"
                         alt="Gedung {{ $profileSekolah->nama_sekolah }}"
                         class="rounded-3xl shadow-2xl w-full h-auto object-cover">
                    <div class="absolute -bottom-6 -right-6 z-10 bg-white rounded-2xl p-5 shadow-xl hidden md:block">
                        <div class="text-3xl font-extrabold text-primary-600">{{ $profileSekolah->tahun_berdiri }}</div>
                        <div class="text-sm text-gray-500">Tahun Berdiri</div>
                    </div>
                </div>

                <div class="space-y-6">
                    <span class="inline-block px-4 py-1.5 bg-primary-100 text-primary-700 rounded-full text-sm font-semibold">Sejarah Kami</span>
                    <h2 class="text-3xl md:text-4xl font-extrabold text-gray-800 leading-tight">
                        Perjalanan Membentuk Generasi Unggul
                    </h2>
                    {{-- TODO: ganti dengan narasi sejarah asli sekolah --}}
                    <p class="text-gray-600 leading-relaxed">
                        {{ $profileSekolah->nama_sekolah }} didirikan pada tahun {{ $profileSekolah->tahun_berdiri }} dengan visi menghadirkan pendidikan menengah pertama yang berkualitas dan terjangkau bagi masyarakat sekitar.
                    </p>
                    <p class="text-gray-600 leading-relaxed">
                        Selama {{ $tahunBerjalan }} tahun perjalanan, kami terus berkembang — dari fasilitas, kurikulum, hingga kualitas tenaga pendidik — untuk memastikan setiap siswa mendapatkan pengalaman belajar terbaik dan siap melangkah ke jenjang pendidikan berikutnya.
                    </p>
                    <div class="flex items-center gap-3 pt-2">
                        <div class="w-10 h-10 bg-accent-100 rounded-full flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5 text-accent-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                @foreach ($renderIcon('check') as $d)
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $d }}"/>
                                @endforeach
                            </svg>
                        </div>
                        <span class="text-gray-700 font-medium">Terakreditasi {{ $profileSekolah->akreditasi }} — Standar Nasional</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ============ VISI & MISI ============ --}}
    <section class="py-20 bg-slate-50">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-14">
                <span class="inline-block px-4 py-1.5 bg-accent-100 text-accent-700 rounded-full text-sm font-semibold mb-4">Arah Kami</span>
                <h2 class="text-3xl md:text-4xl font-extrabold text-gray-800">Visi &amp; Misi</h2>
            </div>

            <div class="grid lg:grid-cols-5 gap-8">
                {{-- Visi --}}
                <div class="lg:col-span-2 bg-gradient-primary rounded-3xl p-8 md:p-10 text-white shadow-xl flex flex-col justify-center">
                    <div class="w-14 h-14 bg-white/20 backdrop-blur-sm rounded-2xl flex items-center justify-center mb-6">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            @foreach ($renderIcon('target') as $d)
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $d }}"/>
                            @endforeach
                        </svg>
                    </div>
                    <h3 class="text-2xl font-bold mb-4">Visi</h3>
                    {{-- TODO: ganti dengan visi asli sekolah --}}
                    <p class="text-white/90 leading-relaxed text-lg">
                        Mewujudkan generasi yang cerdas, berkarakter, dan berprestasi untuk masa depan Indonesia yang gemilang.
                    </p>
                </div>

                {{-- Misi --}}
                <div class="lg:col-span-3 bg-white rounded-3xl p-8 md:p-10 shadow-xl border border-gray-100">
                    <div class="w-14 h-14 bg-primary-100 rounded-2xl flex items-center justify-center mb-6">
                        <svg class="w-7 h-7 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            @foreach ($renderIcon('flag') as $d)
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $d }}"/>
                            @endforeach
                        </svg>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-800 mb-6">Misi</h3>
                    <div class="space-y-4">
                        @foreach ($misiList as $index => $misi)
                            <div class="flex items-start gap-4">
                                <div class="w-8 h-8 bg-primary-600 text-white rounded-full flex items-center justify-center text-sm font-bold flex-shrink-0">
                                    {{ $index + 1 }}
                                </div>
                                <span class="text-gray-700 leading-relaxed pt-0.5">{{ $misi }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ============ NILAI-NILAI ============ --}}
    <section class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-14">
                <span class="inline-block px-4 py-1.5 bg-secondary-100 text-secondary-700 rounded-full text-sm font-semibold mb-4">Yang Kami Junjung</span>
                <h2 class="text-3xl md:text-4xl font-extrabold text-gray-800 mb-4">Nilai-Nilai Sekolah</h2>
                <p class="text-lg text-gray-600 max-w-2xl mx-auto">Enam nilai yang membentuk karakter setiap siswa kami</p>
            </div>

            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($nilaiList as $nilai)
                    <div class="bg-slate-50 hover:bg-white rounded-2xl p-6 border border-gray-100 hover:shadow-xl transition-all duration-300 card-hover">
                        <div class="w-14 h-14 bg-gradient-primary rounded-2xl flex items-center justify-center text-white mb-4 shadow-md">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                @foreach ($renderIcon($nilai['icon']) as $d)
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $d }}"/>
                                @endforeach
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-gray-800 mb-2">{{ $nilai['title'] }}</h3>
                        <p class="text-gray-600 text-sm leading-relaxed">{{ $nilai['desc'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ============ SAMBUTAN KEPALA SEKOLAH ============ --}}
    <section class="py-20 bg-slate-50">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white rounded-3xl shadow-xl border border-gray-100 overflow-hidden">
                <div class="grid md:grid-cols-5">
                    <div class="md:col-span-2 relative">
                        {{-- TODO: ganti dengan foto asli kepala sekolah --}}
                        <img src="{{ $kepalaSekolah->foto }}" alt="{{ $kepalaSekolah->nama }}"
                             class="w-full h-64 md:h-full object-cover">
                    </div>
                    <div class="md:col-span-3 p-8 md:p-10 flex flex-col justify-center relative">
                        <svg class="w-10 h-10 text-primary-100 mb-4" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            @foreach ($renderIcon('quote') as $d)
                                <path d="{{ $d }}"/>
                            @endforeach
                        </svg>
                        <p class="text-gray-700 text-lg leading-relaxed mb-6 italic">
                            &ldquo;{{ $kepalaSekolah->sambutan }}&rdquo;
                        </p>
                        <div>
                            <div class="font-bold text-gray-800 text-lg">{{ $kepalaSekolah->nama }}</div>
                            <div class="text-primary-600 text-sm font-medium">{{ $kepalaSekolah->jabatan }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ============ FASILITAS ============ --}}
    <section class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-14">
                <span class="inline-block px-4 py-1.5 bg-accent-100 text-accent-700 rounded-full text-sm font-semibold mb-4">Fasilitas</span>
                <h2 class="text-3xl md:text-4xl font-extrabold text-gray-800 mb-4">Fasilitas Penunjang Belajar</h2>
                <p class="text-lg text-gray-600 max-w-2xl mx-auto">Lingkungan belajar yang lengkap dan nyaman untuk mendukung tumbuh kembang siswa</p>
            </div>

            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($fasilitasList as $fasilitas)
                    <div class="group relative rounded-2xl overflow-hidden shadow-lg hover:shadow-2xl transition-all duration-300 card-hover">
                        <img src="{{ $fasilitas['image'] }}" alt="{{ $fasilitas['title'] }}"
                             class="w-full h-56 object-cover group-hover:scale-105 transition-transform duration-500">
                        <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/10 to-transparent"></div>
                        <div class="absolute bottom-0 left-0 right-0 p-5">
                            <h3 class="text-white font-bold text-lg mb-1">{{ $fasilitas['title'] }}</h3>
                            <p class="text-white/80 text-sm">{{ $fasilitas['desc'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ============ CTA ============ --}}
    <section class="relative bg-gradient-primary py-20 overflow-hidden">
        <div class="absolute top-0 left-1/4 w-64 h-64 bg-secondary-400/20 blob-shape animate-float"></div>
        <div class="absolute bottom-0 right-1/4 w-72 h-72 bg-accent-400/20 blob-shape animate-float" style="animation-delay: 1.2s;"></div>

        <div class="relative max-w-3xl mx-auto text-center px-4 sm:px-6 lg:px-8">
            <h2 class="text-3xl md:text-4xl font-extrabold text-white mb-4">Siap Bergabung Bersama Kami?</h2>
            <p class="text-lg text-white/90 mb-8">
                Wujudkan langkah pertama menuju masa depan cerah putra-putri Anda
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="{{ route('spmb.pendaftaran') }}"
                   class="inline-flex items-center justify-center px-8 py-4 text-lg font-bold text-primary-700 bg-white rounded-2xl hover:bg-gray-50 transition shadow-xl hover:shadow-2xl hover:scale-105 transform">
                    <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        @foreach ($renderIcon('bolt') as $d)
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $d }}"/>
                        @endforeach
                    </svg>
                    Daftar Sekarang
                </a>
                <a href="{{ route('spmb.home') }}#kontak"
                   class="inline-flex items-center justify-center px-8 py-4 text-lg font-bold text-white border-2 border-white/60 rounded-2xl hover:bg-white hover:text-primary-700 transition">
                    Hubungi Kami
                </a>
            </div>
        </div>
    </section>

    @include('spmb.partials.footer')

@endsection

