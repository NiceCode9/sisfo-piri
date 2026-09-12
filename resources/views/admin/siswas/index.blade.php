@extends('layouts.app')

@section('title', 'Nexus Admin — Siswa')
@section('breadcrumb', 'Siswa')

@section('content')
<div class="page-header d-flex flex-wrap align-items-start justify-content-between gap-3">
    <div>
        <h1 class="page-title">Siswa</h1>
        <p class="page-subtitle mb-0">Kelola data siswa aktif</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        @can('siswas.create')
            <a href="{{ route('admin.siswas.template') }}" class="btn btn-nexus-outline btn-sm"><i class="fa-solid fa-file-excel"></i> Template</a>
            <button type="button" class="btn btn-nexus-outline btn-sm" data-bs-toggle="modal" data-bs-target="#modalImport"><i class="fa-solid fa-file-import"></i> Import</button>
            <a href="{{ route('admin.siswas.create') }}" class="btn btn-primary btn-sm"><i class="fa-solid fa-plus"></i> Tambah Siswa</a>
        @endcan
    </div>
</div>

@include('layouts.partials.alert')

<div class="card-nexus">
    <div class="card-header-nexus">
        <div>
            <h5 class="card-title">Daftar Siswa</h5>
            <p class="card-subtitle">{{ $siswas->total() }} siswa</p>
        </div>
        <form method="GET" action="{{ route('admin.siswas.index') }}" class="d-flex gap-2 flex-wrap">
            <div class="position-relative">
                <i class="fa-solid fa-magnifying-glass" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:var(--text-muted);font-size:13px;"></i>
                <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm ps-5" placeholder="NIS/NISN/Nama..." style="width:180px" />
            </div>
            <select name="kelas" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()">
                <option value="">Semua Kelas</option>
                @foreach ($kelases as $k)
                    <option value="{{ $k->id }}" @selected((string) request('kelas') === (string) $k->id)>{{ $k->nama_kelas }}</option>
                @endforeach
            </select>
            <select name="tahun" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()">
                <option value="aktif" @selected($tahunMode === 'aktif')>Tahun Aktif{{ $tahunAktif ? " ({$tahunAktif->nama_tahun_ajaran})" : '' }}</option>
                <option value="semua" @selected($tahunMode === 'semua')>Semua Tahun</option>
                @foreach ($tahunAjarans as $ta)
                    <option value="{{ $ta->id }}" @selected((string) $tahunMode === (string) $ta->id)>{{ $ta->nama_tahun_ajaran }}</option>
                @endforeach
            </select>
            @if(request('search') || request('kelas') || $tahunMode !== 'aktif')<a href="{{ route('admin.siswas.index') }}" class="btn btn-nexus-outline btn-sm">Reset</a>@endif
        </form>
    </div>
    <div class="card-body-nexus p-0">
        <div class="table-responsive">
            <table class="table-nexus w-100" aria-label="Daftar Siswa">
                <thead><tr><th>Nama</th><th>NIS / NISN</th><th>Kelas</th><th>Tahun</th><th>Status</th><th>Aksi</th></tr></thead>
                <tbody>
                    @forelse($siswas as $s)
                        <tr>
                            <td>
                                <div class="user-cell">
                                    <div class="avatar avatar-sm">{{ strtoupper(mb_substr($s->user->name ?? $s->calonSiswa->nama_lengkap ?? '?', 0, 1)) }}</div>
                                    <div><div class="u-name">{{ $s->user->name ?? $s->calonSiswa->nama_lengkap ?? '-' }}</div><div class="u-email">{{ $s->user->username ?? '-' }}</div></div>
                                </div>
                            </td>
                            <td style="font-size:12.5px;">{{ $s->nis ?? '-' }}<div style="font-size:11px;color:var(--text-muted);">{{ $s->nisn ?? '' }}</div></td>
                            <td><span class="badge-nexus badge-info">{{ $s->kelas->nama_kelas ?? '-' }}</span></td>
                            <td style="font-size:12.5px;">{{ $s->tahunAjaran->nama_tahun_ajaran ?? '-' }}</td>
                            <td>{!! $s->is_aktif ? '<span class="badge-nexus badge-info">aktif</span>' : '<span class="badge-nexus badge-neutral">nonaktif</span>' !!}</td>
                            <td>
                                <div class="d-flex gap-1">
                                    @can('siswas.view')
                                        <a href="{{ route('admin.siswas.show', $s) }}" class="btn-icon btn btn-nexus-outline btn-sm" aria-label="Detail"><i class="fa-solid fa-eye"></i></a>
                                        <a href="{{ route('admin.siswas.kartu', $s) }}" class="btn-icon btn btn-nexus-outline btn-sm" aria-label="Kartu" target="_blank"><i class="fa-solid fa-id-card"></i></a>
                                    @endcan
                                    @can('siswas.edit')<a href="{{ route('admin.siswas.edit', $s) }}" class="btn-icon btn btn-nexus-outline btn-sm" aria-label="Ubah"><i class="fa-solid fa-pencil"></i></a>@endcan
                                    @can('siswas.delete')<form action="{{ route('admin.siswas.destroy', $s) }}" method="POST" class="d-inline">@csrf @method('DELETE')<button type="submit" class="btn-icon btn btn-nexus-outline btn-sm text-danger" data-confirm="Hapus siswa {{ $s->user->name ?? '' }} beserta akunnya?"><i class="fa-solid fa-trash"></i></button></form>@endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center py-4" style="color:var(--text-muted);">Belum ada siswa.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($siswas->hasPages())<div class="px-3 py-2 border-top d-flex justify-content-end" style="border-color:var(--border-color)!important;">{{ $siswas->links('pagination::bootstrap-5') }}</div>@endif
</div>

@can('siswas.create')
<div class="modal fade" id="modalImport" tabindex="-1" aria-labelledby="modalImportLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalImportLabel"><i class="fa-solid fa-file-import me-2"></i>Import Data Siswa</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="{{ route('admin.siswas.import') }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <p style="font-size:13px;color:var(--text-secondary);">Upload file Excel sesuai template. Akun login dibuat otomatis (password = NISN) + riwayat kelas aktif.</p>
                    <div class="mb-3">
                        <a href="{{ route('admin.siswas.template') }}" class="btn btn-nexus-outline btn-sm"><i class="fa-solid fa-download"></i> Unduh Template Excel</a>
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
