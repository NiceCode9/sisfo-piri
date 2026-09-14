@extends('layouts.app')

@section('title', 'Nexus Admin — Tambah Materi')
@section('breadcrumb', 'Tambah Materi')

@section('content')
<div class="page-header"><h1 class="page-title">Tambah Materi</h1></div>
@include('layouts.partials.alert')
<div class="card-nexus"><div class="card-body-nexus">
    <form method="POST" action="{{ route('admin.materis.store') }}" enctype="multipart/form-data">
        @csrf
        @include('admin.materis._form', ['materi' => null])
        <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-floppy-disk"></i> Simpan</button>
        <a href="{{ route('admin.materis.index') }}" class="btn btn-nexus-outline btn-sm">Batal</a>
    </form>
</div></div>
@endsection
