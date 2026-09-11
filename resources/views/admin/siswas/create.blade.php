@extends('layouts.app')

@section('title', 'Nexus Admin — Tambah Siswa')
@section('breadcrumb', 'Tambah Siswa')

@section('content')
<div class="page-header d-flex flex-wrap align-items-start justify-content-between gap-3">
    <div>
        <h1 class="page-title">Tambah Siswa</h1>
        <p class="page-subtitle mb-0">Input manual + akun login otomatis (password = NISN)</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('admin.siswas.index') }}" class="btn btn-nexus-outline btn-sm"><i class="fa-solid fa-arrow-left"></i> Kembali</a>
    </div>
</div>

<div class="card-nexus">
    <div class="card-header-nexus">
        <div><h5 class="card-title">Data Siswa</h5><p class="card-subtitle">Termasuk data orang tua</p></div>
    </div>
    <div class="card-body-nexus">
        <form method="POST" action="{{ route('admin.siswas.store') }}">
            @csrf
            @include('admin.siswas._form', ['siswa' => null, 'kelases' => $kelases, 'tahunAjarans' => $tahunAjarans, 'tahunAktif' => $tahunAktif])
            <div class="d-flex gap-2 justify-content-end mt-2">
                <a href="{{ route('admin.siswas.index') }}" class="btn btn-nexus-outline">Batal</a>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Simpan</button>
            </div>
        </form>
    </div>
</div>
@endsection
