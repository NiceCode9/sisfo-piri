@extends('layouts.app')

@section('title', 'Nexus Admin — Pembayaran Lainnya')
@section('breadcrumb', 'Pembayaran Lainnya')

@section('content')
<div class="page-header d-flex flex-wrap align-items-start justify-content-between gap-3">
    <div>
        <h1 class="page-title">Pembayaran Lainnya</h1>
        <p class="page-subtitle mb-0">Biaya di luar biaya pendaftaran master</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        @can('pembayaran-lainnyas.view')
            <a href="{{ route('admin.pembayaran-lainnyas.export.excel', request()->query()) }}" class="btn btn-nexus-outline btn-sm"><i class="fa-solid fa-file-excel text-success"></i> Excel</a>
            <a href="{{ route('admin.pembayaran-lainnyas.export.pdf', request()->query()) }}" class="btn btn-nexus-outline btn-sm"><i class="fa-solid fa-file-pdf text-danger"></i> PDF</a>
        @endcan
        @can('pembayaran-lainnyas.create')
            <a href="{{ route('admin.pembayaran-lainnyas.create') }}" class="btn btn-primary btn-sm"><i class="fa-solid fa-plus"></i> Tambah</a>
        @endcan
    </div>
</div>

@include('layouts.partials.alert')

<div class="card-nexus">
    <div class="card-header-nexus">
        <div>
            <h5 class="card-title">Daftar Pembayaran Lainnya</h5>
            <p class="card-subtitle">{{ $pembayarans->total() }} data</p>
        </div>
        <form method="GET" action="{{ route('admin.pembayaran-lainnyas.index') }}" class="d-flex gap-2 flex-wrap">
            <div class="position-relative">
                <i class="fa-solid fa-magnifying-glass" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:var(--text-muted);font-size:13px;"></i>
                <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm ps-5" placeholder="Kode/Nama biaya/Nama..." style="width:200px" />
            </div>
            <select name="status" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()">
                <option value="">Semua Status</option>
                <option value="menunggu" @selected(request('status')==='menunggu')>Menunggu</option>
                <option value="berhasil" @selected(request('status')==='berhasil')>Berhasil</option>
                <option value="gagal" @selected(request('status')==='gagal')>Gagal</option>
            </select>
            <input type="date" name="tanggal_mulai" value="{{ request('tanggal_mulai') }}" class="form-control form-control-sm" style="width:auto" title="Tanggal mulai" onchange="this.form.submit()" />
            <input type="date" name="tanggal_sampai" value="{{ request('tanggal_sampai') }}" class="form-control form-control-sm" style="width:auto" title="Tanggal sampai" onchange="this.form.submit()" />
            @if(request('search') || request('status') || request('tanggal_mulai') || request('tanggal_sampai'))<a href="{{ route('admin.pembayaran-lainnyas.index') }}" class="btn btn-nexus-outline btn-sm">Reset</a>@endif
        </form>
    </div>
    <div class="card-body-nexus p-0">
        <div class="table-responsive">
            <table class="table-nexus w-100">
                <thead><tr><th>Kode</th><th>Calon</th><th>Nama Biaya</th><th>Jumlah</th><th>Metode</th><th>Status</th><th>Bukti</th><th>Aksi</th></tr></thead>
                <tbody>
                    @forelse($pembayarans as $p)
                        <tr>
                            <td style="font-size:12.5px;font-weight:600;">{{ $p->kode_pembayaran }}</td>
                            <td style="font-size:12.5px;">{{ $p->calonSiswa->nama_lengkap ?? '-' }}<div style="font-size:11px;color:var(--text-muted);">{{ $p->calonSiswa->no_pendaftaran ?? '' }}</div></td>
                            <td style="font-size:12.5px;">{{ $p->nama_biaya }}</td>
                            <td style="font-size:13px;">Rp {{ number_format($p->jumlah,0,',','.') }}</td>
                            <td><span class="badge-nexus badge-neutral">{{ $p->metode_pembayaran }}</span></td>
                            <td>
                                @php $badge = $p->status==='berhasil' ? 'badge-info' : ($p->status==='gagal' ? 'badge-danger' : 'badge-neutral'); @endphp
                                <span class="badge-nexus {{ $badge }}">{{ $p->status }}</span>
                            </td>
                            <td>
                                @if($p->bukti_pembayaran_path)
                                    <a href="{{ Storage::disk('public')->url($p->bukti_pembayaran_path) }}" target="_blank" class="text-primary" style="font-size:12px;">Lihat</a>
                                @else — @endif
                            </td>
                            <td>
                                <div class="d-flex gap-1">
                                    <a href="{{ route('admin.pembayaran-lainnyas.show', $p) }}" class="btn-icon btn btn-nexus-outline btn-sm"><i class="fa-solid fa-eye"></i></a>
                                    @can('pembayaran-lainnyas.edit')<a href="{{ route('admin.pembayaran-lainnyas.edit', $p) }}" class="btn-icon btn btn-nexus-outline btn-sm"><i class="fa-solid fa-pencil"></i></a>@endcan
                                    @can('pembayaran-lainnyas.delete')<form action="{{ route('admin.pembayaran-lainnyas.destroy', $p) }}" method="POST" class="d-inline">@csrf @method('DELETE')<button type="submit" class="btn-icon btn btn-nexus-outline btn-sm text-danger" data-confirm="Hapus {{ $p->kode_pembayaran }}?"><i class="fa-solid fa-trash"></i></button></form>@endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center py-4" style="color:var(--text-muted);">Belum ada pembayaran lainnya.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($pembayarans->hasPages())<div class="px-3 py-2 border-top d-flex justify-content-end" style="border-color:var(--border-color)!important;">{{ $pembayarans->links('pagination::bootstrap-5') }}</div>@endif
</div>
@endsection
