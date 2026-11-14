@extends('layouts.app')
@section('title', 'Jurnalistik — Tambah Kategori')
@section('content')
<div class="page-header"><h1 class="page-title">Tambah Kategori</h1></div>
@include('layouts.partials.alert')
<div class="card-nexus mt-3">
    <div class="card-body-nexus">
        <form method="POST" action="{{ route('jurnalistik.kategoris.store') }}">
            @csrf
            <div class="mb-3"><label class="form-label">Nama</label><input type="text" name="nama" class="form-control" value="{{ old('nama') }}" required></div>
            <div class="mb-3"><label class="form-label">Slug (opsional)</label><input type="text" name="slug" class="form-control" value="{{ old('slug') }}"></div>
            <div class="mb-3"><label class="form-label">Deskripsi</label><textarea name="deskripsi" class="form-control" rows="3">{{ old('deskripsi') }}</textarea></div>
            <button class="btn btn-primary">Simpan</button>
            <a href="{{ route('jurnalistik.kategoris.index') }}" class="btn btn-secondary">Batal</a>
        </form>
    </div>
</div>
@endsection
