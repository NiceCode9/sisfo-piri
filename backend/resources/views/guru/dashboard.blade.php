@extends('layouts.app')

@section('title', 'Dashboard Guru')
@section('breadcrumb', 'Dashboard Guru')

@section('content')
    <div class="page-header d-flex flex-wrap align-items-start justify-content-between gap-3">
        <div>
            <h1 class="page-title">Dashboard Guru</h1>
            <p class="page-subtitle mb-0">
                {{ $hariIni->translatedFormat('l, d F Y') }}
                @if ($guru)
                    &middot; {{ $guru->nama }}
                @endif
            </p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('admin.absensis.index') }}" class="btn btn-primary btn-sm">
                <i class="fa-solid fa-clipboard-check"></i> Isi Absensi
            </a>
            <a href="{{ route('admin.tugas.index') }}" class="btn btn-nexus-outline btn-sm">
                <i class="fa-solid fa-clipboard-question"></i> Kelola Tugas
            </a>
        </div>
    </div>

    @include('layouts.partials.alert')

    @include('guru._nav', ['tabAktif' => 'dashboard'])

    @if ($ringkasan['kelas'] === 0)
        {{-- Nol rombel adalah hasil yang sah: guru baru terdaftar, atau
             penugasannya belum diisi. Menampilkan angka nol tanpa penjelasan
             selalu membingungkan; kartu ini yang menjelaskan kenapa. --}}
        <div class="card-nexus">
            <div class="card-body-nexus text-center py-5">
                <i class="fa-solid fa-user-slash fa-2x text-muted mb-3"></i>
                <h5 class="mb-2">Belum ada kelas yang diampu</h5>
                <p class="text-muted mb-0">
                    Dashboard ini hanya menampilkan kelas yang Anda wali atau mengampu mapelnya.
                    Hubungi administrator bila ini keliru.
                </p>
            </div>
        </div>
    @else
        {{-- 1. Ringkasan cakupan --}}
        <div class="row g-3 mb-3">
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="stat-card h-100">
                    <div class="stat-icon-wrap" style="background:rgba(79,70,229,.12);color:#4f46e5"><i class="fa-solid fa-school"></i></div>
                    <div class="stat-value">{{ $ringkasan['kelas'] }}</div>
                    <div class="stat-label">Kelas Diampu</div>
                    <div class="stat-trend trend-neutral">{{ $ringkasan['wali'] }} sebagai wali kelas</div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="stat-card h-100">
                    <div class="stat-icon-wrap" style="background:rgba(124,58,237,.12);color:#7c3aed"><i class="fa-solid fa-user-graduate"></i></div>
                    <div class="stat-value">{{ $ringkasan['siswa'] }}</div>
                    <div class="stat-label">Siswa Diajar</div>
                    <div class="stat-trend trend-neutral">Anggota aktif kelas berjalan</div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="stat-card h-100">
                    <div class="stat-icon-wrap" style="background:rgba(245,158,11,.12);color:#f59e0b"><i class="fa-solid fa-clipboard-question"></i></div>
                    <div class="stat-value">{{ $ringkasan['tugas'] }}</div>
                    <div class="stat-label">Tugas Aktif</div>
                    <div class="stat-trend trend-neutral">{{ count($deadlineTerdekat) }} punya deadline terdekat</div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="stat-card h-100">
                    <div class="stat-icon-wrap" style="background:rgba(59,130,246,.12);color:#3b82f6"><i class="fa-solid fa-book-open-reader"></i></div>
                    <div class="stat-value">{{ $ringkasan['materi'] }}</div>
                    <div class="stat-label">Materi Aktif</div>
                    <div class="stat-trend trend-neutral">Tersedia untuk siswa</div>
                </div>
            </div>
        </div>

        {{-- 2. Absensi hari ini per kelas --}}
        <div class="card-nexus mb-3">
            <div class="card-header-nexus">
                <div>
                    <h5 class="card-title">Absensi Hari Ini</h5>
                    <p class="card-subtitle">Kelas yang belum lengkap absensinya diisi lebih dulu</p>
                </div>
                <a href="{{ route('admin.absensis.index') }}" class="btn btn-nexus-outline btn-sm">Semua Absensi</a>
            </div>
            <div class="card-body-nexus">
                <div class="row g-3">
                    @foreach ($absensiHariIni as $baris)
                        <div class="col-12 col-md-6 col-xl-4">
                            <div class="d-flex justify-content-between align-items-center p-3 rounded" style="border:1px solid var(--border-color)">
                                <div>
                                    <div class="fw-semibold">{{ $baris['rombel']->kelas?->nama_kelas ?? '-' }}</div>
                                    <div class="small text-muted">
                                        {{ $baris['total'] }} siswa
                                        @if ($baris['persen'] === null)
                                            &middot; belum ada data
                                        @else
                                            &middot; {{ $baris['tercatat'] }} tercatat
                                        @endif
                                    </div>
                                </div>
                                <div class="text-end">
                                    @if ($baris['persen'] === null)
                                        <span class="badge-nexus badge-neutral">Belum diisi</span>
                                    @elseif ($baris['sudah'])
                                        <span class="badge-nexus badge-success">{{ $baris['persen'] }}% lengkap</span>
                                    @else
                                        <span class="badge-nexus badge-warning">{{ $baris['persen'] }}%</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="row g-3">
            {{-- 3. Tugas menunggu penilaian --}}
            <div class="col-12 col-xl-7">
                <div class="card-nexus h-100">
                    <div class="card-header-nexus">
                        <div>
                            <h5 class="card-title">Menunggu Penilaian</h5>
                            <p class="card-subtitle">Pengumpulan siswa yang belum dinilai</p>
                        </div>
                        <a href="{{ route('admin.tugas.index') }}" class="btn btn-nexus-outline btn-sm">Nilai Tugas</a>
                    </div>
                    <div class="card-body-nexus">
                        @forelse ($tugasMenungguPenilaian as $kumpulan)
                            <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                                <div>
                                    <div class="fw-semibold">{{ $kumpulan->tugas?->judul ?? 'Tugas dihapus' }}</div>
                                    <div class="small text-muted">
                                        {{ $kumpulan->tugas?->rombel?->kelas?->nama_kelas ?? '-' }}
                                        @if ($kumpulan->tugas?->mataPelajaran)
                                            &middot; {{ $kumpulan->tugas->mataPelajaran->nama }}
                                        @endif
                                        &middot; {{ $kumpulan->siswa?->user?->name ?? 'Siswa dihapus' }}
                                        @if ($kumpulan->is_terlambat)
                                            <span class="badge-nexus badge-warning ms-1">Terlambat</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-4">
                                <i class="fa-solid fa-circle-check fa-2x text-success mb-2"></i>
                                <p class="mb-0 text-muted">Tidak ada pengumpulan yang menunggu dinilai.</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-5">
                {{-- 4. Ujian CBT --}}
                <div class="card-nexus mb-3">
                    <div class="card-header-nexus">
                        <div>
                            <h5 class="card-title">Ujian CBT Saya</h5>
                            <p class="card-subtitle">Ujian yang dipublikasikan untuk kelas Anda</p>
                        </div>
                        <a href="{{ route('admin.cbt.exams.index') }}" class="btn btn-nexus-outline btn-sm">Kelola</a>
                    </div>
                    <div class="card-body-nexus">
                        @forelse ($ujianSaya as $ujian)
                            <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                                <div>
                                    <div class="fw-semibold">{{ $ujian->name }}</div>
                                    <div class="small text-muted">
                                        {{ $ujian->rombel?->kelas?->nama_kelas ?? '-' }}
                                        @if ($ujian->mataPelajaran)
                                            &middot; {{ $ujian->mataPelajaran->nama }}
                                        @endif
                                        &middot; {{ $ujian->tokens_count }} token
                                    </div>
                                </div>
                                <span class="badge-nexus badge-info">{{ $ujian->duration_minutes }} menit</span>
                            </div>
                        @empty
                            <div class="text-center py-4">
                                <i class="fa-solid fa-file-circle-check fa-2x text-muted mb-2"></i>
                                <p class="mb-0 text-muted">Belum ada ujian untuk kelas Anda.</p>
                            </div>
                        @endforelse
                    </div>
                </div>

                <div class="card-nexus">
                    <div class="card-header-nexus">
                        <div>
                            <h5 class="card-title">Deadline Terdekat</h5>
                            <p class="card-subtitle">Tugas yang belum lewat tenggat</p>
                        </div>
                    </div>
                    <div class="card-body-nexus">
                        @forelse ($deadlineTerdekat as $tugas)
                            <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                                <div>
                                    <div class="fw-semibold">{{ $tugas->judul }}</div>
                                    <div class="small text-muted">
                                        {{ $tugas->rombel?->kelas?->nama_kelas ?? '-' }}
                                        &middot; {{ $tugas->deadline->translatedFormat('j M H:i') }}
                                    </div>
                                </div>
                                @if ($tugas->deadline->isBefore(now()->addDay()))
                                    <span class="badge-nexus badge-danger">Segera</span>
                                @else
                                    <span class="badge-nexus badge-neutral">{{ $tugas->deadline->diffForHumans() }}</span>
                                @endif
                            </div>
                        @empty
                            <div class="text-center py-4">
                                <i class="fa-solid fa-calendar-check fa-2x text-muted mb-2"></i>
                                <p class="mb-0 text-muted">Tidak ada deadline yang mendekati.</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection
