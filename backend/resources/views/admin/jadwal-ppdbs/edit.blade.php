@extends('layouts.app')

@section('title', 'Nexus Admin — Ubah Jadwal')
@section('breadcrumb', 'Jadwal PPDB')

@section('content')
<div class="page-header d-flex flex-wrap align-items-start justify-content-between gap-3">
    <div>
        <h1 class="page-title">Ubah Jadwal PPDB</h1>
        <p class="page-subtitle mb-0">{{ $jadwal->nama_jadwal }}</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('admin.jadwal-ppdbs.index') }}" class="btn btn-nexus-outline btn-sm"><i class="fa-solid fa-arrow-left"></i> Kembali</a>
    </div>
</div>

@include('layouts.partials.alert')

<div class="card-nexus">
    <div class="card-header-nexus"><div><h5 class="card-title">Form Jadwal</h5><p class="card-subtitle">Periode ini tampil di timeline pendaftaran publik</p></div></div>
    <div class="card-body-nexus">
        <form method="POST" action="{{ route('admin.jadwal-ppdbs.update', $jadwal) }}">
            @csrf
            @method('PUT')
            @include('admin.jadwal-ppdbs._form')
            <div class="d-flex gap-2 justify-content-end mt-2">
                <a href="{{ route('admin.jadwal-ppdbs.index') }}" class="btn btn-nexus-outline">Batal</a>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Perbarui</button>
            </div>
        </form>
    </div>
</div>
@endsection
