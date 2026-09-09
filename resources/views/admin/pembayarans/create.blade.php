@extends('layouts.app')

@section('title', 'Nexus Admin — Tambah Pembayaran')
@section('breadcrumb', 'Tambah Pembayaran')

@section('content')
<div class="page-header d-flex flex-wrap align-items-start justify-content-between gap-3">
    <div>
        <h1 class="page-title">Tambah Pembayaran</h1>
        <p class="page-subtitle mb-0">Buat pembayaran manual (admin upload → otomatis berhasil)</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('admin.pembayarans.index') }}" class="btn btn-nexus-outline btn-sm"><i class="fa-solid fa-arrow-left"></i> Kembali</a>
    </div>
</div>

<div class="card-nexus">
    <div class="card-header-nexus"><div><h5 class="card-title">Data Pembayaran</h5><p class="card-subtitle">Kode akan digenerate PAY-YYYY-XXXX</p></div></div>
    <div class="card-body-nexus">
        <form method="POST" action="{{ route('admin.pembayarans.store') }}" enctype="multipart/form-data">
            @csrf
            @include('admin.pembayarans._form', ['pembayaran'=>null,'calons'=>$calons,'biayas'=>$biayas])
            <div class="d-flex gap-2 justify-content-end mt-2">
                <a href="{{ route('admin.pembayarans.index') }}" class="btn btn-nexus-outline">Batal</a>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Simpan</button>
            </div>
        </form>
    </div>
</div>
@endsection
