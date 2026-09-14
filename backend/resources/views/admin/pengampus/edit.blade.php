@extends('layouts.app')

@section('title', 'Nexus Admin — Ubah Penugasan')
@section('breadcrumb', 'Pengampu')

@section('content')
<div class="page-header d-flex flex-wrap align-items-start justify-content-between gap-3">
    <div>
        <h1 class="page-title">Ubah Penugasan</h1>
        <p class="page-subtitle mb-0">{{ $pengampu->guru->nama ?? '-' }} • {{ $pengampu->mataPelajaran->kode ?? '-' }} • {{ $pengampu->kelas->nama_kelas ?? '-' }}</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('admin.pengampus.index') }}" class="btn btn-nexus-outline btn-sm"><i class="fa-solid fa-arrow-left"></i> Kembali</a>
    </div>
</div>

@include('layouts.partials.alert')

<div class="card-nexus">
    <div class="card-header-nexus"><div><h5 class="card-title">Form Penugasan</h5><p class="card-subtitle">Untuk tahun berbeda, buat baris baru</p></div></div>
    <div class="card-body-nexus">
        <form method="POST" action="{{ route('admin.pengampus.update', $pengampu) }}">
            @csrf
            @method('PUT')
            @include('admin.pengampus._form')
            <div class="d-flex gap-2 justify-content-end mt-2">
                <a href="{{ route('admin.pengampus.index') }}" class="btn btn-nexus-outline">Batal</a>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Perbarui</button>
            </div>
        </form>
    </div>
</div>
@endsection
