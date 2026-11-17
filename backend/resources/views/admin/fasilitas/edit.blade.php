@extends('layouts.app')

@section('title', 'Nexus Admin — Edit Fasilitas')
@section('breadcrumb', 'Fasilitas')

@section('content')
<div class="page-header d-flex flex-wrap align-items-start justify-content-between gap-3">
    <div>
        <h1 class="page-title">Edit Fasilitas</h1>
        <p class="page-subtitle mb-0">{{ $fasilitas->nama }}</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('admin.fasilitas.index') }}" class="btn btn-nexus-outline btn-sm"><i class="fa-solid fa-arrow-left"></i> Kembali</a>
    </div>
</div>

<div class="card-nexus">
    <div class="card-header-nexus"><div><h5 class="card-title">Form Fasilitas</h5><p class="card-subtitle">Centang "Hapus" untuk menghapus foto lama</p></div></div>
    <div class="card-body-nexus">
        <form method="POST" action="{{ route('admin.fasilitas.update', $fasilitas) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            @include('admin.fasilitas._form', ['fasilitas' => $fasilitas, 'jenisList' => $jenisList])
            <div class="d-flex gap-2 justify-content-end mt-2">
                <a href="{{ route('admin.fasilitas.index') }}" class="btn btn-nexus-outline">Batal</a>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Perbarui</button>
            </div>
        </form>
    </div>
</div>
@endsection