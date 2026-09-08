@extends('layouts.app')

@section('title', 'Nexus Admin — Ubah Tahun Ajaran')
@section('breadcrumb', 'Tahun Ajaran')

@section('content')
<div class="page-header d-flex flex-wrap align-items-start justify-content-between gap-3">
    <div>
        <h1 class="page-title">Ubah Tahun Ajaran</h1>
        <p class="page-subtitle mb-0">{{ $tahunAjaran->nama_tahun_ajaran }}</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('admin.tahun-ajarans.index') }}" class="btn btn-nexus-outline btn-sm"><i class="fa-solid fa-arrow-left"></i> Kembali</a>
    </div>
</div>

@include('layouts.partials.alert')

<div class="card-nexus">
    <div class="card-header-nexus"><div><h5 class="card-title">Form Tahun Ajaran</h5><p class="card-subtitle">Mengaktifkan tahun ini menonaktifkan tahun lain</p></div></div>
    <div class="card-body-nexus">
        <form method="POST" action="{{ route('admin.tahun-ajarans.update', $tahunAjaran) }}">
            @csrf
            @method('PUT')
            @include('admin.tahun-ajarans._form')
            <div class="d-flex gap-2 justify-content-end mt-2">
                <a href="{{ route('admin.tahun-ajarans.index') }}" class="btn btn-nexus-outline">Batal</a>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Perbarui</button>
            </div>
        </form>
    </div>
</div>
@endsection
