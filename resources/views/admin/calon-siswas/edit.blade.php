@extends('layouts.app')

@section('title', 'Nexus Admin — Ubah Calon Siswa')
@section('breadcrumb', 'Ubah Calon Siswa')

@section('content')
<div class="page-header d-flex flex-wrap align-items-start justify-content-between gap-3">
    <div>
        <h1 class="page-title">Ubah Calon Siswa</h1>
        <p class="page-subtitle mb-0">{{ $calon->no_pendaftaran }} — {{ $calon->nama_lengkap }}</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('admin.calon-siswas.show', $calon) }}" class="btn btn-nexus-outline btn-sm">
            <i class="fa-solid fa-eye"></i> Detail
        </a>
        <a href="{{ route('admin.calon-siswas.index') }}" class="btn btn-nexus-outline btn-sm">
            <i class="fa-solid fa-arrow-left"></i> Kembali
        </a>
    </div>
</div>

@include('layouts.partials.alert')

<div class="card-nexus">
    <div class="card-header-nexus">
        <div>
            <h5 class="card-title">Data Calon Siswa</h5>
            <p class="card-subtitle">Perbarui data pendaftar</p>
        </div>
    </div>
    <div class="card-body-nexus">
        <form method="POST" action="{{ route('admin.calon-siswas.update', $calon) }}">
            @csrf
            @method('PUT')
            @include('admin.calon-siswas._form', ['calon' => $calon, 'jalurs' => $jalurs, 'tahunAjarans' => $tahunAjarans, 'tahunAktif' => null, 'isEdit' => true])
            <div class="d-flex gap-2 justify-content-end mt-2">
                <a href="{{ route('admin.calon-siswas.show', $calon) }}" class="btn btn-nexus-outline">Batal</a>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Perbarui</button>
            </div>
        </form>
    </div>
</div>
@endsection
