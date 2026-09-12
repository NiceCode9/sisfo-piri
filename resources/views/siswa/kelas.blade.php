@extends('layouts.app')

@section('title', 'Riwayat Kelas Saya')
@section('breadcrumb', 'Riwayat Kelas')

@section('content')
<div class="page-header d-flex flex-wrap align-items-start justify-content-between gap-3">
    <div>
        <h1 class="page-title">Riwayat Kelas</h1>
        <p class="page-subtitle mb-0">Perjalanan kelas dari tahun ke tahun</p>
    </div>
</div>

@include('layouts.partials.alert')

@include('siswa._nav', ['tabAktif' => 'kelas'])

<div class="card-nexus">
    <div class="card-body-nexus p-0">
        <div class="table-responsive">
            <table class="table-nexus w-100" style="font-size:13px;">
                <thead><tr><th>Kelas</th><th>Tahun Ajaran</th><th>Status</th></tr></thead>
                <tbody>
                    @forelse($riwayat as $r)
                        <tr>
                            <td style="font-weight:600;">{{ $r->kelas->nama_kelas ?? '-' }}</td>
                            <td>{{ $r->tahunAjaran->nama_tahun_ajaran ?? '-' }}</td>
                            <td><span class="badge-nexus badge-info">{{ $r->status }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-center py-4" style="color:var(--text-muted);">Belum ada riwayat kelas.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
