@extends('layouts.app')

@section('title', 'Jurnalistik — Media')
@section('breadcrumb', 'Media')

@section('content')
<div class="page-header">
    <h1 class="page-title">Media</h1>
    <p class="page-subtitle mb-0">Unggah dan kelola gambar untuk berita.</p>
</div>

@include('layouts.partials.alert')

<div class="card-nexus mt-3">
    <div class="card-header-nexus">
        <div>
            <h5 class="card-title">Unggah Media</h5>
            <p class="card-subtitle">Format jpg/png/webp, maks 2MB.</p>
        </div>
    </div>
    <div class="card-body-nexus">
        <form method="POST" action="{{ route('jurnalistik.media.store') }}" enctype="multipart/form-data" class="d-flex gap-2 flex-wrap align-items-end">
            @csrf
            <div class="flex-grow-1" style="max-width: 420px;">
                <input type="file" name="file" class="form-control form-control-sm" accept="image/jpeg,image/png,image/webp" required>
            </div>
            <button type="submit" class="btn btn-primary btn-sm">
                <i class="fa-solid fa-upload me-1"></i> Unggah
            </button>
        </form>
    </div>
</div>

<div class="card-nexus mt-3">
    <div class="card-header-nexus">
        <div>
            <h5 class="card-title">Daftar Media</h5>
            <p class="card-subtitle">Total {{ $files->count() }} file</p>
        </div>
    </div>
    <div class="card-body-nexus p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Preview</th>
                        <th>Nama File</th>
                        <th>URL</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($files as $f)
                        <tr>
                            <td>
                                <img src="{{ $f['url'] }}" alt="{{ $f['name'] }}" style="width: 48px; height: 48px; object-fit: cover; border-radius: 6px;">
                            </td>
                            <td class="fw-semibold">{{ $f['name'] }}</td>
                            <td>
                                <input class="form-control form-control-sm" value="{{ $f['url'] }}" readonly onclick="this.select()">
                            </td>
                            <td class="text-end">
                                <form method="POST" action="{{ route('jurnalistik.media.destroy') }}" class="d-inline" onsubmit="return confirm('Hapus file ini?')">
                                    @csrf @method('DELETE')
                                    <input type="hidden" name="path" value="{{ $f['path'] }}">
                                    <button class="btn btn-sm btn-outline-danger" title="Hapus">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">Belum ada media.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection