@extends('layouts.app')

@section('title', 'Nexus Admin — Tetapkan Wali')
@section('breadcrumb', 'Wali Kelas')

@section('content')
<div class="page-header d-flex flex-wrap align-items-start justify-content-between gap-3">
    <div>
        <h1 class="page-title">Tetapkan Wali Kelas</h1>
        <p class="page-subtitle mb-0">Pilih guru untuk satu kelas satu tahun</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('admin.wali-kelas.index') }}" class="btn btn-nexus-outline btn-sm"><i class="fa-solid fa-arrow-left"></i> Kembali</a>
    </div>
</div>

<div class="card-nexus">
    <div class="card-header-nexus"><div><h5 class="card-title">Form Wali Kelas</h5><p class="card-subtitle">Contoh: Guru A → 7A → 2026/2027</p></div></div>
    <div class="card-body-nexus">
        <form method="POST" action="{{ route('admin.wali-kelas.store') }}">
            @csrf
            @include('admin.wali-kelas._form')
            <div class="d-flex gap-2 justify-content-end mt-2">
                <a href="{{ route('admin.wali-kelas.index') }}" class="btn btn-nexus-outline">Batal</a>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Simpan</button>
            </div>
        </form>
    </div>
</div>
@endsection
