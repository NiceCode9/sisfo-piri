@extends('layouts.app')

@section('title', 'Nexus Admin — Calon Siswa')
@section('breadcrumb', 'Calon Siswa')

@section('content')
<div class="page-header d-flex flex-wrap align-items-start justify-content-between gap-3">
    <div>
        <h1 class="page-title">Calon Siswa</h1>
        <p class="page-subtitle mb-0">Kelola data pendaftar PPDB</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        @can('calon-siswas.create')
            <a href="{{ route('admin.calon-siswas.create') }}" class="btn btn-primary btn-sm">
                <i class="fa-solid fa-plus"></i> Tambah Calon
            </a>
        @endcan
    </div>
</div>

@include('layouts.partials.alert')

<div class="card-nexus">
    <div class="card-header-nexus">
        <div>
            <h5 class="card-title">Daftar Calon Siswa</h5>
            <p class="card-subtitle">{{ $calons->total() }} pendaftar</p>
        </div>
        <form method="GET" action="{{ route('admin.calon-siswas.index') }}" class="d-flex gap-2 flex-wrap">
            <select name="tahun" class="form-select form-select-sm" style="width: auto" onchange="this.form.submit()">
                <option value="aktif" @selected($tahunMode === 'aktif')>Tahun Aktif{{ $tahunAktif ? " ({$tahunAktif->nama_tahun_ajaran})" : '' }}</option>
                <option value="semua" @selected($tahunMode === 'semua')>Semua Tahun</option>
                @foreach ($tahunAjarans as $t)
                    <option value="{{ $t->id }}" @selected((string) $tahunMode === (string) $t->id)>{{ $t->nama_tahun_ajaran }}</option>
                @endforeach
            </select>
            <div class="position-relative">
                <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: 13px;"></i>
                <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm ps-5" placeholder="No/NIK/Nama..." style="width: 180px" />
            </div>
            <select name="jalur" class="form-select form-select-sm" style="width: auto" onchange="this.form.submit()">
                <option value="">Semua Jalur</option>
                @foreach ($jalurs as $j)
                    <option value="{{ $j->id }}" @selected((string) request('jalur') === (string) $j->id)>{{ $j->nama_jalur }}</option>
                @endforeach
            </select>
            <select name="status" class="form-select form-select-sm" style="width: auto" onchange="this.form.submit()">
                <option value="">Semua Status</option>
                <option value="menunggu" @selected(request('status')==='menunggu')>Menunggu</option>
                <option value="diterima" @selected(request('status')==='diterima')>Diterima</option>
                <option value="ditolak" @selected(request('status')==='ditolak')>Ditolak</option>
                <option value="daftar_ulang" @selected(request('status')==='daftar_ulang')>Daftar Ulang</option>
            </select>
            @if (request('search') || request('jalur') || request('status') || $tahunMode !== 'aktif')
                <a href="{{ route('admin.calon-siswas.index') }}" class="btn btn-nexus-outline btn-sm">Reset</a>
            @endif
        </form>
    </div>
    <div class="card-body-nexus p-0">
        <div class="table-responsive">
            <table class="table-nexus w-100" aria-label="Daftar Calon Siswa">
                <thead>
                    <tr><th>No Pendaftaran</th><th>Nama</th><th>Jalur</th><th>Status</th><th>Berkas</th><th>Aksi</th></tr>
                </thead>
                <tbody>
                    @forelse ($calons as $calon)
                        <tr>
                            <td>
                                <div style="font-weight: 600; font-size: 13px;">{{ $calon->no_pendaftaran }}</div>
                                <div style="font-size: 11px; color: var(--text-muted);">{{ $calon->nik }}</div>
                            </td>
                            <td style="font-size: 13px;">
                                {{ $calon->nama_lengkap }}
                                <div style="font-size: 11px; color: var(--text-muted);">{{ $calon->jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan' }} • {{ $calon->asal_sekolah ?? '-' }}</div>
                            </td>
                            <td style="font-size: 12.5px;">{{ $calon->jalurPendaftaran->nama_jalur ?? '-' }}</td>
                            <td>
                                @php $badge = match($calon->status_pendaftaran){ 'diterima'=>'badge-info','ditolak'=>'badge-danger','daftar_ulang'=>'badge-purple', default=>'badge-neutral'}; @endphp
                                <span class="badge-nexus {{ $badge }}">{{ $calon->status_pendaftaran }}</span>
                            </td>
                            <td>
                                @if ($calon->berkasCalonSiswa)
                                    @if ($calon->berkasCalonSiswa->status_verifikasi)
                                        <span class="badge-nexus badge-info">terverifikasi</span>
                                    @else
                                        <span class="badge-nexus badge-neutral">belum</span>
                                    @endif
                                @else
                                    <span style="font-size: 12px; color: var(--text-muted);">—</span>
                                @endif
                            </td>
                            <td>
                                <div class="d-flex gap-1">
                                    @can('calon-siswas.view')
                                        <a href="{{ route('admin.calon-siswas.show', $calon) }}" class="btn-icon btn btn-nexus-outline btn-sm" aria-label="Lihat"><i class="fa-solid fa-eye"></i></a>
                                    @endcan
                                    @can('calon-siswas.edit')
                                        <a href="{{ route('admin.calon-siswas.edit', $calon) }}" class="btn-icon btn btn-nexus-outline btn-sm" aria-label="Ubah"><i class="fa-solid fa-pencil"></i></a>
                                    @endcan
                                    @can('calon-siswas.delete')
                                        <form action="{{ route('admin.calon-siswas.destroy', $calon) }}" method="POST" class="d-inline">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn-icon btn btn-nexus-outline btn-sm text-danger" data-confirm="Hapus {{ $calon->nama_lengkap }}?"><i class="fa-solid fa-trash"></i></button>
                                        </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center py-4" style="color: var(--text-muted);">Belum ada pendaftar.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if ($calons->hasPages())
        <div class="px-3 py-2 border-top d-flex justify-content-end" style="border-color: var(--border-color) !important;">
            {{ $calons->links('pagination::bootstrap-5') }}
        </div>
    @endif
</div>
@endsection
