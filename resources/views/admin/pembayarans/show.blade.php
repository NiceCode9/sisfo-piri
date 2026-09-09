@extends('layouts.app')

@section('title', 'Nexus Admin — Detail Pembayaran')
@section('breadcrumb', 'Detail Pembayaran')

@section('content')
<div class="page-header d-flex flex-wrap align-items-start justify-content-between gap-3">
    <div>
        <h1 class="page-title">{{ $pembayaran->kode_pembayaran }}</h1>
        <p class="page-subtitle mb-0">{{ $pembayaran->calonSiswa->nama_lengkap ?? '-' }} • {{ $pembayaran->biayaPendaftaran->jenis_biaya ?? '-' }}</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('admin.pembayarans.index') }}" class="btn btn-nexus-outline btn-sm"><i class="fa-solid fa-arrow-left"></i> Kembali</a>
        <a href="{{ route('admin.pembayarans.edit', $pembayaran) }}" class="btn btn-primary btn-sm"><i class="fa-solid fa-pencil"></i> Ubah</a>
    </div>
</div>

@include('layouts.partials.alert')

<div class="card-nexus">
    <div class="card-header-nexus"><h5 class="card-title">Detail Pembayaran</h5><span class="badge-nexus {{ $pembayaran->status==='berhasil'?'badge-info':($pembayaran->status==='gagal'?'badge-danger':'badge-neutral') }}">{{ $pembayaran->status }}</span></div>
    <div class="card-body-nexus">
        <div class="row g-3" style="font-size:13px;">
            <div class="col-6"><strong>Calon:</strong> {{ $pembayaran->calonSiswa->nama_lengkap ?? '-' }} ({{ $pembayaran->calonSiswa->no_pendaftaran ?? '' }})</div>
            <div class="col-6"><strong>Biaya:</strong> {{ $pembayaran->biayaPendaftaran->jenis_biaya ?? '-' }} — Rp {{ number_format($pembayaran->jumlah,0,',','.') }}</div>
            <div class="col-4"><strong>Metode:</strong> {{ $pembayaran->metode_pembayaran }}</div>
            <div class="col-4"><strong>Jenis:</strong> {{ $pembayaran->jenis_pembayaran }}</div>
            <div class="col-4"><strong>Tanggal:</strong> {{ $pembayaran->tanggal_pembayaran ? \Carbon\Carbon::parse($pembayaran->tanggal_pembayaran)->format('d M Y') : '-' }}</div>
            <div class="col-12"><strong>Bukti:</strong> @if($pembayaran->bukti_pembayaran_path) <a href="{{ Storage::disk('public')->url($pembayaran->bukti_pembayaran_path) }}" target="_blank" class="text-primary">Lihat bukti</a> @else — @endif</div>
            @if($pembayaran->catatan)<div class="col-12"><strong>Catatan:</strong> {{ $pembayaran->catatan }}</div>@endif
            @if($pembayaran->keterangan_angsuran)<div class="col-12"><strong>Keterangan Angsuran:</strong> {{ $pembayaran->keterangan_angsuran }}</div>@endif
        </div>

        <hr class="my-3" />
        <form method="POST" action="{{ route('admin.pembayarans.updateStatus', $pembayaran) }}" class="d-flex gap-2 flex-wrap">
            @csrf @method('PATCH')
            <select name="status" class="form-select form-select-sm" style="width:auto">
                <option value="menunggu" @selected($pembayaran->status==='menunggu')>Menunggu</option>
                <option value="berhasil" @selected($pembayaran->status==='berhasil')>Berhasil</option>
                <option value="gagal" @selected($pembayaran->status==='gagal')>Gagal</option>
            </select>
            <button type="submit" class="btn btn-primary btn-sm">Ubah Status</button>
        </form>
    </div>
</div>
@endsection
