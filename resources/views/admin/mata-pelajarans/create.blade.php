@extends('layouts.app')

@section('title', 'Nexus Admin — Tambah Mapel')
@section('breadcrumb', 'Mata Pelajaran')

@section('content')
<div class="page-header d-flex flex-wrap align-items-start justify-content-between gap-3">
    <div>
        <h1 class="page-title">Tambah Mata Pelajaran</h1>
        <p class="page-subtitle mb-0">Buat mapel baru</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('admin.mata-pelajarans.index') }}" class="btn btn-nexus-outline btn-sm"><i class="fa-solid fa-arrow-left"></i> Kembali</a>
    </div>
</div>

<div class="card-nexus">
    <div class="card-header-nexus"><div><h5 class="card-title">Form Mapel</h5><p class="card-subtitle">Kode unik, mis. MTK</p></div></div>
    <div class="card-body-nexus">
        <form method="POST" action="{{ route('admin.mata-pelajarans.store') }}">
            @csrf
            @include('admin.mata-pelajarans._form')
            <div class="d-flex gap-2 justify-content-end mt-2">
                <a href="{{ route('admin.mata-pelajarans.index') }}" class="btn btn-nexus-outline">Batal</a>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Simpan</button>
            </div>
        </form>
    </div>
</div>
@endsection
