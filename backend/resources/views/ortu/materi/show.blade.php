@extends('layouts.app')

@section('title', 'Detail Materi Anak')
@section('breadcrumb', 'Materi')

@section('content')
<div class="page-header d-flex justify-content-between gap-3"><div><h1 class="page-title">{{ $materi->judul }}</h1><p class="page-subtitle mb-0">{{ $materi->mataPelajaran->kode ?? '-' }} • {{ $materi->tipe }}</p></div><a href="{{ route('ortu.materi.index') }}" class="btn btn-nexus-outline btn-sm">Kembali</a></div>
@include('layouts.partials.alert')
<div class="card-nexus"><div class="card-body-nexus">
    <p style="font-size:13px;">{{ $materi->deskripsi ?? '—' }}</p>
    @if($materi->file_path)<p><a href="{{ Storage::disk('public')->url($materi->file_path) }}" target="_blank" class="btn btn-nexus-outline btn-sm">Download File</a></p>@endif
    @if($materi->url)<p><a href="{{ $materi->url }}" target="_blank" class="text-primary">{{ $materi->url }}</a></p>@endif
</div></div>
@endsection
