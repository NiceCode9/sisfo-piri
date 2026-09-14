@extends('layouts.app')

@section('title', 'Detail Tugas Anak')
@section('breadcrumb', 'Tugas')

@section('content')
<div class="page-header d-flex justify-content-between gap-3"><div><h1 class="page-title">{{ $tugas->judul }}</h1><p class="page-subtitle mb-0">{{ $tugas->mataPelajaran->kode ?? '-' }} • {{ $tugas->deadline ? $tugas->deadline->format('d M Y H:i') : 'Tanpa deadline' }}</p></div><a href="{{ route('ortu.tugas.index') }}" class="btn btn-nexus-outline btn-sm">Kembali</a></div>
@include('layouts.partials.alert')
<div class="card-nexus"><div class="card-body-nexus">
    <p style="font-size:13px;">{{ $tugas->deskripsi ?? '—' }}</p>
    @forelse($tugas->pengumpulans as $p)
        <div class="card-nexus mb-2"><div class="card-body-nexus">
            <div><strong>{{ $p->siswa->user->name ?? '-' }}</strong> @if($p->is_terlambat)<span class="badge-nexus badge-warning">Terlambat</span>@endif</div>
            @if($p->file_path)<div><a href="{{ Storage::disk('public')->url($p->file_path) }}" target="_blank" class="text-primary">File</a></div>@endif
            <div>Nilai: {{ $p->nilai === null ? '—' : $p->nilai }}</div>
            @if($p->catatan_guru)<div>Catatan: {{ $p->catatan_guru }}</div>@endif
        </div></div>
    @empty
        <p style="font-size:13px;color:var(--text-muted);">Belum ada pengumpulan untuk anak Anda.</p>
    @endforelse
</div></div>
@endsection
