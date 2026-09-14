@extends('layouts.app')

@section('title', 'Nexus Admin — Ubah Siswa')
@section('breadcrumb', 'Ubah Siswa')

@section('content')
<div class="page-header d-flex flex-wrap align-items-start justify-content-between gap-3">
    <div>
        <h1 class="page-title">Ubah Siswa</h1>
        <p class="page-subtitle mb-0">{{ $siswa->user->name ?? '-' }} — {{ $siswa->nisn }}</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('admin.siswas.show', $siswa) }}" class="btn btn-nexus-outline btn-sm"><i class="fa-solid fa-eye"></i> Detail</a>
        <a href="{{ route('admin.siswas.index') }}" class="btn btn-nexus-outline btn-sm"><i class="fa-solid fa-arrow-left"></i> Kembali</a>
    </div>
</div>

@include('layouts.partials.alert')

<div class="card-nexus">
    <div class="card-header-nexus">
        <div><h5 class="card-title">Data Siswa</h5><p class="card-subtitle">Ganti kelas otomatis mencatat riwayat</p></div>
    </div>
    <div class="card-body-nexus">
        <form method="POST" action="{{ route('admin.siswas.update', $siswa) }}">
            @csrf @method('PUT')
            @include('admin.siswas._form', ['siswa' => $siswa, 'kelases' => $kelases, 'tahunAjarans' => $tahunAjarans, 'tahunAktif' => null])
            <div class="d-flex gap-2 justify-content-end mt-2">
                <a href="{{ route('admin.siswas.show', $siswa) }}" class="btn btn-nexus-outline">Batal</a>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Perbarui</button>
            </div>
        </form>
    </div>
</div>
@endsection
