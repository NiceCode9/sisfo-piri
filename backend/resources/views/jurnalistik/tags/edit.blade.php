@extends('layouts.app')
@section('title', 'Jurnalistik — Edit Tag')
@section('content')
<div class="page-header"><h1 class="page-title">Edit Tag</h1></div>
@include('layouts.partials.alert')
<div class="card-nexus mt-3">
    <div class="card-body-nexus">
        <form method="POST" action="{{ route('jurnalistik.tags.update', $tag) }}">
            @csrf @method('PUT')
            <div class="mb-3"><label class="form-label">Nama</label><input type="text" name="nama" class="form-control" value="{{ old('nama', $tag->nama) }}" required></div>
            <div class="mb-3"><label class="form-label">Slug</label><input type="text" name="slug" class="form-control" value="{{ old('slug', $tag->slug) }}"></div>
            <button class="btn btn-primary">Simpan</button>
            <a href="{{ route('jurnalistik.tags.index') }}" class="btn btn-secondary">Batal</a>
        </form>
    </div>
</div>
@endsection
