@extends('layouts.app')

@section('title', 'Nexus Admin — Edit Tugas')
@section('breadcrumb', 'Edit Tugas')

@section('content')
<div class="page-header"><h1 class="page-title">Edit Tugas</h1></div>
@include('layouts.partials.alert')
<div class="card-nexus"><div class="card-body-nexus">
    <form method="POST" action="{{ route('admin.tugas.update', $tugas) }}">
        @csrf @method('PUT')
        @include('admin.tugas._form')
        <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-floppy-disk"></i> Simpan</button>
        <a href="{{ route('admin.tugas.index') }}" class="btn btn-nexus-outline btn-sm">Batal</a>
    </form>
</div></div>
@endsection
