@extends('layouts.app')

@section('title', 'Dashboard Siswa')
@section('breadcrumb', 'Dashboard')

@section('content')
<div class="page-header d-flex flex-wrap align-items-start justify-content-between gap-3">
    <div>
        <h1 class="page-title">Dashboard Siswa</h1>
        <p class="page-subtitle mb-0">Halo, {{ auth()->user()->name }} — NISN {{ auth()->user()->username }}</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('login') }}" class="btn btn-nexus-outline btn-sm" onclick="event.preventDefault(); document.getElementById('logout-form').submit();"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
    </div>
</div>

@include('layouts.partials.alert')

@if (!$calon)
    <div class="card-nexus">
        <div class="card-body-nexus text-center py-5">
            <p style="color: var(--text-muted);">Belum ada data pendaftaran terkait akun ini.</p>
        </div>
    </div>
@else
    <div class="row g-3">
        <div class="col-12 col-lg-8">
            <div class="card-nexus mb-3">
                <div class="card-header-nexus">
                    <h5 class="card-title">Status Pendaftaran</h5>
                    <span class="badge-nexus {{ $calon->status_pendaftaran === 'diterima' ? 'badge-info' : ($calon->status_pendaftaran === 'ditolak' ? 'badge-danger' : 'badge-neutral') }}">{{ $calon->status_pendaftaran }}</span>
                </div>
                <div class="card-body-nexus">
                    <div class="row g-3" style="font-size: 13px;">
                        <div class="col-6"><strong>No Pendaftaran:</strong> {{ $calon->no_pendaftaran }}</div>
                        <div class="col-6"><strong>Jalur:</strong> {{ $calon->jalurPendaftaran->nama_jalur ?? '-' }}</div>
                        <div class="col-6"><strong>Tahun:</strong> {{ $calon->tahunAjaran->nama_tahun_ajaran ?? '-' }}</div>
                        <div class="col-6"><strong>Nama:</strong> {{ $calon->nama_lengkap }}</div>
                        <div class="col-12"><strong>NIK/NISN:</strong> {{ $calon->nik }} / {{ $calon->nisn }}</div>
                    </div>

                    @if ($siswa)
                        <div class="alert alert-success mt-3" style="font-size: 13px;">
                            <i class="fa-solid fa-graduation-cap"></i> Selamat! Anda telah diterima sebagai siswa. NIS: {{ $siswa->nis ?? '-' }} — Kelas akan diinfokan.
                        </div>
                    @endif

                    <hr class="my-3" />
                    <h6 style="font-size: 13px; font-weight: 600;">Riwayat Status</h6>
                    <div class="d-flex flex-column gap-2">
                        @forelse ($calon->logStatusPendaftaran->sortByDesc('created_at') as $log)
                            <div style="font-size: 12px; border-left: 2px solid var(--border-color); padding-left: 8px;">
                                <div><strong>{{ $log->status_sebelumnya ?? '—' }} → {{ $log->status_baru }}</strong> • {{ $log->created_at->format('d M Y H:i') }}</div>
                                <div style="color: var(--text-muted);">{{ $log->catatan ?? '' }}</div>
                            </div>
                        @empty
                            <span style="font-size: 12px; color: var(--text-muted);">Belum ada log.</span>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="card-nexus mb-3">
                <div class="card-header-nexus"><h5 class="card-title">Berkas</h5></div>
                <div class="card-body-nexus">
                    @if ($calon->berkasCalonSiswa)
                        @php $b = $calon->berkasCalonSiswa; @endphp
                        <div class="table-responsive">
                            <table class="table-nexus w-100" style="font-size: 13px;">
                                <thead><tr><th>Berkas</th><th>Status</th><th>File</th></tr></thead>
                                <tbody>
                                    @foreach (['ijazah_path'=>'Ijazah','kk_path'=>'KK','akta_path'=>'Akta','foto_path'=>'Foto','skl_path'=>'SKL','krm_path'=>'KRM','kip_path'=>'KIP'] as $field=>$label)
                                        <tr>
                                            <td>{{ $label }}</td>
                                            <td>
                                                @if(in_array($field, $b->berkas_perlu_perbaikan ?? []))
                                                    <span class="badge-nexus badge-danger">perlu perbaikan</span>
                                                @elseif($b->status_verifikasi)
                                                    <span class="badge-nexus badge-info">terverifikasi</span>
                                                @else
                                                    <span class="badge-nexus badge-neutral">menunggu</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($b->$field)
                                                    <a href="{{ Storage::disk('public')->url($b->$field) }}" target="_blank" class="text-primary">Lihat</a>
                                                @else — @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @if($b->alasan_penolakan)
                            <div class="alert alert-warning mt-3" style="font-size: 12px;">Alasan: {{ $b->alasan_penolakan }}</div>
                        @endif
                        @if($b->catatan_berkas)
                            <div style="font-size: 12px; color: var(--text-muted);">Catatan: {{ $b->catatan_berkas }}</div>
                        @endif
                    @else
                        <p style="font-size: 13px; color: var(--text-muted);">Belum ada berkas.</p>
                    @endif

                    @if ($calon->sertifikatPrestasis->isNotEmpty())
                        <h6 class="mt-3 mb-2" style="font-size: 13px; font-weight: 700;">Sertifikat Prestasi</h6>
                        <div class="table-responsive">
                            <table class="table-nexus w-100" style="font-size: 13px;">
                                <thead><tr><th>Nama Kejuaraan</th><th>Status</th><th>File</th></tr></thead>
                                <tbody>
                                    @foreach ($calon->sertifikatPrestasis as $s)
                                        <tr>
                                            <td>{{ $s->nama_sertifikat }}</td>
                                            <td>
                                                @if(in_array('sertifikat', ($b ?? null)?->berkas_perlu_perbaikan ?? []))
                                                    <span class="badge-nexus badge-danger">perlu perbaikan</span>
                                                @elseif(($b ?? null)?->status_verifikasi)
                                                    <span class="badge-nexus badge-info">terverifikasi</span>
                                                @else
                                                    <span class="badge-nexus badge-neutral">menunggu</span>
                                                @endif
                                            </td>
                                            <td><a href="{{ Storage::disk('public')->url($s->file_path) }}" target="_blank" class="text-primary">Lihat</a></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-4">
            <div class="card-nexus mb-3">
                <div class="card-header-nexus"><h5 class="card-title">Pembayaran</h5></div>
                <div class="card-body-nexus">
                    @if($calon->pembayaran->isEmpty())
                        <p style="font-size: 13px; color: var(--text-muted);">
                            @if($calon->status_pendaftaran === 'diterima')
                                Tagihan sedang disiapkan admin.
                            @else
                                Tagihan akan dibuat setelah status <strong>diterima</strong>.
                            @endif
                        </p>
                    @else
                        <div class="table-responsive">
                            <table class="table-nexus w-100" style="font-size: 12px;">
                                <thead><tr><th>Biaya</th><th>Jumlah</th><th>Status</th></tr></thead>
                                <tbody>
                                    @foreach($calon->pembayaran as $bayar)
                                        <tr>
                                            <td>{{ $bayar->biayaPendaftaran->jenis_biaya ?? $bayar->kode_pembayaran }}<div style="font-size:11px;color:var(--text-muted);">{{ $bayar->kode_pembayaran }}</div></td>
                                            <td>Rp {{ number_format($bayar->jumlah,0,',','.') }}</td>
                                            <td>
                                                @php $badge = $bayar->status==='berhasil'?'badge-info':($bayar->status==='gagal'?'badge-danger':'badge-neutral'); @endphp
                                                <span class="badge-nexus {{ $badge }}">{{ $bayar->status }}</span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <hr class="my-3" />
                        @foreach($calon->pembayaran->where('status','menunggu') as $bayar)
                            <form method="POST" action="{{ route('siswa.pembayarans.store') }}" enctype="multipart/form-data" class="mb-3 p-2 border rounded-3" style="border-color:var(--border-color);">
                                @csrf
                                <input type="hidden" name="pembayaran_id" value="{{ $bayar->id }}" />
                                <label class="form-label" style="font-size:12px;">Upload bukti untuk {{ $bayar->biayaPendaftaran->jenis_biaya ?? $bayar->kode_pembayaran }}</label>
                                @if($bayar->bukti_pembayaran_path)
                                    <div style="font-size:11px;">Saat ini: <a href="{{ Storage::disk('public')->url($bayar->bukti_pembayaran_path) }}" target="_blank" class="text-primary">Lihat</a></div>
                                @endif
                                <input type="file" name="bukti_pembayaran_path" accept=".pdf,.jpg,.jpeg,.png" class="form-control form-control-sm mt-1" required />
                                @error('bukti_pembayaran_path')<div class="text-danger" style="font-size:11px;">{{ $message }}</div>@enderror
                                <button type="submit" class="btn btn-primary btn-sm w-100 mt-2">Upload Bukti (menunggu verifikasi)</button>
                            </form>
                        @endforeach
                        <div style="font-size:11px;color:var(--text-muted);">Siswa upload → menunggu, admin verifikasi → berhasil/gagal. Admin upload langsung berhasil.</div>
                    @endif
                </div>
            </div>

            <div class="card-nexus">
                <div class="card-header-nexus"><h5 class="card-title">Riwayat Pendaftaran</h5></div>
                <div class="card-body-nexus">
                    @forelse($riwayat as $r)
                        <div style="font-size: 12px; padding: 6px 0; border-bottom: 1px solid var(--border-color);">
                            <div><strong>{{ $r->no_pendaftaran }}</strong> — {{ $r->status_pendaftaran }} • {{ $r->tahunAjaran->nama_tahun_ajaran ?? '' }}</div>
                        </div>
                    @empty
                        <span style="font-size: 12px; color: var(--text-muted);">-</span>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endif
@endsection
