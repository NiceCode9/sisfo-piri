@extends('layouts.app')

@section('title', 'Jurnalistik — Tambah Berita')
@section('breadcrumb', 'Berita')

@section('content')
<div class="page-header">
    <h1 class="page-title">Tambah Berita</h1>
    <p class="page-subtitle mb-0">Tulis berita baru.</p>
</div>
@include('layouts.partials.alert')
<div class="card-nexus mt-3">
    <div class="card-body-nexus">
        <form method="POST" action="{{ route('jurnalistik.beritas.store') }}" enctype="multipart/form-data">
    @csrf
    <div class="mb-3">
        <label class="form-label">Judul</label>
        <input type="text" name="judul" class="form-control" value="{{ old('judul') }}" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Slug (opsional)</label>
        <input type="text" name="slug" class="form-control" value="{{ old('slug') }}">
    </div>
    <div class="mb-3">
        <label class="form-label">Kategori</label>
        <select name="kategori_berita_id" class="form-select" required>
            @foreach($kategoris as $k)
                <option value="{{ $k->id }}">{{ $k->nama }}</option>
            @endforeach
        </select>
    </div>
    <div class="mb-3">
        <label class="form-label">Ringkasan</label>
        <textarea name="ringkasan" class="form-control" rows="2">{{ old('ringkasan') }}</textarea>
    </div>
    <div class="mb-3">
        <label class="form-label">Konten</label>
        <textarea name="konten" id="konten" class="form-control" rows="8">{{ old('konten') }}</textarea>
    </div>
    <div class="mb-3">
        <label class="form-label">Gambar Utama</label>
        <input type="file" name="gambar_utama" class="form-control" accept="image/*">
    </div>
    <div class="mb-3">
        <label class="form-label">Galeri</label>
        <input type="file" name="gallery[]" class="form-control" accept="image/*" multiple>
    </div>
    <div class="mb-3">
        <label class="form-label">Tag</label>
        <select name="tags[]" class="form-select" multiple>
            @foreach($tags as $t)
                <option value="{{ $t->nama }}">{{ $t->nama }}</option>
            @endforeach
        </select>
        <small class="text-muted">Pilih tag; nama baru bisa diketik? gunakan nama yang ada.</small>
    </div>
    <div class="mb-3">
        <label class="form-label">Status</label>
        <select name="status" class="form-select">
            <option value="draft">Draft</option>
            <option value="published">Published</option>
        </select>
    </div>
    <div class="mb-3">
        <label class="form-label">Jadwal Publish</label>
        <input type="datetime-local" name="published_at" class="form-control" value="{{ old('published_at') }}">
    </div>
    <div class="mb-3">
        <label class="form-label">Meta Title</label>
        <input type="text" name="meta_title" class="form-control" value="{{ old('meta_title') }}">
    </div>
    <div class="mb-3">
        <label class="form-label">Meta Description</label>
        <textarea name="meta_description" class="form-control" rows="2">{{ old('meta_description') }}</textarea>
    </div>
    <button class="btn btn-primary">Simpan</button>
    <a href="{{ route('jurnalistik.beritas.index') }}" class="btn btn-secondary">Batal</a>
</form>
    </div>
</div>
@push('scripts')
<script src="https://cdn.ckeditor.com/ckeditor5/41.4.2/classic/ckeditor.js"></script>
<script>
ClassicEditor.create(document.querySelector('#konten')).catch(console.error);
</script>
@endpush
@endsection