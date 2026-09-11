@extends('layouts.app')

@section('title', 'Nexus Admin — Ubah Rombel')
@section('breadcrumb', 'Ubah Rombel')

@section('content')
<div class="page-header d-flex flex-wrap align-items-start justify-content-between gap-3">
    <div>
        <h1 class="page-title">Ubah Rombel</h1>
        <p class="page-subtitle mb-0">{{ $rombel->kelas->nama_kelas ?? '-' }} — {{ $rombel->tahunAjaran->nama_tahun_ajaran ?? '-' }}</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('admin.rombels.show', $rombel) }}" class="btn btn-nexus-outline btn-sm"><i class="fa-solid fa-eye"></i> Histori</a>
        <a href="{{ route('admin.rombels.index') }}" class="btn btn-nexus-outline btn-sm"><i class="fa-solid fa-arrow-left"></i> Kembali</a>
    </div>
</div>

@include('layouts.partials.alert')

<div class="card-nexus">
    <div class="card-header-nexus">
        <div><h5 class="card-title">Data Rombel</h5><p class="card-subtitle">Perbarui kelas, tahun, atau wali</p></div>
    </div>
    <div class="card-body-nexus">
        <form method="POST" action="{{ route('admin.rombels.update', $rombel) }}">
            @csrf @method('PUT')
            @include('admin.rombels._form', ['rombel' => $rombel, 'kelases' => $kelases, 'tahunAjarans' => $tahunAjarans, 'tahunAktif' => null, 'gurus' => $gurus])
            <div class="d-flex gap-2 justify-content-end mt-2">
                <a href="{{ route('admin.rombels.index') }}" class="btn btn-nexus-outline">Batal</a>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Perbarui</button>
            </div>
        </form>
    </div>
</div>
@endsection
