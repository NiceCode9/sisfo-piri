@extends('layouts.app')

@section('title', 'Nexus Admin — Detail Tugas')
@section('breadcrumb', 'Detail Tugas')

@section('content')
<div class="page-header d-flex flex-wrap align-items-start justify-content-between gap-3">
    <div>
        <h1 class="page-title">{{ $tugas->judul }}</h1>
        <p class="page-subtitle mb-0">{{ $tugas->mataPelajaran->kode ?? '-' }} • {{ $tugas->rombel->kelas->nama_kelas ?? '-' }} • {{ $tugas->deadline ? $tugas->deadline->format('d M Y H:i') : 'Tanpa deadline' }}</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('admin.tugas.index') }}" class="btn btn-nexus-outline btn-sm"><i class="fa-solid fa-arrow-left"></i> Kembali</a>
        @can('tugas.nilai')<a href="{{ route('admin.tugas.nilai', $tugas) }}" class="btn btn-primary btn-sm"><i class="fa-solid fa-star"></i> Nilai</a>@endcan
        @can('tugas.edit')<a href="{{ route('admin.tugas.edit', $tugas) }}" class="btn btn-primary btn-sm"><i class="fa-solid fa-pencil"></i> Ubah</a>@endcan
    </div>
</div>

@include('layouts.partials.alert')

<div class="card-nexus">
    <div class="card-body-nexus">
        <p style="font-size:13px;"><strong>Deskripsi:</strong> {{ $tugas->deskripsi ?? '—' }}</p>
        <p style="font-size:13px;"><strong>Guru:</strong> {{ $tugas->guru->nama ?? '—' }} • <strong>Aktif:</strong> {{ $tugas->is_aktif ? 'Ya' : 'Tidak' }}</p>
    </div>
</div>

<div class="card-nexus mt-3">
    <div class="card-header-nexus"><h5 class="card-title">Pengumpulan ({{ $tugas->pengumpulans->count() }})</h5></div>
    <div class="card-body-nexus p-0">
        <div class="table-responsive"><table class="table-nexus w-100" style="font-size:13px;">
            <thead><tr><th>Siswa</th><th>Status</th><th>Nilai</th><th>File</th></tr></thead>
            <tbody>
                @forelse($tugas->pengumpulans as $p)
                    <tr>
                        <td style="font-weight:600;">{{ $p->siswa->user->name ?? '-' }}</td>
                        <td>@if($p->is_terlambat)<span class="badge-nexus badge-warning">Terlambat</span>@else<span class="badge-nexus badge-info">Tepat</span>@endif</td>
                        <td>{{ $p->nilai === null ? '—' : $p->nilai }}</td>
                        <td>@if($p->file_path)<a href="{{ Storage::disk('public')->url($p->file_path) }}" target="_blank" class="text-primary">Download</a>@else — @endif</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center py-4" style="color:var(--text-muted);">Belum ada pengumpulan.</td></tr>
                @endforelse
            </tbody>
        </table></div>
    </div>
</div>
@endsection
