@extends('layouts.app')

@section('title', 'Nexus Admin — Histori Rombel')
@section('breadcrumb', 'Histori Rombel')

@section('content')
<div class="page-header d-flex flex-wrap align-items-start justify-content-between gap-3">
    <div>
        <h1 class="page-title">Rombel {{ $rombel->kelas->nama_kelas ?? '-' }}</h1>
        <p class="page-subtitle mb-0">{{ $rombel->tahunAjaran->nama_tahun_ajaran ?? '-' }} • Wali: {{ $rombel->waliGuru->nama ?? '—' }}</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('admin.rombels.index') }}" class="btn btn-nexus-outline btn-sm"><i class="fa-solid fa-arrow-left"></i> Kembali</a>
        @can('rombels.edit')
            <a href="{{ route('admin.rombels.edit', $rombel) }}" class="btn btn-primary btn-sm"><i class="fa-solid fa-pencil"></i> Ubah</a>
        @endcan
    </div>
</div>

@include('layouts.partials.alert')

<div class="card-nexus">
    <div class="card-header-nexus">
        <div><h5 class="card-title">Penugasan ({{ $histori['penugasan']->count() }})</h5><p class="card-subtitle">Siapa mengajar mapel apa di rombel ini</p></div>
    </div>
    <div class="card-body-nexus p-0">
        <div class="table-responsive">
            <table class="table-nexus w-100">
                <thead><tr><th>Guru</th><th>Mapel</th></tr></thead>
                <tbody>
                    @forelse($histori['penugasan'] as $p)
                        <tr>
                            <td style="font-weight:600;font-size:13px;">{{ $p->guru->nama ?? '-' }}</td>
                            <td><span class="badge-nexus badge-info">{{ $p->mataPelajaran->kode ?? '-' }}</span> <span style="font-size:12.5px;">{{ $p->mataPelajaran->nama ?? '' }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="2" class="text-center py-4" style="color:var(--text-muted);">Belum ada penugasan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card-nexus mt-3">
    <div class="card-header-nexus">
        <div><h5 class="card-title">Siswa ({{ $histori['siswa']->count() }})</h5><p class="card-subtitle">Terdaftar di rombel ini tahun berjalan</p></div>
    </div>
    <div class="card-body-nexus p-0">
        <div class="table-responsive">
            <table class="table-nexus w-100">
                <thead><tr><th>Nama</th><th>NISN</th></tr></thead>
                <tbody>
                    @forelse($histori['siswa'] as $s)
                        <tr>
                            <td style="font-weight:600;font-size:13px;">{{ $s->user->name ?? $s->calonSiswa->nama_lengkap ?? '-' }}</td>
                            <td style="font-size:12.5px;">{{ $s->nisn ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="2" class="text-center py-4" style="color:var(--text-muted);">Belum ada siswa.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
