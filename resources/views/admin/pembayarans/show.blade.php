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

@if ($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <ul class="mb-0 ps-3">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

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
    .pay-show-progress { height: 8px; border-radius: 99px; background: var(--border-color); overflow: hidden; }
    .pay-show-progress > div { height: 100%; border-radius: 99px; background: linear-gradient(90deg, var(--accent-primary), #7c6cf0); }
</style>
@endpush

{{-- Ringkasan ala kwitansi --}}
<div class="pay-show-summary mb-3">
    <div>
        <div class="pay-code">{{ $pembayaran->kode_pembayaran }} • {{ ucfirst(str_replace('_', ' ', $pembayaran->jenis_pembayaran)) }} • {{ $pembayaran->metode_pembayaran }}</div>
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
                    <dt>Biaya</dt>
                    <dd>{{ $pembayaran->biayaPendaftaran->jenis_biaya ?? '-' }}</dd>
                    <dt>Metode</dt>
                    <dd><span class="badge-nexus badge-neutral">{{ $pembayaran->metode_pembayaran }}</span></dd>
                    <dt>Jenis</dt>
                    <dd>{{ ucfirst(str_replace('_', ' ', $pembayaran->jenis_pembayaran)) }}</dd>
                    <dt>Tanggal</dt>
                    <dd>{{ $pembayaran->tanggal_pembayaran ? \Carbon\Carbon::parse($pembayaran->tanggal_pembayaran)->format('d M Y') : '-' }}</dd>
                    @if($pembayaran->catatan)
                        <dt>Catatan</dt>
                        <dd>{{ $pembayaran->catatan }}</dd>
                    @endif
                    @if($pembayaran->keterangan_angsuran)
                        <dt>Ket. Angsuran</dt>
                        <dd>{{ $pembayaran->keterangan_angsuran }}</dd>
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
                <form method="POST" action="{{ route('admin.pembayarans.status', $pembayaran) }}" class="d-flex flex-column gap-2">
                    @csrf @method('PATCH')
                    <select name="status" class="form-select form-select-sm">
                        <option value="menunggu" @selected($pembayaran->status==='menunggu')>Menunggu</option>
                        <option value="berhasil" @selected($pembayaran->status==='berhasil')>Berhasil</option>
                        <option value="gagal" @selected($pembayaran->status==='gagal')>Gagal</option>
                    </select>
                    <button type="submit" class="btn btn-primary btn-sm w-100">Ubah Status</button>
                </form>
                <div class="mt-2" style="font-size:11.5px;color:var(--text-muted);">Verifikasi cicilan yang berhasil otomatis menutup detail + rencana bila lunas.</div>
            </div>
        </div>
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
        <div class="card-body-nexus">
            @php $terbayar = $rencana->total_biaya - $rencana->sisa_hutang; $persen = $rencana->total_biaya > 0 ? round($terbayar / $rencana->total_biaya * 100) : 0; @endphp
            <div class="mb-3">
                <div class="d-flex justify-content-between mb-1" style="font-size:12px;color:var(--text-muted);">
                    <span>Terbayar Rp {{ number_format($terbayar,0,',','.') }}</span><span>{{ $persen }}%</span>
                </div>
                <div class="pay-show-progress"><div style="width: {{ min(100, $persen) }}%"></div></div>
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
                                <td>{{ $d->cicilan_ke === 0 ? 'DP' : 'Ke-'.$d->cicilan_ke }}</td>
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
                                                <input type="hidden" name="redirect_to" value="{{ route('admin.pembayarans.show', $pembayaran) }}" />
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
                            <label class="form-label" for="jumlah_cicilan">Dicicil Berapa Kali @if($pembayaran->biayaPendaftaran->max_cicilan)(maks {{ $pembayaran->biayaPendaftaran->max_cicilan }}x)@endif</label>
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
