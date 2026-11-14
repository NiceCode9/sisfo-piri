@extends('layouts.app')

@section('title', 'Jurnalistik — Berita')
@section('breadcrumb', 'Berita')

@section('content')
<div class="page-header d-flex flex-wrap align-items-start justify-content-between gap-3">
    <div>
        <h1 class="page-title">Daftar Berita</h1>
        <p class="page-subtitle mb-0">Kelola semua berita yang Anda tulis.</p>
    </div>
    <a href="{{ route('jurnalistik.beritas.create') }}" class="btn btn-primary btn-sm">
        <i class="fa-solid fa-plus me-1"></i> Tambah Berita
    </a>
</div>

@include('layouts.partials.alert')

{{-- Filter --}}
<div class="card-nexus mt-3">
    <div class="card-body-nexus">
        <form method="GET" action="{{ route('jurnalistik.beritas.index') }}" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label small">Cari</label>
                <input type="text" name="q" class="form-control form-control-sm" value="{{ request('q') }}" placeholder="Judul...">
            </div>
            <div class="col-md-3">
                <label class="form-label small">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">Semua</option>
                    <option value="draft" @selected(request('status') === 'draft')>Draft</option>
                    <option value="published" @selected(request('status') === 'published')>Published</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary btn-sm w-100">Filter</button>
            </div>
        </form>
    </div>
</div>

<div class="card-nexus mt-3">
    <div class="card-header-nexus">
        <div>
            <h5 class="card-title">Berita</h5>
            <p class="card-subtitle">Total {{ $beritas->total() }} berita</p>
        </div>
    </div>
    <div class="card-body-nexus p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Judul</th>
                        <th>Status</th>
                        <th>Kategori</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($beritas as $b)
                        <tr>
                            <td class="fw-semibold">{{ $b->judul }}</td>
                            <td>
                                @if($b->status === 'published')
                                    <span class="badge bg-success">Published</span>
                                @else
                                    <span class="badge bg-secondary">Draft</span>
                                @endif
                            </td>
                            <td>{{ $b->kategori?->nama }}</td>
                            <td class="text-end">
                                <a href="{{ route('jurnalistik.beritas.edit', $b) }}" class="btn btn-sm btn-outline-secondary" title="Edit">
                                    <i class="fa-solid fa-pen"></i>
                                </a>
                                <form method="POST" action="{{ route('jurnalistik.beritas.publish', $b) }}" class="d-inline">
                                    @csrf @method('PATCH')
                                    <button class="btn btn-sm btn-outline-primary" title="Toggle publish">
                                        <i class="fa-solid fa-toggle-on"></i>
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('jurnalistik.beritas.destroy', $b) }}" class="d-inline" onsubmit="return confirm('Hapus berita ini?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" title="Hapus">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">Belum ada berita.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-body-nexus border-top">
        {{ $beritas->links() }}
    </div>
</div>
@endsection