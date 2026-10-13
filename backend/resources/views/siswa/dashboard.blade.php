@extends('layouts.app')

@section('title', 'Dashboard')
@section('breadcrumb', 'Dashboard')

@section('content')
<div class="page-header d-flex flex-wrap align-items-start justify-content-between gap-3">
    <div>
        <h1 class="page-title">Dashboard</h1>
        <p class="page-subtitle mb-0">
            Halo, {{ auth()->user()->name }}
            @if ($siswa && $siswa->nis)
                &middot; NIS {{ $siswa->nis }}
            @endif
        </p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('siswa.absensi') }}" class="btn btn-primary btn-sm">
            <i class="fa-solid fa-clipboard-check"></i> Absensi Saya
        </a>
        <a href="{{ route('siswa.tugas.index') }}" class="btn btn-nexus-outline btn-sm">
            <i class="fa-solid fa-clipboard-question"></i> Tugas
        </a>
    </div>
</div>

@include('layouts.partials.alert')

@include('siswa._nav', ['tabAktif' => 'dashboard'])

@if (! $siswa)
    {{-- Akun belum punya baris `siswas`: masih pendaftar, atau belum
         ditempatkan. Menampilkan angka nol tanpa penjelasan akan membingungkan. --}}
    <div class="card-nexus">
        <div class="card-body-nexus text-center py-5">
            <i class="fa-solid fa-user-graduate fa-2x text-muted mb-3"></i>
            <h5 class="mb-2">Belum ada data akademik</h5>
            <p class="text-muted mb-3">
                Ringkasan kehadiran dan tugas muncul setelah Anda ditempatkan di kelas.
            </p>
            <a href="{{ route('siswa.pendaftaran') }}" class="btn btn-primary btn-sm">
                <i class="fa-solid fa-file-lines"></i> Lihat Status Pendaftaran
            </a>
        </div>
    </div>
@else
    {{-- Ringkasan. Angkanya dari RiwayatSiswa::ringkas(), jadi sama persis
         dengan yang tampil di halaman Riwayat dan nol query tambahan. --}}
    <div class="row g-3 mb-3">
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="stat-card h-100">
                <div class="stat-icon-wrap" style="background:rgba(34,197,94,.12);color:#22c55e">
                    <i class="fa-solid fa-clipboard-check"></i>
                </div>
                <div class="stat-value">{{ $ringkas['persenAbsen'] === null ? '-' : $ringkas['persenAbsen'].'%' }}</div>
                <div class="stat-label">Kehadiran</div>
                <div class="stat-trend trend-neutral">{{ number_format($ringkas['hariAbsen'], 0, ',', '.') }} hari tercatat</div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="stat-card h-100">
                <div class="stat-icon-wrap" style="background:rgba(245,158,11,.12);color:#f59e0b">
                    <i class="fa-solid fa-clipboard-question"></i>
                </div>
                <div class="stat-value">{{ count($tugasMenunggu) }}</div>
                <div class="stat-label">Tugas Belum Dikerjakan</div>
                <div class="stat-trend trend-neutral">{{ $ringkas['tugas'] }} tugas diberikan</div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="stat-card h-100">
                <div class="stat-icon-wrap" style="background:rgba(79,70,229,.12);color:#4f46e5">
                    <i class="fa-solid fa-school"></i>
                </div>
                <div class="stat-value">{{ $ringkas['rombel'] }}</div>
                <div class="stat-label">Kelas Diikuti</div>
                <div class="stat-trend trend-neutral">{{ $ringkas['tahun'] }} tahun ajaran</div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="stat-card h-100">
                <div class="stat-icon-wrap" style="background:rgba(124,58,237,.12);color:#7c3aed">
                    <i class="fa-solid fa-file-question"></i>
                </div>
                <div class="stat-value">{{ $nilaiCbt === null ? '-' : $nilaiCbt['persen'] }}</div>
                <div class="stat-label">Nilai CBT Terakhir</div>
                <div class="stat-trend trend-neutral">
                    @if ($nilaiCbt)
                        {{ \Illuminate\Support\Str::limit($nilaiCbt['ujian'], 24) }}
                    @else
                        Belum ada nilai
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if (count($ujianTersedia) > 0)
        <div class="card-nexus mb-3">
            <div class="card-header-nexus">
                <div>
                    <h5 class="card-title">Ujian CBT Tersedia</h5>
                    <p class="card-subtitle">Ujian yang belum Anda kerjakan</p>
                </div>
            </div>
            <div class="card-body-nexus">
                @foreach ($ujianTersedia as $ujian)
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <div>
                            <div class="fw-semibold">{{ $ujian->name }}</div>
                            <div class="small text-muted">
                                {{ $ujian->rombel?->kelas?->nama_kelas ?? '-' }}
                                @if ($ujian->mataPelajaran)
                                    &middot; {{ $ujian->mataPelajaran->nama }}
                                @endif
                                @if ($ujian->available_until)
                                    &middot; s/d {{ $ujian->available_until->translatedFormat('j M H:i') }}
                                @endif
                            </div>
                        </div>
                        <span class="badge-nexus badge-info">{{ $ujian->duration_minutes }} menit</span>
                    </div>
                @endforeach
                <div class="small text-muted mt-3">
                    Ujian dikerjakan di aplikasi CBT dengan token dari guru, bukan di halaman ini.
                </div>
            </div>
        </div>
    @endif

    <div class="row g-3">
        <div class="col-12 col-xl-7">
            <div class="card-nexus h-100">
                <div class="card-header-nexus">
                    <div>
                        <h5 class="card-title">Tugas Belum Dikerjakan</h5>
                        <p class="card-subtitle">Tugas aktif yang belum dikumpulkan</p>
                    </div>
                    <a href="{{ route('siswa.tugas.index') }}" class="btn btn-nexus-outline btn-sm">Semua Tugas</a>
                </div>
                <div class="card-body-nexus">
                    @forelse ($tugasMenunggu as $tugas)
                        <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                            <div>
                                <div class="fw-semibold">{{ $tugas->judul }}</div>
                                <div class="small text-muted">
                                    {{ $tugas->mataPelajaran?->nama ?? $tugas->rombel?->kelas?->nama_kelas ?? '-' }}
                                    @if ($tugas->deadline)
                                        &middot; tenggat {{ $tugas->deadline->translatedFormat('j M H:i') }}
                                    @endif
                                </div>
                            </div>
                            @if ($tugas->deadline && $tugas->deadline->isBefore(now()->addDay()))
                                <span class="badge-nexus badge-danger">Segera</span>
                            @endif
                        </div>
                    @empty
                        <div class="text-center py-4">
                            <i class="fa-solid fa-circle-check fa-2x text-success mb-2"></i>
                            <p class="mb-0 text-muted">Semua tugas sudah dikerjakan.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-5">
            <div class="card-nexus mb-3">
                <div class="card-header-nexus">
                    <div>
                        <h5 class="card-title">Pengumuman</h5>
                        <p class="card-subtitle">Informasi terbaru sekolah</p>
                    </div>
                </div>
                <div class="card-body-nexus">
                    @forelse ($pengumuman as $item)
                        <div class="py-2 border-bottom">
                            <div class="fw-semibold">{{ $item->judul }}</div>
                            <div class="small text-muted">
                                {{ \Illuminate\Support\Str::limit(strip_tags($item->isi), 140) }}
                            </div>
                        </div>
                    @empty
                        <p class="text-muted mb-0">Belum ada pengumuman aktif.</p>
                    @endforelse
                </div>
            </div>

            <div class="card-nexus">
                <div class="card-header-nexus">
                    <div>
                        <h5 class="card-title">Tenggat Terdekat</h5>
                        <p class="card-subtitle">Tugas yang belum lewat batas</p>
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
                        <p class="text-muted mb-0">Tidak ada tenggat yang mendekati.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endif
@endsection
