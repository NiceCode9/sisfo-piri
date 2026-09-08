@extends('layouts.app')

@section('title', 'Nexus Admin — Tambah Tahun Ajaran')
@section('breadcrumb', 'Tahun Ajaran')

@section('content')
<div class="page-header d-flex flex-wrap align-items-start justify-content-between gap-3">
    <div>
        <h1 class="page-title">Tambah Tahun Ajaran</h1>
        <p class="page-subtitle mb-0">Buat tahun ajaran baru</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('admin.tahun-ajarans.index') }}" class="btn btn-nexus-outline btn-sm"><i class="fa-solid fa-arrow-left"></i> Kembali</a>
    </div>
</div>

<div class="card-nexus">
    <div class="card-header-nexus"><div><h5 class="card-title">Form Tahun Ajaran</h5><p class="card-subtitle">Contoh nama: 2025/2026</p></div></div>
    <div class="card-body-nexus">
        <form method="POST" action="{{ route('admin.tahun-ajarans.store') }}">
            @csrf
            @include('admin.tahun-ajarans._form')
            <div class="d-flex gap-2 justify-content-end mt-2">
                <a href="{{ route('admin.tahun-ajarans.index') }}" class="btn btn-nexus-outline">Batal</a>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Simpan</button>
            </div>
        </form>
    </div>
</div>
@endsection
