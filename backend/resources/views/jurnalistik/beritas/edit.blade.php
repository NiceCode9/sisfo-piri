@extends('layouts.app')

@section('title', 'Jurnalistik — Edit Berita')
@section('breadcrumb', 'Berita')

@section('content')
<div class="page-header">
    <h1 class="page-title">Edit Berita</h1>
    <p class="page-subtitle mb-0">{{ $berita->judul }}</p>
</div>
@include('layouts.partials.alert')
<div class="card-nexus mt-3">
    <div class="card-body-nexus">
        <form method="POST" action="{{ route('jurnalistik.beritas.update', $berita) }}" enctype="multipart/form-data">
    @csrf
    @method('PUT')
    <div class="mb-3">
        <label class="form-label">Judul</label>
        <input type="text" name="judul" class="form-control" value="{{ old('judul', $berita->judul) }}" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Slug</label>
        <input type="text" name="slug" class="form-control" value="{{ old('slug', $berita->slug) }}">
    </div>
    <div class="mb-3">
        <label class="form-label">Kategori</label>
        <select name="kategori_berita_id" class="form-select" required>
            @foreach($kategoris as $k)
                <option value="{{ $k->id }}" @selected($berita->kategori_berita_id == $k->id)>{{ $k->nama }}</option>
            @endforeach
        </select>
    </div>
    <div class="mb-3">
        <label class="form-label">Ringkasan</label>
        <textarea name="ringkasan" class="form-control" rows="2">{{ old('ringkasan', $berita->ringkasan) }}</textarea>
    </div>
    <div class="mb-3">
        <label class="form-label">Konten</label>
        <textarea name="konten" id="konten" class="form-control" rows="8">{{ old('konten', $berita->konten) }}</textarea>
    </div>
    <div class="mb-3">
        <label class="form-label">Gambar Utama (ganti)</label>
        @if($berita->gambar_utama)<div class="mb-1"><img src="{{ \Storage::disk('public')->url($berita->gambar_utama) }}" style="max-height:80px"></div>@endif
        <input type="file" name="gambar_utama" class="form-control" accept="image/*">
    </div>
    <div class="mb-3">
        <label class="form-label">Tambah Galeri</label>
        <input type="file" name="gallery[]" class="form-control" accept="image/*" multiple>
    </div>
    <div class="mb-3">
        <label class="form-label">Tag</label>
        @php $selectedTags = $berita->tags->pluck('nama')->toArray(); @endphp
        <select name="tags[]" class="form-select" multiple>
            @foreach($tags as $t)
                <option value="{{ $t->nama }}" @selected(in_array($t->nama, $selectedTags))>{{ $t->nama }}</option>
            @endforeach
        </select>
    </div>
    <div class="mb-3">
        <label class="form-label">Status</label>
        <select name="status" class="form-select">
            <option value="draft" @selected($berita->status=='draft')>Draft</option>
            <option value="published" @selected($berita->status=='published')>Published</option>
        </select>
    </div>
    <div class="mb-3">
        <label class="form-label">Jadwal Publish</label>
        <input type="datetime-local" name="published_at" class="form-control" value="{{ old('published_at', optional($berita->published_at)->format('Y-m-d\TH:i')) }}">
    </div>
    <div class="mb-3">
        <label class="form-label">Meta Title</label>
        <input type="text" name="meta_title" class="form-control" value="{{ old('meta_title', $berita->meta_title) }}">
    </div>
    <div class="mb-3">
        <label class="form-label">Meta Description</label>
        <textarea name="meta_description" class="form-control" rows="2">{{ old('meta_description', $berita->meta_description) }}</textarea>
    </div>
    <button class="btn btn-primary">Update</button>
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