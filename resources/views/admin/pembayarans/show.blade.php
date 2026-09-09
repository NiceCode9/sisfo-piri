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
        <form method="POST" action="{{ route('admin.pembayarans.status', $pembayaran) }}" class="d-flex gap-2 flex-wrap">
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

{{-- Rencana Angsuran --}}
@if ($rencana)
    <div class="card-nexus mt-3">
        <div class="card-header-nexus">
            <div>
                <h5 class="card-title">Rencana Angsuran {{ $rencana->kode_angsuran }}</h5>
                <p class="card-subtitle">Total Rp {{ number_format($rencana->total_biaya,0,',','.') }} • DP Rp {{ number_format($rencana->dp_dibayar,0,',','.') }} • Sisa Rp {{ number_format($rencana->sisa_hutang,0,',','.') }}</p>
            </div>
            <div class="d-flex gap-2 align-items-center">
                <span class="badge-nexus {{ $rencana->status==='lunas'?'badge-info':($rencana->status==='batal'?'badge-neutral':'badge-purple') }}">{{ $rencana->status }}</span>
                @can('rencana-angsurans.delete')
                    @if ($rencana->status === 'aktif' && $rencana->detailAngsuran->where('status','dibayar')->isEmpty())
                        <form action="{{ route('admin.rencana.batal', $rencana) }}" method="POST" class="d-inline">
                            @csrf @method('PATCH')
                            <button type="submit" class="btn btn-nexus-outline btn-sm text-danger" data-confirm="Batalkan rencana {{ $rencana->kode_angsuran }}?">Batalkan</button>
                        </form>
                    @endif
                @endcan
            </div>
        </div>
        <div class="card-body-nexus p-0">
            <div class="table-responsive">
                <table class="table-nexus w-100" style="font-size: 13px;">
                    <thead><tr><th>Cicilan</th><th>Nominal</th><th>Denda</th><th>Jatuh Tempo</th><th>Status</th><th>Bayar</th></tr></thead>
                    <tbody>
                        @foreach ($rencana->detailAngsuran->sortBy('cicilan_ke') as $d)
                            @php $telat = $d->status !== 'dibayar' && $d->tanggal_jatuh_tempo < now()->toDateString(); @endphp
                            <tr>
                                <td>Ke-{{ $d->cicilan_ke }}</td>
                                <td>Rp {{ number_format($d->nominal_cicilan,0,',','.') }}</td>
                                <td>
                                    Rp {{ number_format($d->denda,0,',','.') }}
                                    @can('rencana-angsurans.edit')
                                        @if ($d->status !== 'dibayar' && $rencana->status === 'aktif')
                                            <form action="{{ route('admin.rencana.denda', $d) }}" method="POST" class="d-inline-flex gap-1 mt-1">
                                                @csrf @method('PATCH')
                                                <input type="number" name="denda" value="{{ $d->denda }}" min="0" class="form-control form-control-sm" style="width:110px" />
                                                <button type="submit" class="btn btn-nexus-outline btn-sm">OK</button>
                                            </form>
                                        @endif
                                    @endcan
                                </td>
                                <td>{{ \Carbon\Carbon::parse($d->tanggal_jatuh_tempo)->format('d M Y') }}</td>
                                <td>
                                    @if ($d->status === 'dibayar')
                                        <span class="badge-nexus badge-info">dibayar</span>
                                        <div style="font-size:11px;color:var(--text-muted);">Rp {{ number_format($d->total_bayar,0,',','.') }}</div>
                                    @elseif ($telat)
                                        <span class="badge-nexus badge-danger">terlambat</span>
                                    @else
                                        <span class="badge-nexus badge-neutral">belum bayar</span>
                                    @endif
                                </td>
                                <td>
                                    @can('pembayarans.create')
                                        @if ($d->status !== 'dibayar' && $rencana->status === 'aktif')
                                            <form action="{{ route('admin.pembayarans.store') }}" method="POST" enctype="multipart/form-data" class="d-flex flex-column gap-1" style="min-width:200px">
                                                @csrf
                                                <input type="hidden" name="calon_siswa_id" value="{{ $pembayaran->calon_siswa_id }}" />
                                                <input type="hidden" name="biaya_pendaftaran_id" value="{{ $pembayaran->biaya_pendaftaran_id }}" />
                                                <input type="hidden" name="detail_angsuran_id" value="{{ $d->id }}" />
                                                <input type="hidden" name="jenis_pembayaran" value="cicilan_angsuran" />
                                                <input type="hidden" name="jumlah" value="{{ $d->nominal_cicilan + $d->denda }}" />
                                                <div class="d-flex gap-1">
                                                    <select name="metode_pembayaran" class="form-select form-select-sm" required>
                                                        <option value="transfer">Transfer</option>
                                                        <option value="tunai">Tunai</option>
                                                    </select>
                                                    <input type="file" name="bukti_pembayaran_path" accept=".pdf,.jpg,.jpeg,.png" class="form-control form-control-sm" />
                                                </div>
                                                <button type="submit" class="btn btn-primary btn-sm">Bayar Rp {{ number_format($d->nominal_cicilan + $d->denda,0,',','.') }}</button>
                                            </form>
                                        @else — @endif
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@elseif ($pembayaran->biayaPendaftaran?->dapat_diangsur && $pembayaran->status === 'menunggu')
    @can('rencana-angsurans.create')
        <div class="card-nexus mt-3">
            <div class="card-header-nexus"><div><h5 class="card-title">Buat Rencana Angsuran</h5><p class="card-subtitle">DP tercatat berhasil + jadwal cicilan bulanan otomatis</p></div></div>
            <div class="card-body-nexus">
                <form method="POST" action="{{ route('admin.rencana.store', $pembayaran) }}">
                    @csrf
                    <div class="row g-3">
                        <div class="col-12 col-sm-4">
                            <label class="form-label" for="dp_dibayar">DP Dibayar (min Rp {{ number_format($pembayaran->biayaPendaftaran->min_dp ?? 0,0,',','.') }})</label>
                            <input type="number" name="dp_dibayar" id="dp_dibayar" value="{{ old('dp_dibayar', $pembayaran->biayaPendaftaran->min_dp ?? 0) }}" min="0" max="{{ $pembayaran->jumlah }}" class="form-control @error('dp_dibayar') is-invalid @enderror" required />
                            @error('dp_dibayar')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12 col-sm-4">
                            <label class="form-label" for="jumlah_cicilan">Jumlah Cicilan @if($pembayaran->biayaPendaftaran->max_cicilan)(maks {{ $pembayaran->biayaPendaftaran->max_cicilan }})@endif</label>
                            <input type="number" name="jumlah_cicilan" id="jumlah_cicilan" value="{{ old('jumlah_cicilan', $pembayaran->biayaPendaftaran->max_cicilan ?? 3) }}" min="1" class="form-control @error('jumlah_cicilan') is-invalid @enderror" required />
                            @error('jumlah_cicilan')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12 col-sm-4">
                            <label class="form-label" for="tanggal_mulai">Mulai Cicilan</label>
                            <input type="date" name="tanggal_mulai" id="tanggal_mulai" value="{{ old('tanggal_mulai', now()->toDateString()) }}" class="form-control @error('tanggal_mulai') is-invalid @enderror" required />
                            @error('tanggal_mulai')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="catatan-rencana">Catatan</label>
                            <input type="text" name="catatan" id="catatan-rencana" value="{{ old('catatan') }}" class="form-control" placeholder="Opsional" />
                        </div>
                    </div>
                    <div class="d-flex justify-content-end mt-3">
                        <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-calendar-plus"></i> Buat Angsuran</button>
                    </div>
                </form>
            </div>
        </div>
    @endcan
@endif
@endsection
