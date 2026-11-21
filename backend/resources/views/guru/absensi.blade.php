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

@include('layouts.partials.alert', ['errorDitampilkan' => ['berkas', 'kode']])

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
                <form method="POST" action="{{ route('guru.absensi.scan') }}" id="form-scan" class="d-flex flex-column flex-sm-row gap-2">
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

                <div id="hasil-scan" class="mt-3" aria-live="polite"></div>

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
@endsection
