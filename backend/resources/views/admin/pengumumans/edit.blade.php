@extends('layouts.app')

@section('title', 'Nexus Admin — Ubah Pengumuman')
@section('breadcrumb', 'Ubah Pengumuman')

@section('content')
<div class="page-header d-flex flex-wrap align-items-start justify-content-between gap-3">
    <div>
        <h1 class="page-title">Ubah Pengumuman</h1>
        <p class="page-subtitle mb-0">{{ $pengumuman->judul }}</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('admin.pengumumans.index') }}" class="btn btn-nexus-outline btn-sm"><i class="fa-solid fa-arrow-left"></i> Kembali</a>
    </div>
</div>

@include('layouts.partials.alert')

<div class="card-nexus">
    <div class="card-header-nexus"><div><h5 class="card-title">Data Pengumuman</h5><p class="card-subtitle">Perbarui konten</p></div></div>
    <div class="card-body-nexus">
        <form method="POST" action="{{ route('admin.pengumumans.update', $pengumuman) }}">
            @csrf @method('PUT')
            @include('admin.pengumumans._form', ['pengumuman'=>$pengumuman,'tahunAjarans'=>$tahunAjarans,'tahunAktif'=>null])
            <div class="d-flex gap-2 justify-content-end mt-2">
                <a href="{{ route('admin.pengumumans.index') }}" class="btn btn-nexus-outline">Batal</a>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Perbarui</button>
            </div>
        </form>
    </div>
</div>
@endsection
