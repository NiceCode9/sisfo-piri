@extends('layouts.app')

@section('title', 'Absensi Kelas')
@section('breadcrumb', 'Absensi Kelas')

@section('content')
<div class="page-header d-flex flex-wrap align-items-start justify-content-between gap-3">
    <div>
        <h1 class="page-title">Absensi Kelas</h1>
        <p class="page-subtitle mb-0">Scan kartu siswa atau cari nama untuk membuka daftar kelas hari ini.</p>
    </div>
</div>

@include('layouts.partials.alert')

@include('guru._nav', ['tabAktif' => 'absensi'])

<div class="row g-3">
    <div class="col-12 col-lg-7">
        <div class="card-nexus h-100">
            <div class="card-header-nexus">
                <div>
                    <h5 class="card-title">Scan Kartu Siswa</h5>
                    <p class="card-subtitle mb-0">Arahkan kamera ke QR di belakang kartu, atau ketik NISN bila kamera tidak tersedia.</p>
                </div>
            </div>
            <div class="card-body-nexus">
                <div data-scanner>
                    <video id="kamera-absensi" playsinline muted class="w-100 d-none" aria-label="Pratinjau kamera"></video>
                    <p id="status-kamera" class="mb-2" style="font-size:12.5px;color:var(--text-muted);">Kamera belum dinyalakan.</p>
                    <button type="button" id="mulai-kamera" class="btn btn-nexus-outline btn-sm">Mulai kamera</button>
                    <form method="POST" action="{{ route('guru.absensi.scan') }}" id="form-scan" class="d-flex gap-2 mt-3">
                        @csrf
                        <input type="hidden" name="mode" value="{{ $mode }}">
                        <input
                            type="text"
                            name="kode"
                            id="kode-kartu"
                            class="form-control"
                            placeholder="Scan kartu, tempel kode, atau ketik NISN"
                            autocomplete="off"
                            autocapitalize="off"
                            spellcheck="false"
                            required
                            autofocus
                        >
                        <button type="submit" class="btn btn-nexus text-nowrap">Catat</button>
                    </form>
                    <div id="hasil-scan" class="mt-3" aria-live="polite" style="min-height:38px;"></div>
                </div>

                <p class="mb-0 mt-3" style="font-size:12.5px;color:var(--text-muted);">
                    Jendela absen: buka <strong>{{ $jam_buka }}</strong>,
                    batas terlambat <strong>{{ $batas_terlambat }}</strong>,
                    tutup <strong>{{ $jam_tutup }}</strong>.
                </p>
            </div>
        </div>
    </div>
    <div class="col-12 col-lg-5">
        <div class="card-nexus h-100">
            <div class="card-header-nexus"><h5 class="card-title">Cara Kerja</h5></div>
            <div class="card-body-nexus" style="font-size:13px;">
                <p class="mb-2">Kelas diambil dari kartu siswa, jadi Anda tidak perlu memilih kelas.</p>
                <p class="mb-2">Scan sebelum batas terlambat tercatat <strong>hadir</strong>, sesudahnya <strong>terlambat</strong>.</p>
                <p class="mb-0">Siswa yang tidak membawa kartu atau datang setelah absen ditutup dicatat lewat antrian manual di bawah.</p>
            </div>
        </div>
    </div>
</div>

<div class="card-nexus mt-3">
    <div class="card-header-nexus"><h5 class="card-title">Cari Siswa</h5></div>
    <div class="card-body-nexus">
        <form method="GET" action="{{ route('guru.absensi.cari') }}" class="d-flex gap-2">
            <input type="search" name="q" value="{{ $q ?? '' }}" class="form-control" placeholder="Cari nama atau NISN" aria-label="Cari siswa">
            <button class="btn btn-nexus-outline text-nowrap">Cari</button>
        </form>

        @if (isset($hasilCari) && $hasilCari->isNotEmpty())
            <div class="table-responsive mt-3">
                <table class="table-nexus w-100">
                    <thead><tr><th>Siswa</th><th>Kelas</th><th style="width:90px;">Tindakan</th></tr></thead>
                    <tbody>
                        @foreach ($hasilCari as $s)
                            <tr>
                                <td>{{ $s->user?->name ?? $s->nis }} <span class="text-muted" style="font-size:11px;">{{ $s->nis }}</span></td>
                                <td>{{ $s->kelas_aktif ?? '-' }}</td>
                                <td>
                                    <form method="POST" action="{{ route('guru.absensi.pilih') }}">
                                        @csrf
                                        <input type="hidden" name="siswa_id" value="{{ $s->id }}">
                                        <button class="btn btn-nexus btn-sm">Buka</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @elseif (isset($q) && $q !== '')
            <p class="mb-0 mt-3" style="color:var(--text-muted);">Siswa tidak ditemukan di kelas yang Anda ampu.</p>
        @endif
    </div>
</div>

@if ($daftar)
    <div id="daftar-absensi" data-mode="{{ $mode }}" class="mt-3">
        @include('guru._absensi-kelas', $daftar + ['mode' => $mode])
    </div>
@else
    <div class="card-nexus mt-3">
        <div class="card-body-nexus text-center py-4">
            <p class="mb-0" style="color:var(--text-muted);">Scan kartu siswa atau cari nama/NISN untuk memulai.</p>
        </div>
    </div>
@endif

@include('guru._izin-sakit')

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.js"></script>
<script src="{{ asset('assets/absensi-scan.js') }}"></script>
@endpush
@endsection
