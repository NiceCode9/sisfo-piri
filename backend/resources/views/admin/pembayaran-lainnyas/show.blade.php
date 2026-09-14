@extends('layouts.app')

@section('title', 'Nexus Admin — Detail Pembayaran Lainnya')
@section('breadcrumb', 'Detail Pembayaran Lainnya')

@section('content')
<div class="page-header d-flex flex-wrap align-items-start justify-content-between gap-3">
    <div>
        <h1 class="page-title">{{ $pembayaran->kode_pembayaran }}</h1>
        <p class="page-subtitle mb-0">{{ $pembayaran->calonSiswa->nama_lengkap ?? '-' }} • {{ $pembayaran->nama_biaya }}</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('admin.pembayaran-lainnyas.index') }}" class="btn btn-nexus-outline btn-sm"><i class="fa-solid fa-arrow-left"></i> Kembali</a>
        <a href="{{ route('admin.pembayaran-lainnyas.edit', $pembayaran) }}" class="btn btn-primary btn-sm"><i class="fa-solid fa-pencil"></i> Ubah</a>
    </div>
</div>

@include('layouts.partials.alert')

@push('styles')
<style>
    .pay-show-summary {
        background: linear-gradient(135deg, var(--accent-primary), #7c6cf0);
        color: #fff;
        border-radius: 14px;
        padding: 18px 22px;
        display: flex;
        flex-wrap: wrap;
        gap: 16px;
        align-items: center;
        justify-content: space-between;
        box-shadow: 0 8px 24px rgba(79, 70, 229, 0.25);
    }
    .pay-show-summary .pay-code { font-size: 12px; opacity: 0.85; }
    .pay-show-summary .pay-total { font-size: 26px; font-weight: 800; line-height: 1.1; }
    .pay-show-summary .pay-meta { font-size: 12.5px; opacity: 0.9; }
    .pay-show-summary .badge-nexus { background: rgba(255, 255, 255, 0.22); color: #fff; }
    .pay-show-kv { display: grid; grid-template-columns: 130px 1fr; gap: 6px 12px; font-size: 13px; }
    .pay-show-kv dt { color: var(--text-muted); font-weight: 600; }
    .pay-show-kv dd { margin: 0; color: var(--text-primary); font-weight: 500; overflow-wrap: anywhere; }
    .pay-show-bukti img { max-height: 220px; border-radius: 10px; border: 1px solid var(--border-color); }
</style>
@endpush

<div class="pay-show-summary mb-3">
    <div>
        <div class="pay-code">{{ $pembayaran->kode_pembayaran }} • {{ $pembayaran->metode_pembayaran }}</div>
        <div class="pay-total">Rp {{ number_format($pembayaran->jumlah, 0, ',', '.') }}</div>
        <div class="pay-meta">
            {{ $pembayaran->calonSiswa->nama_lengkap ?? '-' }} ({{ $pembayaran->calonSiswa->no_pendaftaran ?? '-' }}) •
            {{ $pembayaran->tanggal_pembayaran ? \Carbon\Carbon::parse($pembayaran->tanggal_pembayaran)->format('d M Y') : 'Tanggal belum diisi' }}
        </div>
    </div>
    <div class="d-flex gap-2 align-items-center flex-wrap">
        <span class="badge-nexus {{ $pembayaran->status === 'berhasil' ? 'badge-info' : ($pembayaran->status === 'gagal' ? 'badge-danger' : 'badge-neutral') }}" style="font-size: 13px;">{{ $pembayaran->status }}</span>
        @if($pembayaran->bukti_pembayaran_path)
            <a href="{{ Storage::disk('public')->url($pembayaran->bukti_pembayaran_path) }}" target="_blank" class="btn btn-sm btn-light"><i class="fa-solid fa-receipt"></i> Lihat Bukti</a>
        @endif
    </div>
</div>

<div class="row g-3">
    <div class="col-12 col-lg-8">
        <div class="card-nexus mb-3">
            <div class="card-header-nexus"><h5 class="card-title">Detail Pembayaran</h5></div>
            <div class="card-body-nexus">
                <dl class="pay-show-kv">
                    <dt>Calon Siswa</dt>
                    <dd>{{ $pembayaran->calonSiswa->nama_lengkap ?? '-' }} ({{ $pembayaran->calonSiswa->no_pendaftaran ?? '-' }})</dd>
                    <dt>Nama Biaya</dt>
                    <dd>{{ $pembayaran->nama_biaya }}</dd>
                    <dt>Metode</dt>
                    <dd><span class="badge-nexus badge-neutral">{{ $pembayaran->metode_pembayaran }}</span></dd>
                    <dt>Tanggal</dt>
                    <dd>{{ $pembayaran->tanggal_pembayaran ? \Carbon\Carbon::parse($pembayaran->tanggal_pembayaran)->format('d M Y') : '-' }}</dd>
                    @if($pembayaran->catatan)
                        <dt>Catatan</dt>
                        <dd>{{ $pembayaran->catatan }}</dd>
                    @endif
                </dl>
            </div>
        </div>

        <div class="card-nexus mb-3">
            <div class="card-header-nexus"><h5 class="card-title">Bukti Pembayaran</h5></div>
            <div class="card-body-nexus pay-show-bukti">
                @if($pembayaran->bukti_pembayaran_path)
                    @php $ext = strtolower(pathinfo($pembayaran->bukti_pembayaran_path, PATHINFO_EXTENSION)); @endphp
                    @if(in_array($ext, ['jpg', 'jpeg', 'png', 'webp']))
                        <a href="{{ Storage::disk('public')->url($pembayaran->bukti_pembayaran_path) }}" target="_blank">
                            <img src="{{ Storage::disk('public')->url($pembayaran->bukti_pembayaran_path) }}" alt="Bukti {{ $pembayaran->kode_pembayaran }}" class="img-fluid" />
                        </a>
                    @else
                        <a href="{{ Storage::disk('public')->url($pembayaran->bukti_pembayaran_path) }}" target="_blank" class="btn btn-nexus-outline btn-sm"><i class="fa-solid fa-file-pdf"></i> Lihat Bukti ({{ strtoupper($ext) }})</a>
                    @endif
                @else
                    <p class="mb-0" style="font-size:13px;color:var(--text-muted);">Belum ada bukti terlampir.</p>
                @endif
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-4">
        <div class="card-nexus mb-3">
            <div class="card-header-nexus"><h5 class="card-title">Verifikasi</h5></div>
            <div class="card-body-nexus">
                <form method="POST" action="{{ route('admin.pembayaran-lainnyas.status', $pembayaran) }}" class="d-flex flex-column gap-2">
                    @csrf @method('PATCH')
                    <select name="status" class="form-select form-select-sm">
                        <option value="menunggu" @selected($pembayaran->status==='menunggu')>Menunggu</option>
                        <option value="berhasil" @selected($pembayaran->status==='berhasil')>Berhasil</option>
                        <option value="gagal" @selected($pembayaran->status==='gagal')>Gagal</option>
                    </select>
                    <button type="submit" class="btn btn-primary btn-sm w-100">Ubah Status</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
