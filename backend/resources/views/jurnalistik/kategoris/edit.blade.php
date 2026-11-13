@extends('layouts.app')
@section('title', 'Jurnalistik — Edit Kategori')
@section('content')
<div class="page-header"><h1 class="page-title">Edit Kategori</h1></div>
@include('layouts.partials.alert')
<form method="POST" action="{{ route('jurnalistik.kategoris.update', $kategori) }}">
    @csrf @method('PUT')
    <div class="mb-3"><label class="form-label">Nama</label><input type="text" name="nama" class="form-control" value="{{ old('nama', $kategori->nama) }}" required></div>
    <div class="mb-3"><label class="form-label">Slug</label><input type="text" name="slug" class="form-control" value="{{ old('slug', $kategori->slug) }}"></div>
    <div class="mb-3"><label class="form-label">Deskripsi</label><textarea name="deskripsi" class="form-control" rows="3">{{ old('deskripsi', $kategori->deskripsi) }}</textarea></div>
    <button class="btn btn-primary">Simpan</button>
    <a href="{{ route('jurnalistik.kategoris.index') }}" class="btn btn-secondary">Batal</a>
</form>
@endsection
