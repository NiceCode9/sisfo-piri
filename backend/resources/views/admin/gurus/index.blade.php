@extends('layouts.app')

@section('title', 'Nexus Admin — Guru')
@section('breadcrumb', 'Guru')

@section('content')
<div class="page-header d-flex flex-wrap align-items-start justify-content-between gap-3">
    <div>
        <h1 class="page-title">Guru</h1>
        <p class="page-subtitle mb-0">Kelola data guru beserta akun login</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        @can('gurus.create')
            <a href="{{ route('admin.gurus.template') }}" class="btn btn-nexus-outline btn-sm"><i class="fa-solid fa-file-excel"></i> Template</a>
            <button type="button" class="btn btn-nexus-outline btn-sm" data-bs-toggle="modal" data-bs-target="#modalImport"><i class="fa-solid fa-file-import"></i> Import</button>
            <a href="{{ route('admin.gurus.create') }}" class="btn btn-primary btn-sm"><i class="fa-solid fa-plus"></i> Tambah Guru</a>
        @endcan
    </div>
</div>

@include('layouts.partials.alert')

<div class="card-nexus">
    <div class="card-header-nexus">
        <div>
            <h5 class="card-title">Daftar Guru</h5>
            <p class="card-subtitle">{{ $gurus->total() }} guru</p>
        </div>
        <form method="GET" action="{{ route('admin.gurus.index') }}" class="d-flex gap-2 flex-wrap">
            <div class="position-relative">
                <i class="fa-solid fa-magnifying-glass" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:var(--text-muted);font-size:13px;"></i>
                <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm ps-5" placeholder="Cari nama/NIP..." style="width:200px" />
            </div>
            @if(request('search'))<a href="{{ route('admin.gurus.index') }}" class="btn btn-nexus-outline btn-sm">Reset</a>@endif
        </form>
    </div>
    <div class="card-body-nexus p-0">
        <div class="table-responsive">
            <table class="table-nexus w-100">
                <thead><tr><th>Nama</th><th>NIP</th><th>Username</th><th>L/P</th><th>Telp</th><th>Aktif</th><th>Aksi</th></tr></thead>
                <tbody>
                    @forelse($gurus as $g)
                        <tr>
                            <td style="font-weight:600;font-size:13px;">{{ $g->nama }}</td>
                            <td style="font-size:13px;">{{ $g->nip ?? '-' }}</td>
                            <td style="font-size:13px;">{{ $g->user->username ?? '-' }}</td>
                            <td style="font-size:13px;">{{ $g->jenis_kelamin }}</td>
                            <td style="font-size:13px;">{{ $g->telp ?? '-' }}</td>
                            <td>{!! $g->is_aktif ? '<span class="badge-nexus badge-info">aktif</span>' : '<span class="badge-nexus badge-neutral">nonaktif</span>' !!}</td>
                            <td>
                                <div class="d-flex gap-1">
                                    @can('gurus.edit')<a href="{{ route('admin.gurus.edit', $g) }}" class="btn-icon btn btn-nexus-outline btn-sm"><i class="fa-solid fa-pencil"></i></a>@endcan
                                    @can('gurus.delete')<form action="{{ route('admin.gurus.destroy', $g) }}" method="POST" class="d-inline">@csrf @method('DELETE')<button type="submit" class="btn-icon btn btn-nexus-outline btn-sm text-danger" data-confirm="Hapus {{ $g->nama }} beserta akunnya?"><i class="fa-solid fa-trash"></i></button></form>@endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center py-4" style="color:var(--text-muted);">Belum ada guru.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($gurus->hasPages())<div class="px-3 py-2 border-top d-flex justify-content-end" style="border-color:var(--border-color)!important;">{{ $gurus->links('pagination::bootstrap-5') }}</div>@endif
</div>

@can('gurus.create')
<div class="modal fade" id="modalImport" tabindex="-1" aria-labelledby="modalImportLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalImportLabel"><i class="fa-solid fa-file-import me-2"></i>Import Data Guru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="{{ route('admin.gurus.import') }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <p style="font-size:13px;color:var(--text-secondary);">
                        Upload file Excel sesuai template. NIP yang belum terdaftar dibuat sebagai guru baru sekaligus
                        akun login (<code>username</code> = NIP, <code>password</code> = NIP). NIP yang sudah ada hanya
                        memperbarui data profil, password tidak berubah.
                    </p>
                    <p style="font-size:12px;color:var(--text-muted);">
                        Format kolom NIP sebagai Teks agar digit tidak hilang.
                    </p>
                    <div class="mb-3">
                        <a href="{{ route('admin.gurus.template') }}" class="btn btn-nexus-outline btn-sm"><i class="fa-solid fa-download"></i> Unduh Template Excel</a>
                    </div>
                    <input type="file" name="file" accept=".xlsx,.xls,.csv" class="form-control" required />
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-nexus-outline" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-upload"></i> Import</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endcan
@endsection
