@extends('layouts.spmb')

@section('title', 'Pendaftaran Siswa Baru')

@php
    $icons = [
        'Pendaftaran' => 'fa-calendar-check',
        'Tes Masuk' => 'fa-tasks',
        'Verifikasi Berkas' => 'fa-file-alt',
        'Pengumuman' => 'fa-bell',
        'Daftar Ulang' => 'fa-user-check',
    ];
// TODO: Data dummy sementara — ganti dengan data asli dari controller
    // (mis. JadwalPpdb::orderBy('tanggal_mulai')->get()) bila sudah siap.
    $jadwalPpdb = $jadwalPpdb ?? collect([
        (object) [
            'nama_jadwal' => 'Pendaftaran',
            'tanggal_mulai' => '01 Januari 2026',
            'tanggal_selesai' => '28 Februari 2026',
            'keterangan' => 'Pendaftaran dibuka melalui website resmi sekolah',
        ],
        (object) [
            'nama_jadwal' => 'Tes Masuk',
            'tanggal_mulai' => '05 Maret 2026',
            'tanggal_selesai' => '10 Maret 2026',
            'keterangan' => 'Tes tertulis dan wawancara di lokasi sekolah',
        ],
        (object) [
            'nama_jadwal' => 'Verifikasi Berkas',
            'tanggal_mulai' => '12 Maret 2026',
            'tanggal_selesai' => '15 Maret 2026',
            'keterangan' => 'Pastikan seluruh dokumen persyaratan sudah lengkap',
        ],
        (object) [
            'nama_jadwal' => 'Pengumuman',
            'tanggal_mulai' => '20 Maret 2026',
            'tanggal_selesai' => '20 Maret 2026',
            'keterangan' => 'Hasil seleksi dapat dilihat melalui website ini',
        ],
        (object) [
            'nama_jadwal' => 'Daftar Ulang',
            'tanggal_mulai' => '22 Maret 2026',
            'tanggal_selesai' => '31 Maret 2026',
            'keterangan' => 'Bagi siswa yang dinyatakan lolos seleksi',
        ],
    ]);
@endphp

@section('content')
    <!-- Hero Section -->
    <section class="relative bg-gradient-to-br from-primary-900 via-primary-800 to-primary-950 py-20 overflow-hidden">
        <div class="absolute inset-0 bg-black opacity-20"></div>
        <div class="absolute inset-0">
            <div class="absolute top-10 left-10 w-32 h-32 bg-primary-400 rounded-full opacity-10 animate-pulse"></div>
            <div class="absolute bottom-20 right-20 w-48 h-48 bg-secondary-400 rounded-full opacity-10 animate-bounce"></div>
            <div class="absolute top-1/2 left-1/3 w-24 h-24 bg-accent-400 rounded-full opacity-10 animate-ping"></div>
        </div>

        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <div
                class="inline-flex items-center px-4 py-2 bg-white bg-opacity-10 backdrop-blur-sm rounded-full text-white text-sm mb-6">
                <i class="fas fa-graduation-cap mr-2"></i>
                Tahun Ajaran {{ \Carbon\Carbon::now()->format('Y') }}/{{ \Carbon\Carbon::now()->addYear()->format('Y') }}
            </div>

            <h1 class="text-4xl md:text-6xl font-bold text-white mb-6 leading-tight">
                Pendaftaran
                <span class="bg-gradient-to-r from-secondary-300 to-secondary-500 bg-clip-text text-transparent">
                    Siswa Baru
                </span>
            </h1>

            <p class="text-xl text-gray-200 max-w-3xl mx-auto mb-8 leading-relaxed">
                Bergabunglah dengan keluarga besar sekolah kami dan raih masa depan yang gemilang bersama pendidikan
                berkualitas tinggi
            </p>

            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="#form-pendaftaran"
                    class="inline-flex items-center px-8 py-4 bg-gradient-to-r from-secondary-500 to-secondary-700 text-white rounded-full font-semibold hover:shadow-2xl transform hover:scale-105 transition-all duration-300">
                    <i class="fas fa-rocket mr-2"></i>
                    Daftar Sekarang
                </a>
                <a href="#info-ppdb"
                    class="inline-flex items-center px-8 py-4 bg-white bg-opacity-10 backdrop-blur-sm text-white rounded-full font-semibold hover:bg-opacity-20 transition-all duration-300 border border-white border-opacity-20">
                    <i class="fas fa-info-circle mr-2"></i>
                    Informasi PPDB
                </a>
            </div>
        </div>
    </section>

    @if ($errors->any())
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 -mt-10 relative z-10">
            <div class="bg-red-50 border-l-4 border-red-500 p-6 rounded-lg shadow-lg backdrop-blur-sm">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-triangle text-red-400 text-xl"></i>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-red-800">Ada kesalahan dalam pengisian form:</h3>
                        <div class="mt-2 text-sm text-red-700">
                            <ul class="list-disc pl-5 space-y-1">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <section id="info-ppdb" class="py-20 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Jadwal PPDB Timeline -->
            <div class="mb-16">
                <div class="text-center mb-12">
                    <h2 class="text-3xl md:text-4xl font-bold text-gray-800 mb-4">Timeline PPDB</h2>
                    <p class="text-lg text-gray-600 max-w-2xl mx-auto">Ikuti setiap tahapan pendaftaran dengan cermat</p>
                </div>

                <div class="relative">
                    <div
                        class="absolute left-1/2 transform -translate-x-1/2 h-full w-1 bg-gradient-to-b from-primary-500 to-primary-700 rounded-full">
                    </div>

                    {{-- @foreach ($jadwalPpdb as $index => $jadwal)
                        <div
                            class="relative flex items-center mb-8 {{ $index % 2 == 0 ? 'flex-row' : 'flex-row-reverse' }}">
                            <div class="w-5/12 {{ $index % 2 == 0 ? 'text-right pr-8' : 'text-left pl-8' }}">
                                <div
                                    class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-all duration-300 border border-gray-100">
                                    <div
                                        class="flex items-center {{ $index % 2 == 0 ? 'justify-end' : 'justify-start' }} mb-3">
                                        <div
                                            class="w-12 h-12 bg-gradient-to-r from-primary-500 to-primary-700 rounded-full flex items-center justify-center text-white">
                                            <i class="far {{ $icons[$jadwal->nama_jadwal] ?? 'fa-calendar-alt' }}"></i>
                                        </div>
                                    </div>
                                    <h3 class="text-lg font-bold text-gray-800 mb-2">{{ $jadwal->nama_jadwal }}</h3>
                                    <p class="text-sm text-gray-600 mb-2">{{ $jadwal->tanggal_mulai }} -
                                        {{ $jadwal->tanggal_selesai }}</p>
                                    @if ($jadwal->keterangan)
                                        <p class="text-sm text-gray-500">{{ $jadwal->keterangan }}</p>
                                    @endif
                                </div>
                            </div>

                            <div
                                class="absolute left-1/2 transform -translate-x-1/2 w-6 h-6 bg-white border-4 border-primary-500 rounded-full shadow-lg">
                            </div>

                            <div class="w-5/12"></div>
                        </div>
                    @endforeach --}}
                </div>
            </div>

            <!-- Persyaratan & Info Cards -->
            <div class="grid md:grid-cols-2 gap-8 mb-16">
                <!-- Persyaratan Card -->
                <div
                    class="bg-white rounded-2xl shadow-xl p-8 hover:shadow-2xl transition-all duration-300 border border-gray-100">
                    <div class="flex items-center mb-6">
                        <div
                            class="w-16 h-16 bg-gradient-to-r from-accent-500 to-accent-700 rounded-full flex items-center justify-center text-white mr-4">
                            <i class="fas fa-clipboard-list text-2xl"></i>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-800">Persyaratan Pendaftaran</h3>
                    </div>

                    <div class="space-y-4">
                        <div
                            class="flex items-start p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors duration-200">
                            <div
                                class="w-8 h-8 bg-accent-500 rounded-full flex items-center justify-center text-white mr-4 mt-1 flex-shrink-0">
                                <i class="fas fa-check text-sm"></i>
                            </div>
                            <span class="text-gray-700">Fotokopi akte kelahiran dan kartu keluarga</span>
                        </div>

                        <div
                            class="flex items-start p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors duration-200">
                            <div
                                class="w-8 h-8 bg-accent-500 rounded-full flex items-center justify-center text-white mr-4 mt-1 flex-shrink-0">
                                <i class="fas fa-check text-sm"></i>
                            </div>
                            <span class="text-gray-700">Fotokopi rapor SD kelas 4-6</span>
                        </div>

                        <div
                            class="flex items-start p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors duration-200">
                            <div
                                class="w-8 h-8 bg-accent-500 rounded-full flex items-center justify-center text-white mr-4 mt-1 flex-shrink-0">
                                <i class="fas fa-check text-sm"></i>
                            </div>
                            <span class="text-gray-700">Pas foto 3x4 (3 lembar)</span>
                        </div>

                        <div
                            class="flex items-start p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors duration-200">
                            <div
                                class="w-8 h-8 bg-accent-500 rounded-full flex items-center justify-center text-white mr-4 mt-1 flex-shrink-0">
                                <i class="fas fa-check text-sm"></i>
                            </div>
                            <span class="text-gray-700">Surat Keterangan Lulus (SKL) asli</span>
                        </div>
                    </div>
                </div>

                <!-- Informasi Penting Card -->
                <div class="bg-gradient-to-br from-primary-600 to-primary-800 rounded-2xl shadow-xl p-8 text-white">
                    <div class="flex items-center mb-6">
                        <div
                            class="w-16 h-16 bg-white bg-opacity-20 backdrop-blur-sm rounded-full flex items-center justify-center mr-4">
                            <i class="fas fa-info-circle text-2xl"></i>
                        </div>
                        <h3 class="text-2xl font-bold">Informasi Penting</h3>
                    </div>

                    <div class="space-y-4">
                        <div class="flex items-start p-4 bg-white bg-opacity-10 backdrop-blur-sm rounded-lg">
                            <i class="fas fa-globe mt-1 mr-4 text-secondary-300"></i>
                            <span>Pendaftaran dilakukan secara online melalui website ini</span>
                        </div>

                        <div class="flex items-start p-4 bg-white bg-opacity-10 backdrop-blur-sm rounded-lg">
                            <i class="fas fa-book-open mt-1 mr-4 text-secondary-300"></i>
                            <span>Tes masuk meliputi: Matematika, IPA, dan Bahasa Indonesia</span>
                        </div>

                        <div class="flex items-start p-4 bg-white bg-opacity-10 backdrop-blur-sm rounded-lg">
                            <i class="fas fa-bell mt-1 mr-4 text-secondary-300"></i>
                            <span>Pengumuman hasil seleksi dapat dilihat di website ini</span>
                        </div>

                        <div class="flex items-start p-4 bg-white bg-opacity-10 backdrop-blur-sm rounded-lg">
                            <i class="fas fa-phone mt-1 mr-4 text-secondary-300"></i>
                            <span>Informasi lebih lanjut hubungi: (021) 1234567</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Form Pendaftaran Section -->
    <section id="form-pendaftaran" class="py-20 bg-white">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-800 mb-4">Formulir Pendaftaran Online</h2>
                <p class="text-lg text-gray-600 max-w-2xl mx-auto">Lengkapi semua data dengan benar dan pastikan dokumen
                    yang diupload sesuai persyaratan</p>
            </div>

            @if (session('success'))
                <div class="bg-accent-50 border-l-4 border-accent-500 p-6 rounded-lg shadow-lg mb-8">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-check-circle text-accent-400 text-xl"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-accent-700 font-medium">{{ session('success') }}</p>
                        </div>
                    </div>
                </div>
            @endif

            <div class="bg-white rounded-2xl shadow-2xl border border-gray-100 overflow-hidden">
                <form method="POST" action="" enctype="multipart/form-data"
                    class="p-8">
                    @csrf

                    <!-- Progress Steps -->
                    <div class="mb-12">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center text-primary-600">
                                <div
                                    class="w-10 h-10 bg-primary-600 rounded-full flex items-center justify-center text-white font-semibold mr-4">
                                    1</div>
                                <span class="font-medium hidden sm:block">Jalur Pendaftaran</span>
                            </div>
                            <div class="flex-1 h-2 bg-gray-200 rounded-full mx-4">
                                <div class="h-2 bg-primary-600 rounded-full" style="width: 20%"></div>
                            </div>
                            <div class="flex items-center text-gray-400">
                                <div
                                    class="w-10 h-10 bg-gray-200 rounded-full flex items-center justify-center font-semibold mr-4">
                                    2</div>
                                <span class="font-medium hidden sm:block">Data Pribadi</span>
                            </div>
                            <div class="flex-1 h-2 bg-gray-200 rounded-full mx-4"></div>
                            <div class="flex items-center text-gray-400">
                                <div
                                    class="w-10 h-10 bg-gray-200 rounded-full flex items-center justify-center font-semibold mr-4">
                                    3</div>
                                <span class="font-medium hidden sm:block">Data Orang Tua</span>
                            </div>
                            <div class="flex-1 h-2 bg-gray-200 rounded-full mx-4"></div>
                            <div class="flex items-center text-gray-400">
                                <div
                                    class="w-10 h-10 bg-gray-200 rounded-full flex items-center justify-center font-semibold">
                                    4</div>
                                <span class="font-medium hidden sm:block ml-4">Upload Berkas</span>
                            </div>
                        </div>
                    </div>

                    <!-- Step 1: Jalur Pendaftaran -->
                    <div class="bg-gradient-to-r from-primary-50 to-primary-100 rounded-xl p-8 mb-8 border border-primary-200">
                        <div class="flex items-center mb-6">
                            <div
                                class="w-12 h-12 bg-primary-600 rounded-full flex items-center justify-center text-white mr-4">
                                <i class="fas fa-route"></i>
                            </div>
                            <h3 class="text-xl font-bold text-gray-800">Pilih Jalur Pendaftaran</h3>
                        </div>

                        <div class="space-y-4">
                            <label for="jalur_pendaftaran_id" class="block text-sm font-semibold text-gray-700 mb-2">
                                Jalur Pendaftaran <span class="text-red-500">*</span>
                            </label>
                            <select id="jalur_pendaftaran_id" name="jalur_pendaftaran_id" required
                                class="w-full px-4 py-4 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500 bg-white shadow-sm transition-all duration-200 @error('jalur_pendaftaran_id') border-red-500 ring-2 ring-red-200 @enderror">
                                <option value="">🎯 Pilih Jalur Pendaftaran</option>

                                {{-- @foreach ($jalurPendaftarans as $jalur)
                                    @if ($jalur->aktif)
                                        <option value="{{ $jalur->id }}"
                                            {{ old('jalur_pendaftaran_id') == $jalur->id ? 'selected' : '' }}>
                                            {{ $jalur->nama_jalur }}
                                            @if ($jalur->kuotaPendaftaran->where('tahun_ajaran_id', $tahunAjaranAktif->id)->first())
                                                (Sisa Kuota:
                                                {{ $jalur->kuotaPendaftaran->where('tahun_ajaran_id', $tahunAjaranAktif->id)->first()->kuota - $jalur->kuotaPendaftaran->where('tahun_ajaran_id', $tahunAjaranAktif->id)->first()->terisi }})
                                            @endif
                                        </option>
                                    @endif
                                @endforeach --}}
                            </select>
                            @error('jalur_pendaftaran_id')
                                <p class="mt-2 text-sm text-red-600 flex items-center">
                                    <i class="fas fa-exclamation-circle mr-2"></i>{{ $message }}
                                </p>
                            @enderror
                        </div>
                    </div>

                    <!-- Step 2: Data Pribadi -->
                    <div class="bg-gradient-to-r from-accent-50 to-accent-100 rounded-xl p-8 mb-8 border border-accent-200">
                        <div class="flex items-center mb-6">
                            <div
                                class="w-12 h-12 bg-accent-600 rounded-full flex items-center justify-center text-white mr-4">
                                <i class="fas fa-user"></i>
                            </div>
                            <h3 class="text-xl font-bold text-gray-800">Data Pribadi Siswa</h3>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Nama Lengkap -->
                            <div class="space-y-2">
                                <label for="nama_lengkap" class="block text-sm font-semibold text-gray-700">
                                    Nama Lengkap <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <i class="fas fa-user absolute left-4 top-4 text-gray-400"></i>
                                    <input type="text" id="nama_lengkap" name="nama_lengkap" required
                                        class="w-full pl-12 pr-4 py-4 border border-gray-300 rounded-xl focus:ring-2 focus:ring-accent-500 focus:border-accent-500 bg-white shadow-sm transition-all duration-200 @error('nama_lengkap') border-red-500 ring-2 ring-red-200 @enderror"
                                        value="{{ old('nama_lengkap') }}" placeholder="Masukkan nama lengkap">
                                </div>
                                @error('nama_lengkap')
                                    <p class="text-sm text-red-600 flex items-center">
                                        <i class="fas fa-exclamation-circle mr-2"></i>{{ $message }}
                                    </p>
                                @enderror
                            </div>

                            <!-- Jenis Kelamin -->
                            <div class="space-y-2">
                                <label for="jenis_kelamin" class="block text-sm font-semibold text-gray-700">
                                    Jenis Kelamin <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <i class="fas fa-venus-mars absolute left-4 top-4 text-gray-400"></i>
                                    <select id="jenis_kelamin" name="jenis_kelamin" required
                                        class="w-full pl-12 pr-4 py-4 border border-gray-300 rounded-xl focus:ring-2 focus:ring-accent-500 focus:border-accent-500 bg-white shadow-sm transition-all duration-200 @error('jenis_kelamin') border-red-500 ring-2 ring-red-200 @enderror">
                                        <option value="">Pilih Jenis Kelamin</option>
                                        <option value="L" {{ old('jenis_kelamin') == 'L' ? 'selected' : '' }}>👨
                                            Laki-laki</option>
                                        <option value="P" {{ old('jenis_kelamin') == 'P' ? 'selected' : '' }}>👩
                                            Perempuan</option>
                                    </select>
                                </div>
                                @error('jenis_kelamin')
                                    <p class="text-sm text-red-600 flex items-center">
                                        <i class="fas fa-exclamation-circle mr-2"></i>{{ $message }}
                                    </p>
                                @enderror
                            </div>

                            <!-- NIK -->
                            <div class="space-y-2">
                                <label for="nik" class="block text-sm font-semibold text-gray-700">
                                    NIK <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <i class="fas fa-id-card absolute left-4 top-4 text-gray-400"></i>
                                    <input type="text" id="nik" name="nik" required maxlength="16"
                                        class="w-full pl-12 pr-4 py-4 border border-gray-300 rounded-xl focus:ring-2 focus:ring-accent-500 focus:border-accent-500 bg-white shadow-sm transition-all duration-200 @error('nik') border-red-500 ring-2 ring-red-200 @enderror"
                                        value="{{ old('nik') }}" placeholder="16 digit NIK">
                                </div>
                                @error('nik')
                                    <p class="text-sm text-red-600 flex items-center">
                                        <i class="fas fa-exclamation-circle mr-2"></i>{{ $message }}
                                    </p>
                                @enderror
                            </div>

                            <!-- NISN -->
                            <div class="space-y-2">
                                <label for="nisn" class="block text-sm font-semibold text-gray-700">
                                    NISN <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <i class="fas fa-graduation-cap absolute left-4 top-4 text-gray-400"></i>
                                    <input type="text" id="nisn" name="nisn" required maxlength="10"
                                        class="w-full pl-12 pr-4 py-4 border border-gray-300 rounded-xl focus:ring-2 focus:ring-accent-500 focus:border-accent-500 bg-white shadow-sm transition-all duration-200 @error('nisn') border-red-500 ring-2 ring-red-200 @enderror"
                                        value="{{ old('nisn') }}" placeholder="10 digit NISN">
                                </div>
                                @error('nisn')
                                    <p class="text-sm text-red-600 flex items-center">
                                        <i class="fas fa-exclamation-circle mr-2"></i>{{ $message }}
                                    </p>
                                @enderror
                            </div>

                            <!-- Tempat Lahir -->
                            <div class="space-y-2">
                                <label for="tempat_lahir" class="block text-sm font-semibold text-gray-700">
                                    Tempat Lahir <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <i class="fas fa-map-marker-alt absolute left-4 top-4 text-gray-400"></i>
                                    <input type="text" id="tempat_lahir" name="tempat_lahir" required
                                        class="w-full pl-12 pr-4 py-4 border border-gray-300 rounded-xl focus:ring-2 focus:ring-accent-500 focus:border-accent-500 bg-white shadow-sm transition-all duration-200 @error('tempat_lahir') border-red-500 ring-2 ring-red-200 @enderror"
                                        value="{{ old('tempat_lahir') }}" placeholder="Kota tempat lahir">
                                </div>
                                @error('tempat_lahir')
                                    <p class="text-sm text-red-600 flex items-center">
                                        <i class="fas fa-exclamation-circle mr-2"></i>{{ $message }}
                                    </p>
                                @enderror
                            </div>

                            <!-- Tanggal Lahir -->
                            <div class="space-y-2">
                                <label for="tanggal_lahir" class="block text-sm font-semibold text-gray-700">
                                    Tanggal Lahir <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <i class="fas fa-calendar-alt absolute left-4 top-4 text-gray-400"></i>
                                    <input type="date" id="tanggal_lahir" name="tanggal_lahir" required
                                        class="w-full pl-12 pr-4 py-4 border border-gray-300 rounded-xl focus:ring-2 focus:ring-accent-500 focus:border-accent-500 bg-white shadow-sm transition-all duration-200 @error('tanggal_lahir') border-red-500 ring-2 ring-red-200 @enderror"
                                        value="{{ old('tanggal_lahir') }}">
                                </div>
                                @error('tanggal_lahir')
                                    <p class="text-sm text-red-600 flex items-center">
                                        <i class="fas fa-exclamation-circle mr-2"></i>{{ $message }}
                                    </p>
                                @enderror
                            </div>

                            <!-- Agama -->
                            <div class="space-y-2">
                                <label for="agama" class="block text-sm font-semibold text-gray-700">
                                    Agama <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <i class="fas fa-praying-hands absolute left-4 top-4 text-gray-400"></i>
                                    <input type="text" id="agama" name="agama" required
                                        class="w-full pl-12 pr-4 py-4 border border-gray-300 rounded-xl focus:ring-2 focus:ring-accent-500 focus:border-accent-500 bg-white shadow-sm transition-all duration-200 @error('agama') border-red-500 ring-2 ring-red-200 @enderror"
                                        value="{{ old('agama') }}" placeholder="Agama yang dianut">
                                </div>
                                @error('agama')
                                    <p class="text-sm text-red-600 flex items-center">
                                        <i class="fas fa-exclamation-circle mr-2"></i>{{ $message }}
                                    </p>
                                @enderror
                            </div>

                            <!-- Asal Sekolah -->
                            <div class="space-y-2">
                                <label for="asal_sekolah" class="block text-sm font-semibold text-gray-700">
                                    Asal Sekolah <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <i class="fas fa-school absolute left-4 top-4 text-gray-400"></i>
                                    <input type="text" id="asal_sekolah" name="asal_sekolah" required
                                        class="w-full pl-12 pr-4 py-4 border border-gray-300 rounded-xl focus:ring-2 focus:ring-accent-500 focus:border-accent-500 bg-white shadow-sm transition-all duration-200 @error('asal_sekolah') border-red-500 ring-2 ring-red-200 @enderror"
                                        value="{{ old('asal_sekolah') }}" placeholder="Nama sekolah asal">
                                </div>
                                @error('asal_sekolah')
                                    <p class="text-sm text-red-600 flex items-center">
                                        <i class="fas fa-exclamation-circle mr-2"></i>{{ $message }}
                                    </p>
                                @enderror
                            </div>

                            <!-- Alamat -->
                            <div class="space-y-2 md:col-span-2">
                                <label for="alamat" class="block text-sm font-semibold text-gray-700">
                                    Alamat <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <i class="fas fa-home absolute left-4 top-4 text-gray-400"></i>
                                    <textarea id="alamat" name="alamat" required rows="3"
                                        class="w-full pl-12 pr-4 py-4 border border-gray-300 rounded-xl focus:ring-2 focus:ring-accent-500 focus:border-accent-500 bg-white shadow-sm transition-all duration-200 @error('alamat') border-red-500 ring-2 ring-red-200 @enderror"
                                        placeholder="Alamat lengkap tempat tinggal">{{ old('alamat') }}</textarea>
                                </div>
                                @error('alamat')
                                    <p class="text-sm text-red-600 flex items-center">
                                        <i class="fas fa-exclamation-circle mr-2"></i>{{ $message }}
                                    </p>
                                @enderror
                            </div>

                            <!-- No HP Siswa -->
                            <div class="space-y-2">
                                <label for="no_hp" class="block text-sm font-semibold text-gray-700">
                                    No. HP Siswa <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <i class="fas fa-mobile-alt absolute left-4 top-4 text-gray-400"></i>
                                    <input type="tel" id="no_hp" name="no_hp" required
                                        class="w-full pl-12 pr-4 py-4 border border-gray-300 rounded-xl focus:ring-2 focus:ring-accent-500 focus:border-accent-500 bg-white shadow-sm transition-all duration-200 @error('no_hp') border-red-500 ring-2 ring-red-200 @enderror"
                                        value="{{ old('no_hp') }}" placeholder="08xxxxxxxxxx">
                                </div>
                                @error('no_hp')
                                    <p class="text-sm text-red-600 flex items-center">
                                        <i class="fas fa-exclamation-circle mr-2"></i>{{ $message }}
                                    </p>
                                @enderror
                            </div>

                            <!-- Email -->
                            <div class="space-y-2">
                                <label for="email" class="block text-sm font-semibold text-gray-700">
                                    Email <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <i class="fas fa-envelope absolute left-4 top-4 text-gray-400"></i>
                                    <input type="email" id="email" name="email" required
                                        class="w-full pl-12 pr-4 py-4 border border-gray-300 rounded-xl focus:ring-2 focus:ring-accent-500 focus:border-accent-500 bg-white shadow-sm transition-all duration-200 @error('email') border-red-500 ring-2 ring-red-200 @enderror"
                                        value="{{ old('email') }}" placeholder="email@contoh.com">
                                </div>
                                @error('email')
                                    <p class="text-sm text-red-600 flex items-center">
                                        <i class="fas fa-exclamation-circle mr-2"></i>{{ $message }}
                                    </p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Step 3: Data Orang Tua -->
                    <div class="bg-gradient-to-r from-secondary-50 to-secondary-100 rounded-xl p-8 mb-8 border border-secondary-200">
                        <div class="flex items-center mb-6">
                            <div
                                class="w-12 h-12 bg-secondary-600 rounded-full flex items-center justify-center text-white mr-4">
                                <i class="fas fa-users"></i>
                            </div>
                            <h3 class="text-xl font-bold text-gray-800">Data Orang Tua</h3>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Nama Ayah -->
                            <div class="space-y-2">
                                <label for="nama_ayah" class="block text-sm font-semibold text-gray-700">
                                    Nama Ayah <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <i class="fas fa-male absolute left-4 top-4 text-gray-400"></i>
                                    <input type="text" id="nama_ayah" name="nama_ayah" required
                                        class="w-full pl-12 pr-4 py-4 border border-gray-300 rounded-xl focus:ring-2 focus:ring-secondary-500 focus:border-secondary-500 bg-white shadow-sm transition-all duration-200 @error('nama_ayah') border-red-500 ring-2 ring-red-200 @enderror"
                                        value="{{ old('nama_ayah') }}" placeholder="Nama lengkap ayah">
                                </div>
                                @error('nama_ayah')
                                    <p class="text-sm text-red-600 flex items-center">
                                        <i class="fas fa-exclamation-circle mr-2"></i>{{ $message }}
                                    </p>
                                @enderror
                            </div>

                            <!-- Pekerjaan Ayah -->
                            <div class="space-y-2">
                                <label for="pekerjaan_ayah" class="block text-sm font-semibold text-gray-700">
                                    Pekerjaan Ayah <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <i class="fas fa-briefcase absolute left-4 top-4 text-gray-400"></i>
                                    <input type="text" id="pekerjaan_ayah" name="pekerjaan_ayah" required
                                        class="w-full pl-12 pr-4 py-4 border border-gray-300 rounded-xl focus:ring-2 focus:ring-secondary-500 focus:border-secondary-500 bg-white shadow-sm transition-all duration-200 @error('pekerjaan_ayah') border-red-500 ring-2 ring-red-200 @enderror"
                                        value="{{ old('pekerjaan_ayah') }}" placeholder="Pekerjaan ayah">
                                </div>
                                @error('pekerjaan_ayah')
                                    <p class="text-sm text-red-600 flex items-center">
                                        <i class="fas fa-exclamation-circle mr-2"></i>{{ $message }}
                                    </p>
                                @enderror
                            </div>

                            <!-- Nama Ibu -->
                            <div class="space-y-2">
                                <label for="nama_ibu" class="block text-sm font-semibold text-gray-700">
                                    Nama Ibu <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <i class="fas fa-female absolute left-4 top-4 text-gray-400"></i>
                                    <input type="text" id="nama_ibu" name="nama_ibu" required
                                        class="w-full pl-12 pr-4 py-4 border border-gray-300 rounded-xl focus:ring-2 focus:ring-secondary-500 focus:border-secondary-500 bg-white shadow-sm transition-all duration-200 @error('nama_ibu') border-red-500 ring-2 ring-red-200 @enderror"
                                        value="{{ old('nama_ibu') }}" placeholder="Nama lengkap ibu">
                                </div>
                                @error('nama_ibu')
                                    <p class="text-sm text-red-600 flex items-center">
                                        <i class="fas fa-exclamation-circle mr-2"></i>{{ $message }}
                                    </p>
                                @enderror
                            </div>

                            <!-- Pekerjaan Ibu -->
                            <div class="space-y-2">
                                <label for="pekerjaan_ibu" class="block text-sm font-semibold text-gray-700">
                                    Pekerjaan Ibu <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <i class="fas fa-briefcase absolute left-4 top-4 text-gray-400"></i>
                                    <input type="text" id="pekerjaan_ibu" name="pekerjaan_ibu" required
                                        class="w-full pl-12 pr-4 py-4 border border-gray-300 rounded-xl focus:ring-2 focus:ring-secondary-500 focus:border-secondary-500 bg-white shadow-sm transition-all duration-200 @error('pekerjaan_ibu') border-red-500 ring-2 ring-red-200 @enderror"
                                        value="{{ old('pekerjaan_ibu') }}" placeholder="Pekerjaan ibu">
                                </div>
                                @error('pekerjaan_ibu')
                                    <p class="text-sm text-red-600 flex items-center">
                                        <i class="fas fa-exclamation-circle mr-2"></i>{{ $message }}
                                    </p>
                                @enderror
                            </div>

                            <!-- No HP Orang Tua -->
                            <div class="space-y-2 md:col-span-2">
                                <label for="no_hp_orang_tua" class="block text-sm font-semibold text-gray-700">
                                    No. HP Orang Tua <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <i class="fas fa-phone absolute left-4 top-4 text-gray-400"></i>
                                    <input type="tel" id="no_hp_orang_tua" name="no_hp_orang_tua" required
                                        class="w-full pl-12 pr-4 py-4 border border-gray-300 rounded-xl focus:ring-2 focus:ring-secondary-500 focus:border-secondary-500 bg-white shadow-sm transition-all duration-200 @error('no_hp_orang_tua') border-red-500 ring-2 ring-red-200 @enderror"
                                        value="{{ old('no_hp_orang_tua') }}" placeholder="08xxxxxxxxxx">
                                </div>
                                @error('no_hp_orang_tua')
                                    <p class="text-sm text-red-600 flex items-center">
                                        <i class="fas fa-exclamation-circle mr-2"></i>{{ $message }}
                                    </p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Step 4: Upload Berkas -->
                    <div class="bg-gradient-to-r from-primary-100 to-primary-50 rounded-xl p-8 mb-8 border border-primary-200">
                        <div class="flex items-center mb-6">
                            <div
                                class="w-12 h-12 bg-primary-700 rounded-full flex items-center justify-center text-white mr-4">
                                <i class="fas fa-cloud-upload-alt"></i>
                            </div>
                            <h3 class="text-xl font-bold text-gray-800">Upload Berkas Persyaratan</h3>
                        </div>

                        <div class="space-y-6">
                            <!-- Upload Ijazah -->
                            <div class="space-y-2">
                                <label for="ijazah_path" class="block text-sm font-semibold text-gray-700">
                                    Upload Ijazah (PDF) <span class="text-red-500">*</span>
                                </label>
                                <div
                                    class="relative border-2 border-dashed border-gray-300 rounded-xl p-6 hover:border-secondary-500 transition-colors duration-200 bg-white">
                                    <div class="text-center">
                                        <i class="fas fa-file-pdf text-4xl text-gray-400 mb-4"></i>
                                        <div class="text-sm text-gray-600 mb-2">
                                            <strong>Klik untuk upload</strong> atau drag & drop file
                                        </div>
                                        <div class="text-xs text-gray-500">PDF maksimal 5MB</div>
                                    </div>
                                    <input type="file" id="ijazah_path" name="ijazah_path" accept=".pdf" required
                                        class="absolute inset-0 w-full h-full opacity-0 cursor-pointer @error('ijazah_path') border-red-500 @enderror">
                                </div>
                                @error('ijazah_path')
                                    <p class="text-sm text-red-600 flex items-center">
                                        <i class="fas fa-exclamation-circle mr-2"></i>{{ $message }}
                                    </p>
                                @enderror
                            </div>

                            <!-- Upload KK -->
                            <div class="space-y-2">
                                <label for="kk_path" class="block text-sm font-semibold text-gray-700">
                                    Upload Kartu Keluarga (PDF) <span class="text-red-500">*</span>
                                </label>
                                <div
                                    class="relative border-2 border-dashed border-gray-300 rounded-xl p-6 hover:border-secondary-500 transition-colors duration-200 bg-white">
                                    <div class="text-center">
                                        <i class="fas fa-file-pdf text-4xl text-gray-400 mb-4"></i>
                                        <div class="text-sm text-gray-600 mb-2">
                                            <strong>Klik untuk upload</strong> atau drag & drop file
                                        </div>
                                        <div class="text-xs text-gray-500">PDF maksimal 5MB</div>
                                    </div>
                                    <input type="file" id="kk_path" name="kk_path" accept=".pdf" required
                                        class="absolute inset-0 w-full h-full opacity-0 cursor-pointer @error('kk_path') border-red-500 @enderror">
                                </div>
                                @error('kk_path')
                                    <p class="text-sm text-red-600 flex items-center">
                                        <i class="fas fa-exclamation-circle mr-2"></i>{{ $message }}
                                    </p>
                                @enderror
                            </div>

                            <!-- Upload Akta -->
                            <div class="space-y-2">
                                <label for="akta_path" class="block text-sm font-semibold text-gray-700">
                                    Upload Akta Kelahiran (PDF) <span class="text-red-500">*</span>
                                </label>
                                <div
                                    class="relative border-2 border-dashed border-gray-300 rounded-xl p-6 hover:border-secondary-500 transition-colors duration-200 bg-white">
                                    <div class="text-center">
                                        <i class="fas fa-file-pdf text-4xl text-gray-400 mb-4"></i>
                                        <div class="text-sm text-gray-600 mb-2">
                                            <strong>Klik untuk upload</strong> atau drag & drop file
                                        </div>
                                        <div class="text-xs text-gray-500">PDF maksimal 5MB</div>
                                    </div>
                                    <input type="file" id="akta_path" name="akta_path" accept=".pdf" required
                                        class="absolute inset-0 w-full h-full opacity-0 cursor-pointer @error('akta_path') border-red-500 @enderror">
                                </div>
                                @error('akta_path')
                                    <p class="text-sm text-red-600 flex items-center">
                                        <i class="fas fa-exclamation-circle mr-2"></i>{{ $message }}
                                    </p>
                                @enderror
                            </div>

                            <!-- Upload Foto -->
                            <div class="space-y-2">
                                <label for="foto_path" class="block text-sm font-semibold text-gray-700">
                                    Upload Pas Foto (JPG/PNG) <span class="text-red-500">*</span>
                                </label>
                                <div
                                    class="relative border-2 border-dashed border-gray-300 rounded-xl p-6 hover:border-secondary-500 transition-colors duration-200 bg-white">
                                    <div class="text-center">
                                        <i class="fas fa-image text-4xl text-gray-400 mb-4"></i>
                                        <div class="text-sm text-gray-600 mb-2">
                                            <strong>Klik untuk upload</strong> atau drag & drop file
                                        </div>
                                        <div class="text-xs text-gray-500">JPG/PNG maksimal 2MB</div>
                                    </div>
                                    <input type="file" id="foto_path" name="foto_path" accept="image/*" required
                                        class="absolute inset-0 w-full h-full opacity-0 cursor-pointer @error('foto_path') border-red-500 @enderror">
                                </div>
                                @error('foto_path')
                                    <p class="text-sm text-red-600 flex items-center">
                                        <i class="fas fa-exclamation-circle mr-2"></i>{{ $message }}
                                    </p>
                                @enderror
                            </div>

                            <!-- Upload SKL -->
                            <div class="space-y-2">
                                <label for="skl_path" class="block text-sm font-semibold text-gray-700">
                                    Upload Surat Keterangan Lulus (PDF) <span class="text-red-500">*</span>
                                </label>
                                <div
                                    class="relative border-2 border-dashed border-gray-300 rounded-xl p-6 hover:border-secondary-500 transition-colors duration-200 bg-white">
                                    <div class="text-center">
                                        <i class="fas fa-file-pdf text-4xl text-gray-400 mb-4"></i>
                                        <div class="text-sm text-gray-600 mb-2">
                                            <strong>Klik untuk upload</strong> atau drag & drop file
                                        </div>
                                        <div class="text-xs text-gray-500">PDF maksimal 5MB</div>
                                    </div>
                                    <input type="file" id="skl_path" name="skl_path" accept=".pdf" required
                                        class="absolute inset-0 w-full h-full opacity-0 cursor-pointer @error('skl_path') border-red-500 @enderror">
                                </div>
                                @error('skl_path')
                                    <p class="text-sm text-red-600 flex items-center">
                                        <i class="fas fa-exclamation-circle mr-2"></i>{{ $message }}
                                    </p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="text-center pt-8">
                        <button type="submit"
                            class="inline-flex items-center px-12 py-4 bg-gradient-to-r from-primary-600 to-primary-800 text-white text-lg font-semibold rounded-full shadow-xl hover:shadow-2xl transform hover:scale-105 transition-all duration-300 focus:ring-4 focus:ring-primary-300">
                            <i class="fas fa-paper-plane mr-3"></i>
                            Kirim Pendaftaran
                        </button>

                        <p class="text-sm text-gray-500 mt-4">
                            Dengan mengirim formulir ini, Anda menyetujui syarat dan ketentuan yang berlaku
                        </p>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <!-- Footer CTA -->
    <section class="bg-gradient-to-r from-primary-600 to-primary-800 py-16">
        <div class="max-w-4xl mx-auto text-center px-4 sm:px-6 lg:px-8">
            <h2 class="text-3xl font-bold text-white mb-4">Butuh Bantuan?</h2>
            <p class="text-xl text-primary-100 mb-8">Tim kami siap membantu Anda dalam proses pendaftaran</p>

            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="tel:{{ $profileSekolah->telp ?? '' }}"
                    class="inline-flex items-center px-6 py-3 bg-white text-primary-600 rounded-lg font-semibold hover:bg-gray-100 transition-colors duration-200">
                    <i class="fas fa-phone mr-2"></i>
                    {{ $profileSekolah->telp ?? '' }}
                </a>
                <a href="mailto:{{ $profileSekolah->email ?? '' }}"
                    class="inline-flex items-center px-6 py-3 bg-primary-500 text-white rounded-lg font-semibold hover:bg-primary-400 transition-colors duration-200">
                    <i class="fas fa-envelope mr-2"></i>
                    {{ $profileSekolah->email ?? '' }}
                </a>
            </div>
        </div>
    </section>

    <style>
        /* Note: fadeInUp/float animations and the scrollbar theme already
           live globally in app.css and use the primary theme color, so
           they are intentionally not redefined here. */

        /* File upload hover effects (secondary/warm accent from app.css theme) */
        .border-dashed:hover {
            border-color: var(--color-secondary-500);
            background-color: var(--color-secondary-50);
        }

        /* Smooth focus transitions */
        input:focus,
        select:focus,
        textarea:focus {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        /* Button hover effects */
        button:hover {
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }
    </style>

    <script>
        // File upload preview and validation
        document.querySelectorAll('input[type="file"]').forEach(input => {
            input.addEventListener('change', function() {
                const file = this.files[0];
                const parent = this.closest('.border-dashed');

                if (file) {
                    parent.classList.add('border-accent-500', 'bg-accent-50');
                    parent.classList.remove('border-gray-300');

                    const icon = parent.querySelector('i');
                    icon.className = 'fas fa-check-circle text-4xl text-accent-500 mb-4';

                    const text = parent.querySelector('.text-sm');
                    text.innerHTML = `<strong class="text-accent-600">File terpilih:</strong> ${file.name}`;
                } else {
                    parent.classList.remove('border-accent-500', 'bg-accent-50');
                    parent.classList.add('border-gray-300');
                }
            });
        });

        document.querySelector('a[href="#form-pendaftaran"]')?.addEventListener('click', function(e) {
            e.preventDefault();
            document.getElementById('form-pendaftaran').scrollIntoView({
                behavior: 'smooth'
            });
        });

        document.querySelectorAll('input, select, textarea').forEach(field => {
            field.addEventListener('blur', function() {
                if (this.value && this.checkValidity()) {
                    this.classList.add('border-accent-500');
                    this.classList.remove('border-red-500');
                } else if (this.value && !this.checkValidity()) {
                    this.classList.add('border-red-500');
                    this.classList.remove('border-accent-500');
                }
            });
        });

        // Progress bar animation
        const steps = document.querySelectorAll('.w-10.h-10');
        const progressBars = document.querySelectorAll('.h-2.bg-primary-600');

        function updateProgress() {
            let completedSteps = 0;

            // Check each section
            const jalurSelect = document.getElementById('jalur_pendaftaran_id');
            if (jalurSelect && jalurSelect.value) completedSteps = 1;

            const requiredPersonalFields = ['nama_lengkap', 'jenis_kelamin', 'nik', 'nisn'];
            if (requiredPersonalFields.every(id => document.getElementById(id)?.value)) completedSteps = 2;

            const requiredParentFields = ['nama_ayah', 'nama_ibu', 'no_hp_orang_tua'];
            if (completedSteps === 2 && requiredParentFields.every(id => document.getElementById(id)?.value))
                completedSteps = 3;

            const fileFields = ['ijazah_path', 'kk_path', 'akta_path', 'foto_path', 'skl_path'];
            if (completedSteps === 3 && fileFields.every(id => document.getElementById(id)?.files.length > 0))
                completedSteps = 4;

            // Update progress visualization
            steps.forEach((step, index) => {
                if (index < completedSteps) {
                    step.classList.add('bg-primary-600', 'text-white');
                    step.classList.remove('bg-gray-200', 'text-gray-400');
                    step.parentElement.classList.add('text-primary-600');
                    step.parentElement.classList.remove('text-gray-400');
                }
            });

            progressBars.forEach((bar, index) => {
                if (index < completedSteps - 1) {
                    bar.style.width = '100%';
                } else if (index === completedSteps - 1) {
                    bar.style.width = '50%';
                }
            });
        }

        // Monitor form changes
        document.querySelectorAll('input, select').forEach(field => {
            field.addEventListener('change', updateProgress);
        });

        // Initial progress check
        updateProgress();
    </script>
@endsection
