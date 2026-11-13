@extends('layouts.app')
@section('title', 'Jurnalistik — Tambah Tag')
@section('content')
<div class="page-header"><h1 class="page-title">Tambah Tag</h1></div>
@include('layouts.partials.alert')
<form method="POST" action="{{ route('jurnalistik.tags.store') }}">
    @csrf
    <div class="mb-3"><label class="form-label">Nama</label><input type="text" name="nama" class="form-control" value="{{ old('nama') }}" required></div>
    <div class="mb-3"><label class="form-label">Slug (opsional)</label><input type="text" name="slug" class="form-control" value="{{ old('slug') }}"></div>
    <button class="btn btn-primary">Simpan</button>
    <a href="{{ route('jurnalistik.tags.index') }}" class="btn btn-secondary">Batal</a>
</form>
@endsection
