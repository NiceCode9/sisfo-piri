@extends('layouts.app')

@section('title', 'Nexus Admin — Tambah Tugas')
@section('breadcrumb', 'Tambah Tugas')

@section('content')
<div class="page-header"><h1 class="page-title">Tambah Tugas</h1></div>
@include('layouts.partials.alert')
<div class="card-nexus"><div class="card-body-nexus">
    <form method="POST" action="{{ route('admin.tugas.store') }}">
        @csrf
        @include('admin.tugas._form', ['tugas' => null])
        <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-floppy-disk"></i> Simpan</button>
        <a href="{{ route('admin.tugas.index') }}" class="btn btn-nexus-outline btn-sm">Batal</a>
    </form>
</div></div>
@endsection
