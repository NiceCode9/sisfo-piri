@extends('layouts.app')

@section('title', 'Nexus Admin — Bentuk Rombel')
@section('breadcrumb', 'Bentuk Rombel')

@section('content')
<div class="page-header d-flex flex-wrap align-items-start justify-content-between gap-3">
    <div>
        <h1 class="page-title">Bentuk Rombel</h1>
        <p class="page-subtitle mb-0">Satu kelas satu rombel per tahun ajaran</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('admin.rombels.index') }}" class="btn btn-nexus-outline btn-sm"><i class="fa-solid fa-arrow-left"></i> Kembali</a>
    </div>
</div>

<div class="card-nexus">
    <div class="card-header-nexus">
        <div><h5 class="card-title">Data Rombel</h5><p class="card-subtitle">Pilih kelas, tahun, dan wali</p></div>
    </div>
    <div class="card-body-nexus">
        <form method="POST" action="{{ route('admin.rombels.store') }}">
            @csrf
            @include('admin.rombels._form', ['rombel' => null, 'kelases' => $kelases, 'tahunAjarans' => $tahunAjarans, 'tahunAktif' => $tahunAktif, 'gurus' => $gurus])
            <div class="d-flex gap-2 justify-content-end mt-2">
                <a href="{{ route('admin.rombels.index') }}" class="btn btn-nexus-outline">Batal</a>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Simpan</button>
            </div>
        </form>
    </div>
</div>
@endsection
