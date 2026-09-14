@extends('layouts.app')

@section('title', 'Nexus Admin — Detail Materi')
@section('breadcrumb', 'Detail Materi')

@section('content')
<div class="page-header d-flex flex-wrap align-items-start justify-content-between gap-3">
    <div>
        <h1 class="page-title">{{ $materi->judul }}</h1>
        <p class="page-subtitle mb-0">{{ $materi->mataPelajaran->kode ?? '-' }} • {{ $materi->rombel->kelas->nama_kelas ?? '-' }} • {{ $materi->tipe }}</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('admin.materis.index') }}" class="btn btn-nexus-outline btn-sm"><i class="fa-solid fa-arrow-left"></i> Kembali</a>
        @can('materis.edit')<a href="{{ route('admin.materis.edit', $materi) }}" class="btn btn-primary btn-sm"><i class="fa-solid fa-pencil"></i> Ubah</a>@endcan
    </div>
</div>

@include('layouts.partials.alert')

<div class="card-nexus">
    <div class="card-body-nexus">
        <p style="font-size:13px;"><strong>Deskripsi:</strong> {{ $materi->deskripsi ?? '—' }}</p>
        @if($materi->file_path)
            <p style="font-size:13px;"><strong>File:</strong> <a href="{{ Storage::disk('public')->url($materi->file_path) }}" target="_blank" class="text-primary">Download</a> ({{ $materi->file_path }})</p>
        @endif
        @if($materi->url)
            <p style="font-size:13px;"><strong>Link:</strong> <a href="{{ $materi->url }}" target="_blank" class="text-primary">{{ $materi->url }}</a></p>
        @endif
        <p style="font-size:13px;"><strong>Guru:</strong> {{ $materi->guru->nama ?? '—' }} • <strong>Aktif:</strong> {{ $materi->is_aktif ? 'Ya' : 'Tidak' }}</p>
    </div>
</div>
@endsection
