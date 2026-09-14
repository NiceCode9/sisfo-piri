@extends('layouts.app')

@section('title', 'Nexus Admin — Edit Materi')
@section('breadcrumb', 'Edit Materi')

@section('content')
<div class="page-header"><h1 class="page-title">Edit Materi</h1></div>
@include('layouts.partials.alert')
<div class="card-nexus"><div class="card-body-nexus">
    <form method="POST" action="{{ route('admin.materis.update', $materi) }}" enctype="multipart/form-data">
        @csrf @method('PUT')
        @include('admin.materis._form')
        <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-floppy-disk"></i> Simpan</button>
        <a href="{{ route('admin.materis.index') }}" class="btn btn-nexus-outline btn-sm">Batal</a>
    </form>
</div></div>
@endsection
