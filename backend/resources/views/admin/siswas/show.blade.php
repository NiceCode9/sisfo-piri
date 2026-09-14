@extends('layouts.app')

@section('title', 'Nexus Admin — Detail Siswa')
@section('breadcrumb', 'Detail Siswa')

@section('content')
<div class="page-header d-flex flex-wrap align-items-start justify-content-between gap-3">
    <div>
        <h1 class="page-title">{{ $siswa->user->name ?? $siswa->calonSiswa->nama_lengkap ?? '-' }}</h1>
        <p class="page-subtitle mb-0">NIS {{ $siswa->nis ?? '-' }} • NISN {{ $siswa->nisn }} • {{ $siswa->kelas->nama_kelas ?? '-' }}</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('admin.siswas.index') }}" class="btn btn-nexus-outline btn-sm"><i class="fa-solid fa-arrow-left"></i> Kembali</a>
        <a href="{{ route('admin.siswas.kartu', $siswa) }}" class="btn btn-nexus-outline btn-sm" target="_blank"><i class="fa-solid fa-id-card"></i> Kartu Siswa</a>
        @can('siswas.edit')
            <a href="{{ route('admin.siswas.edit', $siswa) }}" class="btn btn-primary btn-sm"><i class="fa-solid fa-pencil"></i> Ubah</a>
        @endcan
    </div>
</div>

@include('layouts.partials.alert')

@php
    // Prioritas kolom siswas, fallback ke calon PPDB bila kosong.
    $ortuSiswa = [
        'nama_ayah' => $siswa->nama_ayah ?: ($siswa->calonSiswa->nama_ayah ?? '-'),
        'pekerjaan_ayah' => $siswa->pekerjaan_ayah ?: ($siswa->calonSiswa->pekerjaan_ayah ?? '-'),
        'nama_ibu' => $siswa->nama_ibu ?: ($siswa->calonSiswa->nama_ibu ?? '-'),
        'pekerjaan_ibu' => $siswa->pekerjaan_ibu ?: ($siswa->calonSiswa->pekerjaan_ibu ?? '-'),
        'no_hp_orang_tua' => $siswa->no_hp_orang_tua ?: ($siswa->calonSiswa->no_hp_orang_tua ?? '-'),
    ];
@endphp

<div class="row g-3">
    <div class="col-12 col-lg-8">
        <div class="card-nexus mb-3">
            <div class="card-header-nexus">
                <h5 class="card-title">Profil Siswa</h5>
                <span class="badge-nexus {{ $siswa->is_aktif ? 'badge-info' : 'badge-neutral' }}">{{ $siswa->is_aktif ? 'aktif' : 'nonaktif' }}</span>
            </div>
            <div class="card-body-nexus">
                <div class="row g-3" style="font-size: 13px;">
                    <div class="col-6"><strong>Username:</strong> {{ $siswa->user->username ?? '-' }}</div>
                    <div class="col-6"><strong>Kelas:</strong> {{ $siswa->kelas->nama_kelas ?? '-' }} (tingkat {{ $siswa->kelas->tingkat ?? '-' }})</div>
                    <div class="col-6"><strong>Tahun Ajaran:</strong> {{ $siswa->tahunAjaran->nama_tahun_ajaran ?? '-' }}</div>
                    <div class="col-6"><strong>Tgl Diterima:</strong> {{ $siswa->tanggal_diterima ? \Carbon\Carbon::parse($siswa->tanggal_diterima)->format('d M Y') : '-' }}</div>
                    <div class="col-6"><strong>Asal:</strong> {{ $siswa->calonSiswa ? 'PPDB ('.$siswa->calonSiswa->no_pendaftaran.')' : 'Manual' }}</div>
                </div>
            </div>
        </div>

        <div class="card-nexus mb-3">
            <div class="card-header-nexus"><h5 class="card-title">Data Orang Tua</h5></div>
            <div class="card-body-nexus">
                <div class="row g-3" style="font-size: 13px;">
                    <div class="col-6"><strong>Ayah:</strong> {{ $ortuSiswa['nama_ayah'] }} ({{ $ortuSiswa['pekerjaan_ayah'] }})</div>
                    <div class="col-6"><strong>Ibu:</strong> {{ $ortuSiswa['nama_ibu'] }} ({{ $ortuSiswa['pekerjaan_ibu'] }})</div>
                    <div class="col-6"><strong>No HP Ortu:</strong> {{ $ortuSiswa['no_hp_orang_tua'] }}</div>
                </div>
            </div>
        </div>

        <div class="card-nexus mb-3">
            <div class="card-header-nexus"><h5 class="card-title">Riwayat Kelas</h5></div>
            <div class="card-body-nexus p-0">
                <div class="table-responsive">
                    <table class="table-nexus w-100" style="font-size: 13px;">
                        <thead><tr><th>Kelas</th><th>Tahun</th><th>Status</th></tr></thead>
                        <tbody>
                            @forelse ($siswa->riwayatKelas->sortByDesc('created_at') as $r)
                                <tr>
                                    <td>{{ $r->kelas->nama_kelas ?? '-' }}</td>
                                    <td>{{ $r->tahunAjaran->nama_tahun_ajaran ?? '-' }}</td>
                                    <td><span class="badge-nexus badge-neutral">{{ $r->status }}</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-center py-3" style="color:var(--text-muted);">Belum ada riwayat.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-4">
        <div class="card-nexus mb-3">
            <div class="card-header-nexus"><h5 class="card-title">QR Absensi</h5></div>
            <div class="card-body-nexus text-center">
                @if($siswa->qr_token && class_exists(\SimpleSoftwareIO\QrCode\Facades\QrCode::class))
                    {!! \SimpleSoftwareIO\QrCode\Facades\QrCode::size(180)->generate($siswa->qr_token) !!}
                @else
                    <p style="font-size:13px;color:var(--text-muted);">Belum ada QR. Buka Kartu Siswa atau buat sekarang.</p>
                @endif
                @can('siswas.edit')
                    <form action="{{ route('admin.siswas.qr', $siswa) }}" method="POST" class="mt-2">@csrf<button type="submit" class="btn btn-nexus-outline btn-sm w-100" data-confirm="Buat ulang QR? QR lama di kartu cetak tidak berlaku lagi."><i class="fa-solid fa-rotate"></i> {{ $siswa->qr_token ? 'Generate Ulang' : 'Buat QR' }}</button></form>
                @endcan
            </div>
        </div>
        <div class="card-nexus mb-3">
            <div class="card-header-nexus"><h5 class="card-title">Tagihan</h5></div>
            <div class="card-body-nexus">
                @php $tagihan = $siswa->calonSiswa?->pembayaran ?? collect(); @endphp
                @if($tagihan->isEmpty())
                    <p style="font-size:13px;color:var(--text-muted);">Tidak ada tagihan PPDB (siswa manual/luar PPDB).</p>
                @else
                    <div class="table-responsive">
                        <table class="table-nexus w-100" style="font-size:12px;">
                            <thead><tr><th>Kode</th><th>Jumlah</th><th>Status</th></tr></thead>
                            <tbody>
                                @foreach($tagihan as $t)
                                    <tr>
                                        <td>{{ $t->kode_pembayaran }}</td>
                                        <td>Rp {{ number_format($t->jumlah,0,',','.') }}</td>
                                        <td><span class="badge-nexus badge-neutral">{{ $t->status }}</span></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <a href="{{ route('admin.calon-siswas.show', $siswa->calonSiswa) }}" class="btn btn-nexus-outline btn-sm w-100 mt-2">Kelola di Calon Siswa</a>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
