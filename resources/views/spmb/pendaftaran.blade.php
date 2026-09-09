@extends('layouts.spmb')

@section('title', 'Pendaftaran Siswa Baru')

@php
    // ============================================================
    // TODO: Semua variabel di bawah ini SEMENTARA/DUMMY untuk keperluan
    // desain frontend. Ganti dengan data asli dari controller nanti:
    //   - $jalurPendaftarans, $tahunAjaranAktif -> dari JalurPendaftaranController
    //   - $jadwalPpdb -> dari JadwalPpdbController
    //   - $profileSekolah -> dari ProfileSekolahController
    // ============================================================
    $jalurPendaftarans = $jalurPendaftarans ?? collect([
        (object) ['id' => 1, 'nama_jalur' => 'Reguler', 'aktif' => true],
        (object) ['id' => 2, 'nama_jalur' => 'Prestasi / Beasiswa', 'aktif' => true],
    ]);

    $jadwalPpdb = $jadwalPpdb ?? collect([
        (object) ['nama_jadwal' => 'Pendaftaran', 'tanggal_mulai' => '1 Nov 2026', 'tanggal_selesai' => '30 Nov 2026', 'keterangan' => null],
        (object) ['nama_jadwal' => 'Tes Masuk', 'tanggal_mulai' => '5 Des 2026', 'tanggal_selesai' => '10 Des 2026', 'keterangan' => null],
        (object) ['nama_jadwal' => 'Verifikasi Berkas', 'tanggal_mulai' => '12 Des 2026', 'tanggal_selesai' => '15 Des 2026', 'keterangan' => null],
        (object) ['nama_jadwal' => 'Pengumuman', 'tanggal_mulai' => '20 Des 2026', 'tanggal_selesai' => '20 Des 2026', 'keterangan' => null],
        (object) ['nama_jadwal' => 'Daftar Ulang', 'tanggal_mulai' => '21 Des 2026', 'tanggal_selesai' => '31 Des 2026', 'keterangan' => null],
    ]);

    $profileSekolah = $profileSekolah ?? (object) [
        'telp' => '(022) 1234-5678',
        'email' => 'spmb@smpharapanbangsa.sch.id',
    ];

    // Ikon inline (pengganti Font Awesome, konsisten dengan section lain di site ini).
    $icons = [
        'calendar' => ['M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'],
        'clipboard' => ['M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4'],
        'document' => ['M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
        'bell' => ['M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9'],
        'check-circle' => ['M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
        'graduation-cap' => ['M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14zm-4 6v-7.5l4-2.222'],
        'bolt' => ['M13 10V3L4 14h7v7l9-11h-7z'],
        'info-circle' => ['M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
        'exclamation-triangle' => ['M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z'],
        'globe' => ['M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
        'book-open' => ['M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253'],
        'phone' => ['M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z'],
        'route' => ['M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7'],
        'user' => ['M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'],
        'users' => ['M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zM7 7a3 3 0 11-6 0 3 3 0 016 0z'],
        'id-card' => ['M3 10h18M7 15h1m4 0h1m-7 4h12a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z'],
        'location' => ['M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0zM15 11a3 3 0 11-6 0 3 3 0 016 0z'],
        'home' => ['M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
        'mobile' => ['M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z'],
        'envelope' => ['M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z'],
        'briefcase' => ['M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z'],
        'upload' => ['M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3-3m3 3v12'],
        'image' => ['M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14M14 8h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z'],
        'paper-plane' => ['M12 19l9 2-9-18-9 18 9-2zm0 0v-8'],
        'arrow-left' => ['M10 19l-7-7m0 0l7-7m-7 7h18'],
        'arrow-right' => ['M14 5l7 7m0 0l-7 7m7-7H3'],
        'sparkles' => ['M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-6.714 2.143L12 21l-2.286-6.857L3 12l6.714-2.143L12 3z'],
        'shield-check' => ['M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z'],
    ];

    $timelineIcons = [
        'Pendaftaran' => 'calendar', 'Tes Masuk' => 'clipboard', 'Verifikasi Berkas' => 'document',
        'Pengumuman' => 'bell', 'Daftar Ulang' => 'check-circle',
    ];

    $persyaratanList = [
        'Fotokopi akte kelahiran dan kartu keluarga',
        'Fotokopi rapor SD kelas 4-6',
        'Pas foto 3x4 (3 lembar)',
        'Surat Keterangan Lulus (SKL) asli',
    ];

    $infoPentingList = [
        ['icon' => 'globe', 'text' => 'Pendaftaran dilakukan secara online melalui website ini'],
        ['icon' => 'book-open', 'text' => 'Tes masuk meliputi: Matematika, IPA, dan Bahasa Indonesia'],
        ['icon' => 'bell', 'text' => 'Pengumuman hasil seleksi dapat dilihat di website ini'],
        ['icon' => 'phone', 'text' => "Informasi lebih lanjut hubungi: {$profileSekolah->telp}"],
    ];

    $trustStrip = [
        ['icon' => 'globe', 'title' => '100% Online', 'desc' => 'Daftar dari mana saja'],
        ['icon' => 'bolt', 'title' => 'Proses 3 Hari', 'desc' => 'Verifikasi berkas cepat'],
        ['icon' => 'shield-check', 'title' => 'Data Aman', 'desc' => 'Privasi terjaga'],
    ];

    $dataPribadiFields = [
        ['id' => 'nama_lengkap', 'label' => 'Nama Lengkap', 'icon' => 'user', 'type' => 'text', 'placeholder' => 'Masukkan nama lengkap'],
        ['id' => 'jenis_kelamin', 'label' => 'Jenis Kelamin', 'icon' => 'users', 'type' => 'select', 'options' => ['L' => '👨 Laki-laki', 'P' => '👩 Perempuan'], 'placeholder' => 'Pilih Jenis Kelamin'],
        ['id' => 'nik', 'label' => 'NIK', 'icon' => 'id-card', 'type' => 'text', 'placeholder' => '16 digit NIK', 'maxlength' => 16],
        ['id' => 'nisn', 'label' => 'NISN', 'icon' => 'graduation-cap', 'type' => 'text', 'placeholder' => '10 digit NISN', 'maxlength' => 10],
        ['id' => 'tempat_lahir', 'label' => 'Tempat Lahir', 'icon' => 'location', 'type' => 'text', 'placeholder' => 'Kota tempat lahir'],
        ['id' => 'tanggal_lahir', 'label' => 'Tanggal Lahir', 'icon' => 'calendar', 'type' => 'date'],
        ['id' => 'agama', 'label' => 'Agama', 'icon' => 'book-open', 'type' => 'text', 'placeholder' => 'Agama yang dianut'],
        ['id' => 'asal_sekolah', 'label' => 'Asal Sekolah', 'icon' => 'graduation-cap', 'type' => 'text', 'placeholder' => 'Nama sekolah asal'],
        ['id' => 'alamat', 'label' => 'Alamat', 'icon' => 'home', 'type' => 'textarea', 'placeholder' => 'Alamat lengkap tempat tinggal', 'span' => 'md:col-span-2'],
        ['id' => 'no_hp', 'label' => 'No. HP Siswa', 'icon' => 'mobile', 'type' => 'tel', 'placeholder' => '08xxxxxxxxxx'],
        ['id' => 'email', 'label' => 'Email', 'icon' => 'envelope', 'type' => 'email', 'placeholder' => 'email@contoh.com'],
    ];

    $dataOrangTuaFields = [
        ['id' => 'nama_ayah', 'label' => 'Nama Ayah', 'icon' => 'user', 'type' => 'text', 'placeholder' => 'Nama lengkap ayah'],
        ['id' => 'pekerjaan_ayah', 'label' => 'Pekerjaan Ayah', 'icon' => 'briefcase', 'type' => 'text', 'placeholder' => 'Pekerjaan ayah'],
        ['id' => 'nama_ibu', 'label' => 'Nama Ibu', 'icon' => 'user', 'type' => 'text', 'placeholder' => 'Nama lengkap ibu'],
        ['id' => 'pekerjaan_ibu', 'label' => 'Pekerjaan Ibu', 'icon' => 'briefcase', 'type' => 'text', 'placeholder' => 'Pekerjaan ibu'],
        ['id' => 'no_hp_orang_tua', 'label' => 'No. HP Orang Tua', 'icon' => 'phone', 'type' => 'tel', 'placeholder' => '08xxxxxxxxxx', 'span' => 'md:col-span-2'],
    ];

    $uploadFields = [
        ['id' => 'ijazah_path', 'label' => 'Ijazah', 'icon' => 'document', 'accept' => '.pdf', 'hint' => 'PDF, maks 5MB', 'required' => true],
        ['id' => 'kk_path', 'label' => 'Kartu Keluarga', 'icon' => 'document', 'accept' => '.pdf', 'hint' => 'PDF, maks 5MB', 'required' => true],
        ['id' => 'akta_path', 'label' => 'Akta Kelahiran', 'icon' => 'document', 'accept' => '.pdf', 'hint' => 'PDF, maks 5MB', 'required' => true],
        ['id' => 'foto_path', 'label' => 'Pas Foto', 'icon' => 'image', 'accept' => 'image/*', 'hint' => 'JPG/PNG, maks 2MB', 'required' => true],
        ['id' => 'skl_path', 'label' => 'Surat Keterangan Lulus', 'icon' => 'document', 'accept' => '.pdf', 'hint' => 'PDF, maks 5MB', 'required' => true],
        ['id' => 'krm_path', 'label' => 'KRM', 'icon' => 'document', 'accept' => '.pdf,image/*', 'hint' => 'PDF/JPG, maks 5MB (opsional)', 'required' => false],
        ['id' => 'kip_path', 'label' => 'KIP', 'icon' => 'document', 'accept' => '.pdf,image/*', 'hint' => 'PDF/JPG, maks 5MB (opsional)', 'required' => false],
    ];

    $stepLabels = [1 => 'Jalur', 2 => 'Data Pribadi', 3 => 'Data Ortu', 4 => 'Upload Berkas'];

    $renderIcon = fn ($key) => $icons[$key] ?? $icons['check-circle'];
@endphp

@section('content')

    @include('spmb.partials.navbar', ['transparent' => true])

    {{-- ============ HERO ============ --}}
    <section class="relative bg-gradient-primary py-24 overflow-hidden">
        <div class="absolute top-16 right-10 w-64 h-64 bg-secondary-400/20 blob-shape animate-float"></div>
        <div class="absolute bottom-10 left-10 w-72 h-72 bg-accent-400/20 blob-shape animate-float" style="animation-delay: 1s;"></div>

        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid lg:grid-cols-2 gap-12 items-center">

                <div class="text-white space-y-6 animate-fade-in-up">
                    <div class="inline-flex items-center px-4 py-2 bg-secondary-500 rounded-full text-sm font-semibold shadow-lg">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            @foreach ($renderIcon('sparkles') as $d)
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $d }}"/>
                            @endforeach
                        </svg>
                        Tahun Ajaran {{ date('Y') }}/{{ date('Y') + 1 }}
                    </div>

                    <h1 class="text-4xl md:text-5xl lg:text-6xl font-extrabold leading-tight">
                        Satu Langkah Menuju<br/>
                        <span class="text-secondary-300">Masa Depan Gemilang</span>
                    </h1>

                    <p class="text-lg md:text-xl text-white/90 leading-relaxed max-w-xl">
                        Isi formulir pendaftaran online — hanya butuh beberapa menit untuk memulai perjalanan baru bersama kami.
                    </p>

                    <div class="flex flex-col sm:flex-row gap-4 pt-2">
                        <a href="#form-pendaftaran"
                           class="inline-flex items-center justify-center px-8 py-4 text-lg font-bold text-primary-700 bg-white rounded-2xl hover:bg-gray-50 transition shadow-xl hover:shadow-2xl hover:scale-105 transform">
                            <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                @foreach ($renderIcon('bolt') as $d)
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $d }}"/>
                                @endforeach
                            </svg>
                            Mulai Pendaftaran
                        </a>
                        <a href="#info-ppdb"
                           class="inline-flex items-center justify-center px-8 py-4 text-lg font-bold text-white border-2 border-white/60 rounded-2xl hover:bg-white hover:text-primary-700 transition">
                            Lihat Jadwal SPMB
                        </a>
                    </div>
                </div>

                {{-- Illustration + floating cards --}}
                <div class="relative hidden lg:block animate-fade-in-up" style="animation-delay: 0.3s;">
                    <div class="relative z-10 bg-white/10 backdrop-blur-sm rounded-3xl p-3 border border-white/20">
                        <img src="https://via.placeholder.com/560x480?text=Siswa+Mengisi+Formulir"
                             alt="Ilustrasi pendaftaran siswa baru"
                             class="rounded-2xl shadow-2xl w-full h-auto object-cover">
                    </div>

                    <div class="absolute -top-6 -left-6 z-20 bg-white rounded-2xl p-4 shadow-xl animate-float">
                        <div class="flex items-center space-x-3">
                            <div class="w-11 h-11 bg-accent-500 rounded-xl flex items-center justify-center flex-shrink-0">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    @foreach ($renderIcon('check-circle') as $d)
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $d }}"/>
                                    @endforeach
                                </svg>
                            </div>
                            <div>
                                <div class="text-sm font-bold text-gray-800">4 Langkah Mudah</div>
                                <div class="text-xs text-gray-500">Selesai dalam 10 menit</div>
                            </div>
                        </div>
                    </div>

                    <div class="absolute -bottom-6 -right-6 z-20 bg-white rounded-2xl p-4 shadow-xl animate-float" style="animation-delay: 1.5s;">
                        <div class="flex items-center space-x-3">
                            <div class="w-11 h-11 bg-secondary-500 rounded-xl flex items-center justify-center flex-shrink-0">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    @foreach ($renderIcon('shield-check') as $d)
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $d }}"/>
                                    @endforeach
                                </svg>
                            </div>
                            <div>
                                <div class="text-sm font-bold text-gray-800">Data Terlindungi</div>
                                <div class="text-xs text-gray-500">Aman & rahasia</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Trust strip --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-16">
                @foreach ($trustStrip as $item)
                    <div class="flex items-center gap-4 bg-white/10 backdrop-blur-sm border border-white/10 rounded-2xl p-5">
                        <div class="w-12 h-12 bg-white/15 rounded-xl flex items-center justify-center flex-shrink-0">
                            <svg class="w-6 h-6 text-secondary-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                @foreach ($renderIcon($item['icon']) as $d)
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $d }}"/>
                                @endforeach
                            </svg>
                        </div>
                        <div>
                            <div class="text-white font-bold">{{ $item['title'] }}</div>
                            <div class="text-white/70 text-sm">{{ $item['desc'] }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    @if ($errors->any())
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 -mt-8 relative z-10">
            <div class="bg-red-50 border-l-4 border-red-500 p-6 rounded-2xl shadow-lg">
                <div class="flex items-start">
                    <svg class="w-6 h-6 text-red-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        @foreach ($renderIcon('exclamation-triangle') as $d)
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $d }}"/>
                        @endforeach
                    </svg>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-red-800">Ada kesalahan dalam pengisian form:</h3>
                        <ul class="mt-2 text-sm text-red-700 list-disc pl-5 space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- ============ INFO PPDB ============ --}}
    <section id="info-ppdb" class="py-20 bg-slate-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            <div class="mb-16">
                <div class="text-center mb-12">
                    <span class="inline-block px-4 py-1.5 bg-primary-100 text-primary-700 rounded-full text-sm font-semibold mb-4">Jadwal</span>
                    <h2 class="text-3xl md:text-4xl font-extrabold text-gray-800 mb-4">Timeline SPMB</h2>
                    <p class="text-lg text-gray-600 max-w-2xl mx-auto">Ikuti setiap tahapan pendaftaran dengan cermat</p>
                </div>

                <div class="relative">
                    <div class="absolute left-1/2 -translate-x-1/2 h-full w-1 bg-gradient-to-b from-primary-500 to-accent-500 rounded-full"></div>

                    @foreach ($jadwalPpdb as $index => $jadwal)
                        @php $iconKey = $timelineIcons[$jadwal->nama_jadwal] ?? 'calendar'; @endphp
                        <div class="relative flex items-center mb-8 {{ $index % 2 == 0 ? 'flex-row' : 'flex-row-reverse' }}">
                            <div class="w-5/12 {{ $index % 2 == 0 ? 'text-right pr-8' : 'text-left pl-8' }}">
                                <div class="bg-white rounded-2xl shadow-lg p-6 hover:shadow-2xl transition-all duration-300 border border-gray-100 card-hover">
                                    <div class="flex items-center {{ $index % 2 == 0 ? 'justify-end' : 'justify-start' }} mb-3">
                                        <div class="w-12 h-12 bg-gradient-primary rounded-full flex items-center justify-center text-white shadow-md">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                @foreach ($renderIcon($iconKey) as $d)
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $d }}"/>
                                                @endforeach
                                            </svg>
                                        </div>
                                    </div>
                                    <h3 class="text-lg font-bold text-gray-800 mb-2">{{ $jadwal->nama_jadwal }}</h3>
                                    <p class="text-sm text-gray-600 mb-2">{{ $jadwal->tanggal_mulai }} - {{ $jadwal->tanggal_selesai }}</p>
                                    @if ($jadwal->keterangan)
                                        <p class="text-sm text-gray-500">{{ $jadwal->keterangan }}</p>
                                    @endif
                                </div>
                            </div>
                            <div class="absolute left-1/2 -translate-x-1/2 w-6 h-6 bg-white border-4 border-primary-500 rounded-full shadow-lg z-10"></div>
                            <div class="w-5/12"></div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="grid md:grid-cols-2 gap-8">
                <div class="bg-white rounded-3xl shadow-xl p-8 hover:shadow-2xl transition-all duration-300 border border-gray-100 card-hover">
                    <div class="flex items-center mb-6">
                        <div class="w-16 h-16 bg-gradient-to-r from-accent-500 to-accent-600 rounded-2xl flex items-center justify-center text-white mr-4 flex-shrink-0 shadow-md">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                @foreach ($renderIcon('clipboard') as $d)
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $d }}"/>
                                @endforeach
                            </svg>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-800">Persyaratan Pendaftaran</h3>
                    </div>
                    <div class="space-y-4">
                        @foreach ($persyaratanList as $item)
                            <div class="flex items-start p-4 bg-gray-50 rounded-xl hover:bg-gray-100 transition-colors duration-200">
                                <div class="w-8 h-8 bg-accent-500 rounded-full flex items-center justify-center text-white mr-4 mt-1 flex-shrink-0">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </div>
                                <span class="text-gray-700">{{ $item }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="bg-gradient-primary rounded-3xl shadow-xl p-8 text-white">
                    <div class="flex items-center mb-6">
                        <div class="w-16 h-16 bg-white/20 backdrop-blur-sm rounded-2xl flex items-center justify-center mr-4 flex-shrink-0">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                @foreach ($renderIcon('info-circle') as $d)
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $d }}"/>
                                @endforeach
                            </svg>
                        </div>
                        <h3 class="text-2xl font-bold">Informasi Penting</h3>
                    </div>
                    <div class="space-y-4">
                        @foreach ($infoPentingList as $info)
                            <div class="flex items-start p-4 bg-white/10 backdrop-blur-sm rounded-xl">
                                <svg class="w-5 h-5 mt-1 mr-4 text-secondary-300 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    @foreach ($renderIcon($info['icon']) as $d)
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $d }}"/>
                                    @endforeach
                                </svg>
                                <span>{{ $info['text'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ============ FORM WIZARD ============ --}}
    <section id="form-pendaftaran" class="py-20 bg-white relative overflow-hidden">
        <div class="absolute top-0 right-0 w-96 h-96 bg-primary-50 rounded-full blur-3xl -z-0"></div>
        <div class="absolute bottom-0 left-0 w-96 h-96 bg-accent-50 rounded-full blur-3xl -z-0"></div>

        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 relative">
            <div class="text-center mb-12">
                <span class="inline-block px-4 py-1.5 bg-accent-100 text-accent-700 rounded-full text-sm font-semibold mb-4">Formulir</span>
                <h2 class="text-3xl md:text-4xl font-extrabold text-gray-800 mb-4">Yuk, Daftar Sekarang!</h2>
                <p class="text-lg text-gray-600 max-w-2xl mx-auto">Isi bertahap, tinggal ikuti 4 langkah mudah di bawah ini</p>
            </div>

            @if (session('success'))
                <div class="bg-green-50 border-l-4 border-green-500 p-6 rounded-2xl shadow-lg mb-8">
                    <div class="flex items-center">
                        <svg class="w-6 h-6 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            @foreach ($renderIcon('check-circle') as $d)
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $d }}"/>
                            @endforeach
                        </svg>
                        <p class="ml-3 text-sm text-green-700 font-medium">{{ session('success') }}</p>
                    </div>
                </div>
            @endif

            <div class="bg-white rounded-3xl shadow-2xl border border-gray-100 overflow-hidden">
                <form method="POST" action="{{ route('spmb.store') }}" enctype="multipart/form-data" id="form-wizard" class="p-6 md:p-10">
                    @csrf

                    {{-- Progress Steps --}}
                    <div class="mb-12 sticky top-2 z-20">
                        <div class="bg-white/90 backdrop-blur-sm rounded-2xl p-4 shadow-sm">
                            <div class="flex items-center justify-between">
                                @foreach ($stepLabels as $num => $label)
                                    <div class="flex items-center flex-1 {{ $num == count($stepLabels) ? 'flex-none' : '' }}" data-step-indicator="{{ $num }}">
                                        <div class="flex flex-col items-center flex-shrink-0">
                                            <div class="step-circle w-10 h-10 rounded-full flex items-center justify-center font-bold transition-all duration-300 {{ $num == 1 ? 'bg-primary-600 text-white ring-4 ring-primary-200' : 'bg-gray-200 text-gray-500' }}">
                                                {{ $num }}
                                            </div>
                                            <span class="text-xs font-medium mt-1 hidden sm:block {{ $num == 1 ? 'text-primary-600' : 'text-gray-400' }}">{{ $label }}</span>
                                        </div>
                                        @if ($num < count($stepLabels))
                                            <div class="flex-1 h-1 bg-gray-200 rounded-full mx-2 sm:mx-4"></div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    {{-- Step 1: Jalur Pendaftaran --}}
                    <div data-step="1" class="animate-fade-in-up">
                        <div class="bg-primary-50 rounded-2xl p-6 md:p-8 mb-8 border border-primary-100">
                            <div class="flex items-center mb-6">
                                <div class="w-12 h-12 bg-primary-600 rounded-xl flex items-center justify-center text-white mr-4 flex-shrink-0 shadow-md">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        @foreach ($renderIcon('route') as $d)
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $d }}"/>
                                        @endforeach
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-xl font-bold text-gray-800">Pilih Jalur Pendaftaran</h3>
                                    <p class="text-sm text-gray-500">Langkah 1 dari 4</p>
                                </div>
                            </div>

                            <label for="jalur_pendaftaran_id" class="block text-sm font-semibold text-gray-700 mb-2">
                                Jalur Pendaftaran <span class="text-red-500">*</span>
                            </label>
                            <select id="jalur_pendaftaran_id" name="jalur_pendaftaran_id" required
                                    class="w-full px-4 py-4 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500 bg-white shadow-sm transition-all duration-200 @error('jalur_pendaftaran_id') border-red-500 ring-2 ring-red-200 @enderror">
                                <option value="">🎯 Pilih Jalur Pendaftaran</option>
                                @foreach ($jalurPendaftarans as $jalur)
                                    @if ($jalur->aktif)
                                        <option value="{{ $jalur->id }}" data-wajib-sertifikat="{{ $jalur->wajib_sertifikat ? 1 : 0 }}" {{ old('jalur_pendaftaran_id') == $jalur->id ? 'selected' : '' }}>{{ $jalur->nama_jalur }}</option>
                                    @endif
                                @endforeach
                            </select>
                            @error('jalur_pendaftaran_id')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex justify-end">
                            <button type="button" data-next="2"
                                    class="inline-flex items-center px-6 py-3 bg-primary-600 text-white font-semibold rounded-xl hover:bg-primary-700 transition shadow-md hover:shadow-lg">
                                Lanjut
                                <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    @foreach ($renderIcon('arrow-right') as $d)
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $d }}"/>
                                    @endforeach
                                </svg>
                            </button>
                        </div>
                    </div>

                    {{-- Step 2: Data Pribadi --}}
                    <div data-step="2" class="hidden">
                        <div class="bg-accent-50 rounded-2xl p-6 md:p-8 mb-8 border border-accent-100">
                            <div class="flex items-center mb-6">
                                <div class="w-12 h-12 bg-accent-600 rounded-xl flex items-center justify-center text-white mr-4 flex-shrink-0 shadow-md">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        @foreach ($renderIcon('user') as $d)
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $d }}"/>
                                        @endforeach
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-xl font-bold text-gray-800">Data Pribadi Siswa</h3>
                                    <p class="text-sm text-gray-500">Langkah 2 dari 4</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                @foreach ($dataPribadiFields as $field)
                                    <div class="space-y-2 {{ $field['span'] ?? '' }}">
                                        <label for="{{ $field['id'] }}" class="block text-sm font-semibold text-gray-700">
                                            {{ $field['label'] }} <span class="text-red-500">*</span>
                                        </label>
                                        <div class="relative">
                                            <svg class="w-5 h-5 absolute left-4 top-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                @foreach ($renderIcon($field['icon']) as $d)
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $d }}"/>
                                                @endforeach
                                            </svg>

                                            @if ($field['type'] === 'select')
                                                <select id="{{ $field['id'] }}" name="{{ $field['id'] }}" required
                                                        class="w-full pl-12 pr-4 py-4 border border-gray-300 rounded-xl focus:ring-2 focus:ring-accent-500 focus:border-accent-500 bg-white shadow-sm transition-all duration-200 @error($field['id']) border-red-500 ring-2 ring-red-200 @enderror">
                                                    <option value="">{{ $field['placeholder'] }}</option>
                                                    @foreach ($field['options'] as $val => $optLabel)
                                                        <option value="{{ $val }}" {{ old($field['id']) == $val ? 'selected' : '' }}>{{ $optLabel }}</option>
                                                    @endforeach
                                                </select>
                                            @elseif ($field['type'] === 'textarea')
                                                <textarea id="{{ $field['id'] }}" name="{{ $field['id'] }}" required rows="3"
                                                          class="w-full pl-12 pr-4 py-4 border border-gray-300 rounded-xl focus:ring-2 focus:ring-accent-500 focus:border-accent-500 bg-white shadow-sm transition-all duration-200 @error($field['id']) border-red-500 ring-2 ring-red-200 @enderror"
                                                          placeholder="{{ $field['placeholder'] }}">{{ old($field['id']) }}</textarea>
                                            @else
                                                <input type="{{ $field['type'] }}" id="{{ $field['id'] }}" name="{{ $field['id'] }}" required
                                                       @if (isset($field['maxlength'])) maxlength="{{ $field['maxlength'] }}" @endif
                                                       class="w-full pl-12 pr-4 py-4 border border-gray-300 rounded-xl focus:ring-2 focus:ring-accent-500 focus:border-accent-500 bg-white shadow-sm transition-all duration-200 @error($field['id']) border-red-500 ring-2 ring-red-200 @enderror"
                                                       value="{{ old($field['id']) }}" placeholder="{{ $field['placeholder'] ?? '' }}">
                                            @endif
                                        </div>
                                        @error($field['id'])
                                            <p class="text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="flex justify-between">
                            <button type="button" data-prev="1"
                                    class="inline-flex items-center px-6 py-3 bg-gray-100 text-gray-700 font-semibold rounded-xl hover:bg-gray-200 transition">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    @foreach ($renderIcon('arrow-left') as $d)
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $d }}"/>
                                    @endforeach
                                </svg>
                                Kembali
                            </button>
                            <button type="button" data-next="3"
                                    class="inline-flex items-center px-6 py-3 bg-accent-600 text-white font-semibold rounded-xl hover:bg-accent-700 transition shadow-md hover:shadow-lg">
                                Lanjut
                                <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    @foreach ($renderIcon('arrow-right') as $d)
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $d }}"/>
                                    @endforeach
                                </svg>
                            </button>
                        </div>
                    </div>

                    {{-- Step 3: Data Orang Tua --}}
                    <div data-step="3" class="hidden">
                        <div class="bg-secondary-50 rounded-2xl p-6 md:p-8 mb-8 border border-secondary-100">
                            <div class="flex items-center mb-6">
                                <div class="w-12 h-12 bg-secondary-600 rounded-xl flex items-center justify-center text-white mr-4 flex-shrink-0 shadow-md">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        @foreach ($renderIcon('users') as $d)
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $d }}"/>
                                        @endforeach
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-xl font-bold text-gray-800">Data Orang Tua</h3>
                                    <p class="text-sm text-gray-500">Langkah 3 dari 4</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                @foreach ($dataOrangTuaFields as $field)
                                    <div class="space-y-2 {{ $field['span'] ?? '' }}">
                                        <label for="{{ $field['id'] }}" class="block text-sm font-semibold text-gray-700">
                                            {{ $field['label'] }} <span class="text-red-500">*</span>
                                        </label>
                                        <div class="relative">
                                            <svg class="w-5 h-5 absolute left-4 top-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                @foreach ($renderIcon($field['icon']) as $d)
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $d }}"/>
                                                @endforeach
                                            </svg>
                                            <input type="{{ $field['type'] }}" id="{{ $field['id'] }}" name="{{ $field['id'] }}" required
                                                   class="w-full pl-12 pr-4 py-4 border border-gray-300 rounded-xl focus:ring-2 focus:ring-secondary-500 focus:border-secondary-500 bg-white shadow-sm transition-all duration-200 @error($field['id']) border-red-500 ring-2 ring-red-200 @enderror"
                                                   value="{{ old($field['id']) }}" placeholder="{{ $field['placeholder'] }}">
                                        </div>
                                        @error($field['id'])
                                            <p class="text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="flex justify-between">
                            <button type="button" data-prev="2"
                                    class="inline-flex items-center px-6 py-3 bg-gray-100 text-gray-700 font-semibold rounded-xl hover:bg-gray-200 transition">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    @foreach ($renderIcon('arrow-left') as $d)
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $d }}"/>
                                    @endforeach
                                </svg>
                                Kembali
                            </button>
                            <button type="button" data-next="4"
                                    class="inline-flex items-center px-6 py-3 bg-secondary-500 text-white font-semibold rounded-xl hover:bg-secondary-600 transition shadow-md hover:shadow-lg">
                                Lanjut
                                <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    @foreach ($renderIcon('arrow-right') as $d)
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $d }}"/>
                                    @endforeach
                                </svg>
                            </button>
                        </div>
                    </div>

                    {{-- Step 4: Upload Berkas --}}
                    <div data-step="4" class="hidden">
                        <div class="bg-orange-50 rounded-2xl p-6 md:p-8 mb-8 border border-orange-100">
                            <div class="flex items-center mb-6">
                                <div class="w-12 h-12 bg-orange-500 rounded-xl flex items-center justify-center text-white mr-4 flex-shrink-0 shadow-md">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        @foreach ($renderIcon('upload') as $d)
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $d }}"/>
                                        @endforeach
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-xl font-bold text-gray-800">Upload Berkas Persyaratan</h3>
                                    <p class="text-sm text-gray-500">Langkah 4 dari 4 — hampir selesai!</p>
                                </div>
                            </div>

                            <div class="grid sm:grid-cols-2 gap-6">
                                @foreach ($uploadFields as $field)
                                    <div class="space-y-2 {{ $loop->last ? 'sm:col-span-2' : '' }}">
                                        <label for="{{ $field['id'] }}" class="block text-sm font-semibold text-gray-700">
                                            {{ $field['label'] }} @if($field['required'] ?? true)<span class="text-red-500">*</span>@else<span class="text-gray-400 font-normal">(opsional)</span>@endif
                                        </label>
                                        <div class="relative border-2 border-dashed border-gray-300 rounded-xl p-6 hover:border-orange-500 transition-colors duration-200 bg-white">
                                            <div class="text-center pointer-events-none">
                                                <svg class="w-9 h-9 mx-auto text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                    @foreach ($renderIcon($field['icon']) as $d)
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $d }}"/>
                                                    @endforeach
                                                </svg>
                                                <div class="text-sm text-gray-600 mb-1">
                                                    <strong>Klik untuk upload</strong>
                                                </div>
                                                <div class="text-xs text-gray-500">{{ $field['hint'] }}</div>
                                            </div>
                                            <input type="file" id="{{ $field['id'] }}" name="{{ $field['id'] }}" accept="{{ $field['accept'] }}" @if($field['required'] ?? true) required @endif
                                                   class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                                        </div>
                                        @error($field['id'])
                                            <p class="text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                @endforeach
                            </div>

                            {{-- Sertifikat prestasi: tampil hanya bila jalur mewajibkan --}}
                            @php $oldSertifikat = old('sertifikat', []); @endphp
                            <div id="sertifikat-section" class="mt-8 {{ collect($oldSertifikat)->isNotEmpty() ? '' : 'hidden' }}">
                                <h4 class="text-lg font-bold text-gray-800 mb-1">Sertifikat Prestasi <span class="text-red-500">*</span></h4>
                                <p class="text-sm text-gray-500 mb-4">Jalur yang dipilih mewajibkan minimal 1 sertifikat (maks 5). PDF/JPG, maks 5MB per file.</p>
                                <div id="sertifikat-rows" class="space-y-4">
                                    @forelse ($oldSertifikat as $i => $row)
                                        <div class="sertifikat-row grid grid-cols-1 md:grid-cols-[1fr_1fr_auto] gap-4 items-end bg-white border border-gray-200 rounded-xl p-4">
                                            <div>
                                                <label class="block text-sm font-semibold text-gray-700 mb-2">Nama Kejuaraan <span class="text-red-500">*</span></label>
                                                <input type="text" name="sertifikat[{{ $i }}][nama]" value="{{ $row['nama'] ?? '' }}" placeholder="cth: Juara 1 Pencak Silat Provinsi 2025"
                                                       class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-orange-500 bg-white shadow-sm transition-all duration-200" />
                                                @error("sertifikat.{$i}.nama")<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                                            </div>
                                            <div>
                                                <label class="block text-sm font-semibold text-gray-700 mb-2">File Sertifikat <span class="text-red-500">*</span></label>
                                                <input type="file" name="sertifikat[{{ $i }}][file]" accept=".pdf,.jpg,.jpeg,.png"
                                                       class="w-full px-4 py-3 border border-gray-300 rounded-xl bg-white shadow-sm text-sm text-gray-600" />
                                                @error("sertifikat.{$i}.file")<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                                            </div>
                                            <button type="button" class="sertifikat-remove inline-flex items-center justify-center w-11 h-11 bg-red-50 text-red-600 font-semibold rounded-xl hover:bg-red-100 transition" title="Hapus baris">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                            </button>
                                        </div>
                                    @empty
                                        <div class="sertifikat-row grid grid-cols-1 md:grid-cols-[1fr_1fr_auto] gap-4 items-end bg-white border border-gray-200 rounded-xl p-4">
                                            <div>
                                                <label class="block text-sm font-semibold text-gray-700 mb-2">Nama Kejuaraan <span class="text-red-500">*</span></label>
                                                <input type="text" name="sertifikat[0][nama]" value="" placeholder="cth: Juara 1 Pencak Silat Provinsi 2025"
                                                       class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-orange-500 bg-white shadow-sm transition-all duration-200" />
                                            </div>
                                            <div>
                                                <label class="block text-sm font-semibold text-gray-700 mb-2">File Sertifikat <span class="text-red-500">*</span></label>
                                                <input type="file" name="sertifikat[0][file]" accept=".pdf,.jpg,.jpeg,.png"
                                                       class="w-full px-4 py-3 border border-gray-300 rounded-xl bg-white shadow-sm text-sm text-gray-600" />
                                            </div>
                                            <button type="button" class="sertifikat-remove inline-flex items-center justify-center w-11 h-11 bg-red-50 text-red-600 font-semibold rounded-xl hover:bg-red-100 transition" title="Hapus baris">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                            </button>
                                        </div>
                                    @endforelse
                                </div>
                                <button type="button" id="sertifikat-add"
                                        class="mt-4 inline-flex items-center px-5 py-2.5 bg-white border-2 border-dashed border-orange-400 text-orange-600 font-semibold rounded-xl hover:bg-orange-50 transition">
                                    + Tambah Sertifikat (maks 5)
                                </button>
                                @error('sertifikat')<p class="text-sm text-red-600 mt-2">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        <div class="flex justify-between items-center">
                            <button type="button" data-prev="3"
                                    class="inline-flex items-center px-6 py-3 bg-gray-100 text-gray-700 font-semibold rounded-xl hover:bg-gray-200 transition">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    @foreach ($renderIcon('arrow-left') as $d)
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $d }}"/>
                                    @endforeach
                                </svg>
                                Kembali
                            </button>
                            <button type="submit"
                                    class="inline-flex items-center px-8 py-4 bg-gradient-primary text-white text-lg font-semibold rounded-full shadow-xl hover:shadow-2xl transform hover:scale-105 transition-all duration-300 focus:ring-4 focus:ring-primary-300">
                                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    @foreach ($renderIcon('paper-plane') as $d)
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $d }}"/>
                                    @endforeach
                                </svg>
                                Kirim Pendaftaran
                            </button>
                        </div>
                        <p class="text-sm text-gray-500 mt-4 text-center">
                            Dengan mengirim formulir ini, Anda menyetujui syarat dan ketentuan yang berlaku
                        </p>
                    </div>
                </form>
            </div>
        </div>
    </section>

    {{-- ============ CTA BANTUAN ============ --}}
    <section class="bg-gradient-primary py-16">
        <div class="max-w-4xl mx-auto text-center px-4 sm:px-6 lg:px-8">
            <h2 class="text-3xl font-bold text-white mb-4">Butuh Bantuan?</h2>
            <p class="text-xl text-white/90 mb-8">Tim kami siap membantu Anda dalam proses pendaftaran</p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="tel:{{ $profileSekolah->telp }}"
                   class="inline-flex items-center justify-center px-6 py-3 bg-white text-primary-600 rounded-xl font-semibold hover:bg-gray-100 transition-colors duration-200">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        @foreach ($renderIcon('phone') as $d)
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $d }}"/>
                        @endforeach
                    </svg>
                    {{ $profileSekolah->telp }}
                </a>
                <a href="mailto:{{ $profileSekolah->email }}"
                   class="inline-flex items-center justify-center px-6 py-3 bg-white/10 text-white rounded-xl font-semibold hover:bg-white/20 transition-colors duration-200 border border-white/20">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        @foreach ($renderIcon('envelope') as $d)
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $d }}"/>
                        @endforeach
                    </svg>
                    {{ $profileSekolah->email }}
                </a>
            </div>
        </div>
    </section>

    @include('spmb.partials.footer')

    <script>
        (function () {
            const form = document.getElementById('form-wizard');
            const totalSteps = {{ count($stepLabels) }};
            let current = 1;

            function stepEl(n) {
                return form.querySelector(`[data-step="${n}"]`);
            }

            function validateStep(n) {
                const fields = stepEl(n).querySelectorAll('input[required], select[required], textarea[required]');
                let valid = true;
                fields.forEach((f) => {
                    if (!f.checkValidity()) {
                        f.classList.add('border-red-500');
                        valid = false;
                    } else {
                        f.classList.remove('border-red-500');
                    }
                });
                if (!valid) fields[0]?.reportValidity();
                return valid;
            }

            function updateIndicators() {
                form.querySelectorAll('[data-step-indicator]').forEach((wrap) => {
                    const num = parseInt(wrap.dataset.stepIndicator, 10);
                    const circle = wrap.querySelector('.step-circle');
                    const label = wrap.querySelector('span');
                    circle.classList.remove('bg-primary-600', 'text-white', 'ring-4', 'ring-primary-200', 'bg-gray-200', 'text-gray-500');
                    label?.classList.remove('text-primary-600', 'text-gray-400');
                    if (num < current) {
                        circle.classList.add('bg-primary-600', 'text-white');
                        label?.classList.add('text-primary-600');
                    } else if (num === current) {
                        circle.classList.add('bg-primary-600', 'text-white', 'ring-4', 'ring-primary-200');
                        label?.classList.add('text-primary-600');
                    } else {
                        circle.classList.add('bg-gray-200', 'text-gray-500');
                        label?.classList.add('text-gray-400');
                    }
                });
            }

            function showStep(n) {
                for (let i = 1; i <= totalSteps; i++) {
                    const el = stepEl(i);
                    if (!el) continue;
                    el.classList.toggle('hidden', i !== n);
                    if (i === n) el.classList.add('animate-fade-in-up');
                }
                current = n;
                updateIndicators();
                stepEl(n).scrollIntoView({ behavior: 'smooth', block: 'start' });
            }

            form.querySelectorAll('[data-next]').forEach((btn) => {
                btn.addEventListener('click', () => {
                    const fromStep = current;
                    const toStep = parseInt(btn.dataset.next, 10);
                    if (validateStep(fromStep)) showStep(toStep);
                });
            });

            form.querySelectorAll('[data-prev]').forEach((btn) => {
                btn.addEventListener('click', () => showStep(parseInt(btn.dataset.prev, 10)));
            });

            // Sertifikat prestasi dinamis (wajib bila jalur ber-flag)
            const jalurSelect = document.getElementById('jalur_pendaftaran_id');
            const sertSection = document.getElementById('sertifikat-section');
            const sertRows = document.getElementById('sertifikat-rows');
            const sertAdd = document.getElementById('sertifikat-add');
            let sertIndex = sertRows.querySelectorAll('.sertifikat-row').length;

            function sertifikatWajib() {
                const opt = jalurSelect.selectedOptions[0];
                return !!(opt && opt.dataset.wajibSertifikat === '1');
            }

            function toggleSertifikat() {
                const wajib = sertifikatWajib();
                sertSection.classList.toggle('hidden', !wajib);
                sertRows.querySelectorAll('.sertifikat-row').forEach((row) => {
                    row.querySelectorAll('input').forEach((inp) => { inp.required = wajib; });
                });
            }

            function refreshSertifikatButtons() {
                const rows = sertRows.querySelectorAll('.sertifikat-row');
                rows.forEach((row) => {
                    row.querySelector('.sertifikat-remove').style.display = rows.length > 1 ? '' : 'none';
                });
                sertAdd.disabled = rows.length >= 5;
                sertAdd.classList.toggle('opacity-50', rows.length >= 5);
            }

            sertAdd.addEventListener('click', () => {
                if (sertRows.querySelectorAll('.sertifikat-row').length >= 5) return;
                const idx = sertIndex++;
                const div = document.createElement('div');
                div.className = 'sertifikat-row grid grid-cols-1 md:grid-cols-[1fr_1fr_auto] gap-4 items-end bg-white border border-gray-200 rounded-xl p-4';
                div.innerHTML =
                    `<div><label class="block text-sm font-semibold text-gray-700 mb-2">Nama Kejuaraan <span class="text-red-500">*</span></label>` +
                    `<input type="text" name="sertifikat[${idx}][nama]" placeholder="cth: Juara 1 Pencak Silat Provinsi 2025" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-orange-500 bg-white shadow-sm transition-all duration-200" /></div>` +
                    `<div><label class="block text-sm font-semibold text-gray-700 mb-2">File Sertifikat <span class="text-red-500">*</span></label>` +
                    `<input type="file" name="sertifikat[${idx}][file]" accept=".pdf,.jpg,.jpeg,.png" class="w-full px-4 py-3 border border-gray-300 rounded-xl bg-white shadow-sm text-sm text-gray-600" /></div>` +
                    `<button type="button" class="sertifikat-remove inline-flex items-center justify-center w-11 h-11 bg-red-50 text-red-600 font-semibold rounded-xl hover:bg-red-100 transition" title="Hapus baris">` +
                    `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>`;
                sertRows.appendChild(div);
                if (sertifikatWajib()) div.querySelectorAll('input').forEach((inp) => { inp.required = true; });
                refreshSertifikatButtons();
            });

            sertRows.addEventListener('click', (e) => {
                const btn = e.target.closest('.sertifikat-remove');
                if (!btn || sertRows.querySelectorAll('.sertifikat-row').length <= 1) return;
                btn.closest('.sertifikat-row').remove();
                refreshSertifikatButtons();
            });

            jalurSelect.addEventListener('change', toggleSertifikat);
            toggleSertifikat();
            refreshSertifikatButtons();

            // Preview file upload
            form.querySelectorAll('input[type="file"]').forEach((input) => {
                input.addEventListener('change', function () {
                    const file = this.files[0];
                    const wrap = this.closest('.border-dashed');
                    const hint = wrap.querySelector('.text-sm.text-gray-600');
                    if (file) {
                        wrap.classList.add('border-accent-500', 'bg-accent-50');
                        wrap.classList.remove('border-gray-300');
                        if (hint) hint.innerHTML = `<strong class="text-accent-600">✓ ${file.name}</strong>`;
                    }
                });
            });

            updateIndicators();
        })();
    </script>

@endsection
