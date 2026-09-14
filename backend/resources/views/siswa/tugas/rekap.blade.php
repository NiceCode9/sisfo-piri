@extends('layouts.app')

@section('title', 'Rekap Tugas Saya')
@section('breadcrumb', 'Rekap')

@section('content')
<div class="page-header"><h1 class="page-title">Rekap Nilai</h1><p class="page-subtitle mb-0">Nilai tugas harian Anda</p></div>
@include('layouts.partials.alert')
@include('siswa._nav', ['tabAktif' => 'tugas'])
<div class="card-nexus">
    <div class="card-header-nexus"><h5 class="card-title">Rata-rata: {{ $rata !== null ? round($rata,1) : '—' }}</h5></div>
    <div class="card-body-nexus p-0">
        <div class="table-responsive"><table class="table-nexus w-100" style="font-size:13px;">
            <thead><tr><th>Judul</th><th>Mapel</th><th>Nilai</th><th>Status</th></tr></thead>
            <tbody>
                @forelse($pengumpulans as $p)
                    <tr>
                        <td style="font-weight:600;">{{ $p->tugas->judul ?? '-' }}</td>
                        <td><span class="badge-nexus badge-info">{{ $p->tugas->mataPelajaran->kode ?? '-' }}</span></td>
                        <td>{{ $p->nilai === null ? '—' : $p->nilai }}@if($p->nilai !== null && $p->nilai < ($p->tugas->mataPelajaran->kkm ?? 75))<span class="badge-nexus badge-danger ms-1">KKM</span>@endif</td>
                        <td>@if($p->is_terlambat)<span class="badge-nexus badge-warning">Terlambat</span>@else<span class="badge-nexus badge-info">Tepat</span>@endif</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center py-4" style="color:var(--text-muted);">Belum ada pengumpulan.</td></tr>
                @endforelse
            </tbody>
        </table></div>
    </div>
</div>
@endsection
