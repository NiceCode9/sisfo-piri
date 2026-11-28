@extends('layouts.app')

@section('title', 'Detail Calon Siswa - Nexus Admin')
@section('breadcrumb', 'Detail Calon Siswa')

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <style>
        /* Memakai token tema yang sama dengan halaman admin lain supaya mode gelap tetap konsisten. */
        .card-header-content { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 8px; width: 100%; }
        .section-title {
            font-size: 12px;
            font-weight: 700;
            letter-spacing: .04em;
            text-transform: uppercase;
            color: var(--text-secondary);
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 8px;
            margin-bottom: 16px;
        }
        .info-grid { display: grid; gap: 12px; }
        .info-item {
            padding: 10px 12px;
            background: var(--bg-surface-2);
            border: 1px solid var(--border-subtle);
            border-radius: var(--radius-md);
            overflow-wrap: anywhere;
        }
        .info-label { display: block; font-size: 11px; color: var(--text-secondary); margin-bottom: 2px; }
        .info-value { font-size: 13px; font-weight: 600; color: var(--text-primary); }
        .info-value.primary { font-size: 14px; }
        .contact-link { color: var(--accent-primary); text-decoration: none; }
        .contact-link:hover { text-decoration: underline; }

        .parent-card { background: var(--bg-surface-2); border: 1px solid var(--border-color); border-radius: var(--radius-md); height: 100%; }
        .parent-header { padding: 10px 14px; font-weight: 700; font-size: 13px; border-bottom: 1px solid var(--border-color); }
        .parent-info { padding: 14px; display: grid; gap: 12px; }
        .parent-info .info-item { background: var(--bg-surface); }

        .document-item { display: flex; align-items: center; gap: 12px; padding: 12px 20px; color: var(--text-primary); text-decoration: none; border-bottom: 1px solid var(--border-subtle); }
        .document-item:last-child { border-bottom: 0; }
        .document-item:hover { background: var(--bg-surface-2); }
        .document-icon { width: 34px; height: 34px; flex: 0 0 34px; display: grid; place-items: center; border-radius: var(--radius-sm); background: var(--accent-soft); color: var(--accent-primary); }
        .document-info { min-width: 0; flex: 1; }
        .document-name { font-weight: 600; font-size: 13px; }
        .document-desc { font-size: 12px; color: var(--text-secondary); }
        .document-action { color: var(--accent-primary); }

        .payment-item { display: grid; grid-template-columns: auto minmax(0,1fr) auto auto; gap: 16px; align-items: center; padding: 14px 20px; border-bottom: 1px solid var(--border-subtle); }
        .payment-item:last-child { border-bottom: 0; }
        .payment-date { text-align: center; }
        .date-main { font-weight: 600; font-size: 12px; }
        .date-code { font-size: 11px; color: var(--text-secondary); }
        .payment-type { font-weight: 600; font-size: 13px; }
        .payment-method, .payment-note { font-size: 12px; color: var(--text-secondary); }
        .payment-amount { text-align: right; display: grid; gap: 4px; justify-items: end; }
        .amount { font-weight: 700; color: var(--text-primary); }

        .payment-summary { padding: 16px; margin-bottom: 16px; background: var(--bg-surface-2); border: 1px solid var(--border-color); border-radius: var(--radius-md); }
        .summary-content { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 12px; }
        .summary-title { font-weight: 700; display: flex; align-items: center; gap: 8px; }
        .summary-amount { font-size: 22px; font-weight: 800; }
        .summary-detail { font-size: 12px; color: var(--text-secondary); }

        .installment-info { padding: 16px; margin-bottom: 16px; background: var(--bg-surface-2); border: 1px solid var(--border-color); border-radius: var(--radius-md); }
        .installment-title, .installment-list-title { font-weight: 700; font-size: 13px; margin-bottom: 12px; }
        .installment-item { text-align: center; padding: 12px; background: var(--bg-surface); border: 1px solid var(--border-subtle); border-radius: var(--radius-sm); }
        .installment-item label { display: block; font-size: 11px; color: var(--text-secondary); }
        .installment-item .value { font-weight: 700; color: var(--text-primary); }
        .installment-list { border: 1px solid var(--border-color); border-radius: var(--radius-sm); overflow: hidden; }
        .installment-row { display: grid; grid-template-columns: auto minmax(0,1fr) auto auto; gap: 12px; align-items: center; padding: 10px 14px; border-bottom: 1px solid var(--border-subtle); }
        .installment-row:last-child { border-bottom: 0; }
        .installment-number { width: 28px; height: 28px; border-radius: var(--radius-sm); background: var(--accent-soft); color: var(--accent-primary); display: grid; place-items: center; font-weight: 700; font-size: 12px; }
        .installment-date { font-size: 12px; color: var(--text-secondary); }
        .installment-amount { font-weight: 600; }

        .unpaid-title { font-weight: 700; color: var(--text-primary); margin: 20px 0 12px; }
        .unpaid-fees { display: grid; gap: 12px; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); }
        .fee-item { padding: 14px; background: var(--bg-surface-2); border: 1px solid var(--border-color); border-radius: var(--radius-md); display: flex; flex-direction: column; gap: 12px; }
        .fee-header { display: flex; flex-wrap: wrap; align-items: flex-start; justify-content: space-between; gap: 8px; }
        .fee-name { margin: 0; font-size: 13px; font-weight: 700; }
        .fee-badges { display: flex; flex-wrap: wrap; gap: 4px; }
        .fee-amount { font-size: 18px; font-weight: 700; }
        .fee-desc { font-size: 12px; color: var(--text-secondary); }
        .fee-action { display: flex; flex-wrap: wrap; justify-content: flex-end; gap: 8px; }

        .empty-state { text-align: center; padding: 28px 16px; }
        .empty-icon { font-size: 28px; color: var(--text-secondary); margin-bottom: 8px; }
        .empty-title { font-weight: 700; }
        .empty-text { color: var(--text-secondary); margin: 0; }

        @media (max-width: 767.98px) {
            .payment-item, .installment-row { grid-template-columns: 1fr; text-align: left; gap: 6px; }
            .payment-amount { text-align: left; justify-items: start; }
        }
    </style>
@endpush

@section('content')
    @php
        $statusBadge = [
            'diterima' => 'success',
            'berhasil' => 'success',
            'dibayar' => 'success',
            'ditolak' => 'danger',
            'gagal' => 'danger',
            'menunggu' => 'warning',
            'daftar_ulang' => 'warning',
            'pending' => 'warning',
        ];
    @endphp

    <div class="page-header d-flex flex-wrap align-items-start justify-content-between gap-3">
        <div>
            <h1 class="page-title">Detail Pendaftaran</h1>
            <p class="page-subtitle mb-0">{{ $calon->nama_lengkap }} · {{ $calon->no_pendaftaran }}</p>
        </div>
        <div class="d-flex gap-2 flex-wrap align-items-center">
            <span class="badge-nexus badge-{{ $statusBadge[$calon->status_pendaftaran] ?? 'neutral' }}">
                {{ ucfirst(str_replace('_', ' ', $calon->status_pendaftaran)) }}
            </span>
            <a href="{{ route('admin.calon-siswas.index') }}" class="btn btn-nexus-outline btn-sm">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
            @can('calon-siswas.edit')
                <a href="{{ route('admin.calon-siswas.edit', $calon) }}" class="btn btn-nexus-outline btn-sm">
                    <i class="bi bi-pencil"></i> Ubah
                </a>
            @endcan
            <button type="button" class="btn btn-nexus-outline btn-sm" onclick="window.print()">
                <i class="bi bi-printer"></i> Print
            </button>
        </div>
    </div>

    @include('layouts.partials.alert', ['errorDitampilkan' => \App\Models\BerkasCalonSiswa::UPLOADABLE])

    <div class="row g-4">
        <div class="col-12 col-xl-8">
            <!-- Student Data Card -->
            <div class="card-nexus">
                <div class="card-header-nexus">
                    <div class="card-header-content">
                        <h5 class="card-title">
                            <i class="bi bi-person-fill me-2"></i>Data Pendaftar
                        </h5>
                    </div>
                </div>
                <div class="card-body-nexus">
                    <div class="row g-4">
                        <!-- Personal Info -->
                        <div class="col-md-6">
                            <div class="info-section">
                                <h6 class="section-title">
                                    <i class="bi bi-person-vcard me-2"></i>Informasi Pribadi
                                </h6>
                                <div class="info-grid">
                                    <div class="info-item">
                                        <label class="info-label">No. Pendaftaran</label>
                                        <div class="info-value">{{ $calon->no_pendaftaran }}</div>
                                    </div>
                                    <div class="info-item">
                                        <label class="info-label">NIK</label>
                                        <div class="info-value">{{ $calon->nik }}</div>
                                    </div>
                                    <div class="info-item">
                                        <label class="info-label">NISN</label>
                                        <div class="info-value">{{ $calon->nisn ?? '-' }}</div>
                                    </div>
                                    <div class="info-item">
                                        <label class="info-label">Nama Lengkap</label>
                                        <div class="info-value primary">{{ $calon->nama_lengkap }}</div>
                                    </div>
                                    <div class="info-item">
                                        <label class="info-label">Jenis Kelamin</label>
                                        <div class="info-value">
                                            <i class="bi {{ $calon->jenis_kelamin === 'L' ? 'bi-gender-male text-primary' : 'bi-gender-female text-danger' }} me-2"></i>
                                            {{ $calon->jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan' }}
                                        </div>
                                    </div>
                                    <div class="info-item">
                                        <label class="info-label">Tempat, Tanggal Lahir</label>
                                        <div class="info-value">
                                            <i class="bi bi-calendar-event text-info me-2"></i>
                                            {{ $calon->tempat_lahir }},
                                            {{ \Carbon\Carbon::parse($calon->tanggal_lahir)->format('d F Y') }}
                                        </div>
                                    </div>
                                    <div class="info-item">
                                        <label class="info-label">Agama</label>
                                        <div class="info-value">{{ $calon->agama }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Contact & Education Info -->
                        <div class="col-md-6">
                            <div class="info-section">
                                <h6 class="section-title">
                                    <i class="bi bi-geo-alt me-2"></i>Kontak & Pendidikan
                                </h6>
                                <div class="info-grid">
                                    <div class="info-item">
                                        <label class="info-label">Alamat</label>
                                        <div class="info-value">{{ $calon->alamat }}</div>
                                    </div>
                                    <div class="info-item">
                                        <label class="info-label">No. HP</label>
                                        <div class="info-value">
                                            <i class="bi bi-telephone text-success me-2"></i>
                                            <a href="tel:{{ $calon->no_hp }}" class="contact-link">{{ $calon->no_hp ?? '-' }}</a>
                                        </div>
                                    </div>
                                    <div class="info-item">
                                        <label class="info-label">Email</label>
                                        <div class="info-value">
                                            <i class="bi bi-envelope text-info me-2"></i>
                                            <a href="mailto:{{ $calon->email }}" class="contact-link">{{ $calon->email ?? '-' }}</a>
                                        </div>
                                    </div>
                                    <div class="info-item">
                                        <label class="info-label">Asal Sekolah</label>
                                        <div class="info-value">
                                            <i class="bi bi-building text-warning me-2"></i>
                                            {{ $calon->asal_sekolah ?? '-' }}
                                        </div>
                                    </div>
                                    <div class="info-item">
                                        <label class="info-label">Tahun Ajaran</label>
                                        <div class="info-value">{{ $calon->tahunAjaran->nama_tahun_ajaran ?? '-' }}</div>
                                    </div>
                                    <div class="info-item">
                                        <label class="info-label">Jalur Pendaftaran</label>
                                        <div class="info-value">
                                            @if ($calon->jalurPendaftaran)
                                                {{ $calon->jalurPendaftaran->nama_jalur }}
                                                <span class="badge-nexus badge-{{ $calon->jalurPendaftaran->aktif ? 'success' : 'danger' }}">
                                                    {{ $calon->jalurPendaftaran->aktif ? 'Aktif' : 'Tidak Aktif' }}
                                                </span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Parent Data Card -->
            <div class="card-nexus">
                <div class="card-header-nexus">
                    <h5 class="card-title">
                        <i class="bi bi-people-fill me-2"></i>Data Orang Tua
                    </h5>
                </div>
                <div class="card-body-nexus">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="parent-card parent-father">
                                <div class="parent-header">
                                    <i class="bi bi-person-fill-gear me-2"></i>Data Ayah
                                </div>
                                <div class="parent-info">
                                    <div class="info-item">
                                        <label class="info-label">Nama Ayah</label>
                                        <div class="info-value">{{ $calon->nama_ayah ?? '-' }}</div>
                                    </div>
                                    <div class="info-item">
                                        <label class="info-label">Pekerjaan</label>
                                        <div class="info-value">
                                            <i class="bi bi-briefcase text-primary me-2"></i>
                                            {{ $calon->pekerjaan_ayah ?? '-' }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="parent-card parent-mother">
                                <div class="parent-header">
                                    <i class="bi bi-person-heart me-2"></i>Data Ibu
                                </div>
                                <div class="parent-info">
                                    <div class="info-item">
                                        <label class="info-label">Nama Ibu</label>
                                        <div class="info-value">{{ $calon->nama_ibu ?? '-' }}</div>
                                    </div>
                                    <div class="info-item">
                                        <label class="info-label">Pekerjaan</label>
                                        <div class="info-value">
                                            <i class="bi bi-briefcase text-danger me-2"></i>
                                            {{ $calon->pekerjaan_ibu ?? '-' }}
                                        </div>
                                    </div>
                                    <div class="info-item">
                                        <label class="info-label">No. HP Orang Tua</label>
                                        <div class="info-value">
                                            <i class="bi bi-telephone text-success me-2"></i>
                                            <a href="tel:{{ $calon->no_hp_orang_tua }}" class="contact-link">{{ $calon->no_hp_orang_tua ?? '-' }}</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Status & Log Card -->
            <div class="card-nexus">
                <div class="card-header-nexus">
                    <h5 class="card-title">
                        <i class="bi bi-clipboard-data me-2"></i>Status Pendaftaran
                    </h5>
                </div>
                <div class="card-body-nexus">
                    @can('calon-siswas.edit')
                        <form method="POST" action="{{ route('admin.calon-siswas.status', $calon) }}" id="form-status">
                            @csrf @method('PATCH')
                            <div class="row g-3">
                                <div class="col-12">
                                    <label for="status_pendaftaran" class="form-label">
                                        <i class="bi bi-clipboard-data text-primary me-1"></i>Status Pendaftaran
                                    </label>
                                    <select class="form-select" id="status_pendaftaran" name="status" required>
                                        <option value="">Pilih keputusan…</option>
                                        <option value="menunggu" @selected($calon->status_pendaftaran === 'menunggu')>Menunggu</option>
                                        <option value="diterima" @selected($calon->status_pendaftaran === 'diterima')>Diterima</option>
                                        <option value="ditolak" @selected($calon->status_pendaftaran === 'ditolak')>Ditolak</option>
                                        <option value="daftar_ulang" @selected($calon->status_pendaftaran === 'daftar_ulang')>Daftar Ulang</option>
                                    </select>
                                {{-- Server menolak `diterima` bila berkas belum diverifikasi
                                     atau masih ada yang perlu perbaikan (lihat
                                     CalonSiswaController::berkasBelumSiap()). --}}
                                @php $berkasSiap = $calon->berkasCalonSiswa; @endphp
                                @if ($calon->status_pendaftaran !== 'diterima' && $berkasSiap)
                                    @if ($berkasSiap->perluPerbaikan())
                                        <div class="alert alert-warning d-flex align-items-start gap-2 mb-0" role="alert">
                                            <i class="bi bi-exclamation-triangle-fill mt-1"></i>
                                            <div>
                                                <strong>Belum bisa diterima.</strong>
                                                Masih ada berkas yang perlu perbaikan:
                                                {{ implode(', ', $berkasSiap->labelYangPerluPerbaikan()) }}.
                                                Verifikasi berkas terlebih dahulu.
                                            </div>
                                        </div>
                                    @elseif (! $berkasSiap->status_verifikasi)
                                        <div class="alert alert-info d-flex align-items-start gap-2 mb-0" role="alert">
                                            <i class="bi bi-info-circle-fill mt-1"></i>
                                            <div><strong>Berkas belum diverifikasi.</strong> Verifikasi berkas terlebih dahulu sebelum menerima calon.</div>
                                        </div>
                                    @endif
                                @endif
                                </div>
                                <div class="col-12">
                                    <label for="catatan_status" class="form-label">
                                        <i class="bi bi-chat-text text-info me-1"></i>Catatan (opsional)
                                    </label>
                                    <textarea class="form-control" id="catatan_status" name="catatan" rows="3" placeholder="Berikan catatan untuk keputusan ini…"></textarea>
                                </div>
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary btn-lg w-100">
                                        <i class="bi bi-check-circle me-2"></i>Update Status Pendaftaran
                                    </button>
                                </div>
                            </div>
                        </form>
                    @else
                        <p class="small text-secondary">{{ ucfirst(str_replace('_', ' ', $calon->status_pendaftaran)) }}</p>
                    @endcan

                    <h6 class="mt-4 mb-3 fw-semibold">Riwayat Status</h6>
                    <div class="d-flex flex-column gap-2">
                        @forelse ($calon->logStatusPendaftaran->sortByDesc('created_at') as $log)
                            <div class="small ps-3">
                                <div><strong>{{ $log->status_sebelumnya ?? '-' }} ke {{ $log->status_baru }}</strong> · {{ $log->created_at->format('d M Y H:i') }}</div>
                                <div class="small text-secondary">oleh {{ $log->user->name ?? 'sistem' }} @if ($log->catatan) · {{ $log->catatan }} @endif</div>
                            </div>
                        @empty
                            <span class="small text-secondary">Belum ada log.</span>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-4">
            <!-- Documents Card -->
            <div class="card-nexus">
                <div class="card-header-nexus">
                    <h5 class="card-title">
                        <i class="bi bi-folder2-open me-2"></i>Berkas Pendaftaran
                    </h5>
                </div>
                <div class="card-body-nexus p-0">
                    @if ($calon->berkasCalonSiswa)
                        @php $b = $calon->berkasCalonSiswa; @endphp
                        <div class="document-list">
                            @foreach (['foto_path' => ['Foto 3x4', 'bi-camera-fill'], 'ijazah_path' => ['Ijazah/STTB', 'bi-award-fill'], 'kk_path' => ['KK Asli', 'bi-people-fill'], 'akta_path' => ['Akta Asli', 'bi-file-earmark-text-fill'], 'skl_path' => ['SKL Asli', 'bi-mortarboard-fill'], 'krm_path' => ['Opsional', 'bi-card-text-fill'], 'kip_path' => ['Opsional', 'bi-wallet-fill']] as $field => [$desc, $icon])
                                @if ($b->$field)
                                    <a href="{{ route('dokumen.berkas', [$b, $field]) }}" target="_blank" class="document-item">
                                        <div class="document-icon">
                                            <i class="bi {{ $icon }}"></i>
                                        </div>
                                        <div class="document-info">
                                            <div class="document-name">{{ \App\Models\BerkasCalonSiswa::LABELS[$field] ?? $field }}</div>
                                            <div class="document-desc">{{ $desc }}</div>
                                        </div>
                                        <div class="document-action">
                                            <i class="bi bi-eye-fill"></i>
                                        </div>
                                    </a>
                                @endif
                            @endforeach
                            @foreach ($calon->sertifikatPrestasis as $s)
                                <a href="{{ route('dokumen.sertifikat', $s) }}" target="_blank" class="document-item">
                                    <div class="document-icon bg-warning">
                                        <i class="bi bi-trophy-fill"></i>
                                    </div>
                                    <div class="document-info">
                                        <div class="document-name">{{ $s->nama_sertifikat }}</div>
                                        <div class="document-desc">Sertifikat Prestasi</div>
                                    </div>
                                    <div class="document-action">
                                        <i class="bi bi-eye-fill"></i>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                        <div class="px-4 py-3 small text-secondary border-top">
                            Verifikasi: {{ $b->status_verifikasi ? 'Terverifikasi' : 'Belum' }}
                            @if ($b->alasan_penolakan) · Alasan: {{ $b->alasan_penolakan }} @endif
                        </div>
                    @else
                        <div class="empty-state">
                            <i class="bi bi-folder2-open empty-icon"></i>
                            <p class="empty-text">Belum ada berkas yang diupload</p>
                        </div>
                    @endif

                    @can('berkas-calon-siswas.edit')
                        <div class="p-4 border-top">
                            <form method="POST" action="{{ route('admin.calon-siswas.berkas', $calon) }}" class="d-flex flex-column gap-3" enctype="multipart/form-data">
                                @csrf @method('PATCH')
                                <div class="form-check">
                                    <input type="hidden" name="status_verifikasi" value="0" />
                                    <input type="checkbox" name="status_verifikasi" value="1" id="status_verifikasi" class="form-check-input" @checked(old('status_verifikasi', $calon->berkasCalonSiswa->status_verifikasi ?? false)) />
                                    <label class="form-check-label" for="status_verifikasi">Terverifikasi</label>
                                </div>
                                <div>
                                    <div style="font-weight:600;font-size:13px;">Ganti Berkas (kosongkan bila tidak diubah)</div>
                                    <div class="small text-secondary">PDF 5MB, Foto JPG/PNG 2MB. File lama dihapus bila diganti.</div>
                                </div>
                                <div class="row g-2">
                                    @foreach (\App\Models\BerkasCalonSiswa::UPLOADABLE as $fld)
                                        <div class="col-12 col-sm-6">
                                            <label class="form-label small" for="berkas-{{ $fld }}">{{ \App\Models\BerkasCalonSiswa::LABELS[$fld] ?? $fld }}</label>
                                            <input type="file" name="{{ $fld }}" id="berkas-{{ $fld }}" class="form-control form-control-sm @error($fld) is-invalid @enderror" />
                                            @error($fld)<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                        </div>
                                    @endforeach
                                </div>
                                <div>
                                    <label class="form-label" style="font-size: 12px;">Berkas perlu perbaikan</label>
                                    <div class="d-flex flex-wrap gap-2">
                            @foreach (\App\Models\BerkasCalonSiswa::PERLU_PERBAIKAN as $f)
                                <div class="form-check">
                                    <input type="checkbox" name="berkas_perlu_perbaikan[]" value="{{ $f }}" id="perlu-{{ $f }}" class="form-check-input" @checked(in_array($f, old('berkas_perlu_perbaikan', $calon->berkasCalonSiswa->berkas_perlu_perbaikan ?? []))) />
                                    <label class="form-check-label" for="perlu-{{ $f }}">{{ \App\Models\BerkasCalonSiswa::LABELS[$f] ?? $f }}</label>
                                </div>
                            @endforeach
                                    </div>
                                </div>
                                <div class="form-floating">
                                    <textarea name="alasan_penolakan" id="alasan_penolakan" class="form-control" placeholder="Alasan" style="height: 70px">{{ old('alasan_penolakan', $calon->berkasCalonSiswa->alasan_penolakan ?? '') }}</textarea>
                                    <label for="alasan_penolakan">Alasan Penolakan</label>
                                </div>
                                <div class="form-floating">
                                    <textarea name="catatan_berkas" id="catatan_berkas" class="form-control" placeholder="Catatan" style="height: 70px">{{ old('catatan_berkas', $calon->berkasCalonSiswa->catatan_berkas ?? '') }}</textarea>
                                    <label for="catatan_berkas">Catatan Berkas</label>
                                </div>
                                <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-save me-1"></i>Simpan Verifikasi</button>
                            </form>
                        </div>
                    @endcan
                </div>
            </div>

            <!-- Payment History Card -->
            <div class="card-nexus">
                <div class="card-header-nexus">
                    <h5 class="card-title">
                        <i class="bi bi-clock-history me-2"></i>Riwayat Pembayaran
                    </h5>
                </div>
                <div class="card-body-nexus p-0">
                    <div class="table-responsive">
                        @if ($calon->pembayaran->count() > 0)
                            <div class="payment-history">
                                @foreach ($calon->pembayaran as $pembayaran)
                                    <div class="payment-item">
                                        <div class="payment-date">
                                            <div class="date-main">
                                                {{ $pembayaran->tanggal_pembayaran ? \Carbon\Carbon::parse($pembayaran->tanggal_pembayaran)->format('d/m/Y') : '-' }}
                                            </div>
                                            <div class="date-code">{{ $pembayaran->kode_pembayaran }}</div>
                                        </div>
                                        <div class="payment-details">
                                            <div class="payment-type">{{ $pembayaran->biayaPendaftaran->jenis_biaya ?? '-' }}</div>
                                            <div class="payment-method">{{ ucfirst(str_replace('_', ' ', $pembayaran->jenis_pembayaran)) }}</div>
                                            @if ($pembayaran->keterangan_angsuran)
                                                <div class="payment-note">{{ $pembayaran->keterangan_angsuran }}</div>
                                            @endif
                                        </div>
                                        <div class="payment-amount">
                                            <div class="amount">Rp {{ number_format($pembayaran->jumlah, 0, ',', '.') }}</div>
                                            <div class="badge-nexus badge-{{ $statusBadge[$pembayaran->status] ?? 'neutral' }}">
                                                <i class="bi {{ $pembayaran->status === 'berhasil' ? 'bi-check-circle' : ($pembayaran->status === 'menunggu' ? 'bi-clock' : 'bi-x-circle') }}"></i>
                                                {{ ucfirst($pembayaran->status) }}
                                            </div>
                                        </div>
                                        <div class="payment-proof">
                                            @if ($pembayaran->bukti_pembayaran_path)
                                                <a href="{{ route('dokumen.pembayaran', $pembayaran) }}" target="_blank" class="btn btn-sm btn-nexus-outline" aria-label="Lihat bukti pembayaran {{ $pembayaran->kode_pembayaran }}">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="empty-state">
                                <i class="bi bi-receipt empty-icon"></i>
                                <p class="empty-text">Belum ada riwayat pembayaran</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Calon yang sudah ditolak tidak boleh ditagih: seluruh aksi tambah
                 pembayaran (dan pembayaran lainnya) disembunyikan. Controller juga
                 menolak aksi ini bila request dikirim langsung. --}}
            @php $bolehDitagih = $calon->status_pendaftaran !== 'ditolak'; @endphp

            @unless ($bolehDitagih)
                <div class="alert alert-warning d-flex align-items-center gap-2" role="alert">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <div>
                        <strong>Calon ditolak.</strong> Penagihan dinonaktifkan untuk calon ini.
                        Tagihan yang masih <em>menunggu</em> otomatis dibatalkan saat status ditolak.
                    </div>
                </div>
            @endunless

            <!-- Fee Details Card -->
            <div class="card-nexus">
                <div class="card-header-nexus">
                    <div class="card-header-content">
                        <h5 class="card-title">
                            <i class="bi bi-currency-dollar me-2"></i>Rincian Biaya
                        </h5>
                        @if (auth()->user()?->can('pembayarans.create') && $bolehDitagih)
                            <button type="button" class="btn btn-nexus-outline btn-sm" data-bs-toggle="modal" data-bs-target="#modalPembayaran">
                                <i class="bi bi-plus-circle me-1"></i>Tambah
                            </button>
                        @endif
                    </div>
                </div>
                <div class="card-body-nexus">
                    @php
                        // Satu sumber kebenaran: App\Support\TagihanCalon.
                        // Definisi "lunas" tidak lagi dihitung terpisah di sini,
                        // sehingga halaman ini tidak bisa berbeda jawaban dengan
                        // pemilih pembayaran.
                        $tagihan = $calon->tagihan();
                        $biayaBelumLunas = array_values(array_filter(
                            $tagihan->rincian(),
                            fn (array $b) => ! $b['lunas'],
                        ));

                        $totalWajib = $tagihan->totalWajib();
                        $terbayarWajib = $tagihan->terbayarWajib();
                        $totalBelumBayar = $tagihan->sisaWajib();
                    @endphp

                    <!-- Payment Summary -->
                    <div class="payment-summary payment-summary-{{ $totalBelumBayar > 0 ? 'pending' : 'complete' }}">
                        <div class="summary-content">
                            <div class="summary-info">
                                <div class="summary-title">
                                    <i class="bi {{ $totalBelumBayar > 0 ? 'bi-exclamation-triangle' : 'bi-check-circle' }}"></i>
                                    {{ $totalBelumBayar > 0 ? 'Sisa Pembayaran' : 'Lunas' }}
                                </div>
                                <div class="summary-amount">Rp {{ number_format($totalBelumBayar, 0, ',', '.') }}</div>
                                <div class="summary-detail">
                                    Total biaya wajib: Rp {{ number_format($totalWajib, 0, ',', '.') }} ·
                                    Terbayar: Rp {{ number_format($terbayarWajib, 0, ',', '.') }}
                                </div>
                            </div>
                            @if ($totalBelumBayar > 0)
                                @if (auth()->user()?->can('pembayarans.create') && $bolehDitagih)
                                    <button type="button" class="btn btn-warning btn-lg" data-bs-toggle="modal" data-bs-target="#modalPembayaran">
                                        <i class="bi bi-credit-card me-2"></i>Bayar
                                    </button>
                                @endif
                            @endif
                        </div>
                    </div>

                    <!-- Installment Info -->
                    @foreach ($calon->rencanaAngsuran as $rencana)
                        <div class="installment-info">
                            <h6 class="installment-title">
                                <i class="bi bi-calendar-check me-2"></i>Angsuran {{ $rencana->kode_angsuran }} ({{ $rencana->status }})
                            </h6>
                            <div class="row g-3">
                                <div class="col-6">
                                    <div class="installment-item">
                                        <label>Total Biaya</label>
                                        <div class="value">Rp {{ number_format($rencana->total_biaya, 0, ',', '.') }}</div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="installment-item">
                                        <label>DP Dibayar</label>
                                        <div class="value">Rp {{ number_format($rencana->dp_dibayar, 0, ',', '.') }}</div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="installment-item">
                                        <label>Sisa Hutang</label>
                                        <div class="value">Rp {{ number_format($rencana->sisa_hutang, 0, ',', '.') }}</div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="installment-item">
                                        <label>Jumlah Cicilan</label>
                                        <div class="value">{{ $rencana->jumlah_cicilan }}x</div>
                                    </div>
                                </div>
                            </div>

                            <h6 class="installment-list-title">
                                <i class="bi bi-list-ol me-2"></i>Daftar Cicilan
                            </h6>
                            <div class="installment-list">
                                <div class="table-responsive">
                                    @foreach ($rencana->detailAngsuran->sortBy('cicilan_ke') as $angsuran)
                                        <div class="installment-row">
                                            <div class="installment-number">{{ $angsuran->cicilan_ke === 0 ? 'DP' : $angsuran->cicilan_ke }}</div>
                                            <div class="installment-details">
                                                <div class="installment-date">
                                                    {{ \Carbon\Carbon::parse($angsuran->tanggal_jatuh_tempo)->format('d/m/Y') }}
                                                </div>
                                                <div class="installment-amount">Rp {{ number_format($angsuran->nominal_cicilan, 0, ',', '.') }}</div>
                                            </div>
                                            <div class="installment-status">
                                                <span class="badge-nexus badge-{{ $statusBadge[$angsuran->status] ?? 'neutral' }}">
                                                    {{ ucfirst(str_replace('_', ' ', $angsuran->status)) }}
                                                </span>
                                            </div>
                                            <div class="installment-action">
                                                @if (auth()->user()?->can('pembayarans.create') && $bolehDitagih)
                                                    @if ($angsuran->status !== 'dibayar' && $rencana->status === 'aktif')
                                                        <button type="button" class="btn btn-sm btn-primary"
                                                            onclick="setCicilan('{{ $angsuran->id }}', '{{ $rencana->biaya_pendaftaran_id }}', '{{ $angsuran->nominal_cicilan + $angsuran->denda }}', 'Cicilan ke-{{ $angsuran->cicilan_ke }} Rp {{ number_format($angsuran->nominal_cicilan + $angsuran->denda, 0, ',', '.') }}')"
                                                            data-bs-toggle="modal" data-bs-target="#modalBayarCicilan">
                                                            Bayar
                                                        </button>
                                                    @endif
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endforeach

                    @if (count($biayaBelumLunas) > 0)
                        <h6 class="unpaid-title">
                            <i class="bi bi-list-ul me-2"></i>Biaya yang Belum Dibayar
                        </h6>
                        <div class="unpaid-fees">
                            @foreach ($biayaBelumLunas as $biaya)
                                <div class="fee-item">
                                    <div class="fee-info">
                                        <div class="fee-header">
                                            <h6 class="fee-name">{{ $biaya['jenis'] }}</h6>
                                            <div class="fee-badges">
                                                @if ($biaya['wajib'])
                                                    <span class="badge-nexus badge-danger">Wajib</span>
                                                @endif
                                                @if ($biaya['dapat_diangsur'])
                                                    <span class="badge-nexus badge-info">Dapat Diangsur</span>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="fee-amount">{{ $biaya['mata_uang'] }} {{ number_format($biaya['sisa'], 0, ',', '.') }}</div>
                                        @if ($biaya['keterangan'])
                                            <div class="fee-desc">{{ $biaya['keterangan'] }}</div>
                                        @endif
                                        @if ($biaya['terbayar'] > 0)
                                            <div class="fee-desc">Terbayar: {{ $biaya['mata_uang'] }} {{ number_format($biaya['terbayar'], 0, ',', '.') }}</div>
                                        @endif
                                    </div>
                                    <div class="fee-action">
                                        @if (auth()->user()?->can('pembayarans.create') && $bolehDitagih)
                                            @if (empty($biaya['rencana_aktif']))
                                                <button type="button" class="btn btn-primary"
                                                    onclick="setBiayaId('{{ $biaya['id'] }}', '{{ $biaya['sisa'] }}', '{{ $biaya['mata_uang'] }}', '{{ $biaya['dapat_diangsur'] ? 1 : 0 }}')"
                                                    data-bs-toggle="modal" data-bs-target="#modalPembayaran">
                                                    <i class="bi bi-credit-card me-1"></i>Bayar
                                                </button>
                                            @else
                                                <span class="text-muted" style="font-size:11px;">Dibayar via cicilan di bawah</span>
                                            @endif
                                            @if ($biaya['dapat_diangsur'] && $biaya['tagihan_id'])
                                                <a href="{{ route('admin.pembayarans.show', $biaya['tagihan_id']) }}" class="btn btn-nexus-outline">
                                                    <i class="bi bi-calendar-range me-1"></i>Angsuran
                                                </a>
                                            @endif
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="empty-state">
                            <i class="bi bi-check-circle-fill empty-icon text-success"></i>
                            <h5 class="empty-title">Pembayaran Lunas!</h5>
                            <p class="empty-text">Semua biaya pendaftaran sudah dibayar</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Pembayaran Lainnya -->
            <div class="card-nexus mt-4">
                <div class="card-header-nexus">
                    <h5 class="card-title">
                        <i class="bi bi-receipt me-2"></i>Pembayaran Lainnya
                    </h5>
                    <div class="d-flex gap-2">
                        <a href="{{ route('admin.pembayaran-lainnyas.index', ['search' => $calon->no_pendaftaran]) }}" class="btn btn-nexus-outline btn-sm">
                            <i class="bi bi-list-ul me-1"></i>Lihat Semua
                        </a>
                        @if (auth()->user()?->can('pembayaran-lainnyas.create') && $bolehDitagih)
                            <button type="button" class="btn btn-nexus-outline btn-sm" data-bs-toggle="modal" data-bs-target="#modalPembayaranLainnya">
                                <i class="bi bi-plus-circle me-1"></i>Tambah
                            </button>
                        @endif
                    </div>
                </div>
                <div class="card-body-nexus">
                    @if ($calon->pembayaranLainnya->isNotEmpty())
                        <div class="table-responsive">
                            <table class="table-nexus w-100" style="font-size: 13px;">
                                <thead><tr><th>Kode</th><th>Nama Biaya</th><th>Jumlah</th><th>Status</th><th>Bukti</th><th>Aksi</th></tr></thead>
                                <tbody>
                                    @foreach ($calon->pembayaranLainnya as $lain)
                                        <tr>
                                            <td style="font-size:12px;">{{ $lain->kode_pembayaran }}</td>
                                            <td>{{ $lain->nama_biaya }}</td>
                                            <td>Rp {{ number_format($lain->jumlah, 0, ',', '.') }}</td>
                                            <td>
                                                @php $badgeLain = $lain->status === 'berhasil' ? 'badge-info' : ($lain->status === 'gagal' ? 'badge-danger' : 'badge-neutral'); @endphp
                                                <span class="badge-nexus {{ $badgeLain }}">{{ $lain->status }}</span>
                                            </td>
                                            <td>
                                                @if($lain->bukti_pembayaran_path)
                                                    <a href="{{ route('dokumen.pembayaran-lainnya', $lain) }}" target="_blank" class="text-primary">Lihat</a>
                                                @else - @endif
                                            </td>
                                            <td>
                                                <div class="d-flex gap-1">
                                                    <a href="{{ route('admin.pembayaran-lainnyas.show', $lain) }}" class="btn-icon btn btn-nexus-outline btn-sm" aria-label="Lihat pembayaran {{ $lain->kode_pembayaran }}"><i class="fa-solid fa-eye"></i></a>
                                                    @if($lain->status !== 'berhasil')
                                                        <form action="{{ route('admin.pembayaran-lainnyas.status', $lain) }}" method="POST" class="d-inline">
                                                            @csrf @method('PATCH')
                                                            <input type="hidden" name="status" value="berhasil" />
                                                            <button type="submit" class="btn-icon btn btn-nexus-outline btn-sm text-success" title="Verifikasi berhasil" aria-label="Verifikasi pembayaran {{ $lain->kode_pembayaran }}"><i class="fa-solid fa-check"></i></button>
                                                        </form>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="empty-text">Belum ada pembayaran lainnya untuk calon ini.</p>
                    @endif
                </div>
            </div>

            <!-- Payment Modal -->
            @if (auth()->user()?->can('pembayarans.create') && $bolehDitagih)
                <div class="modal fade" id="modalPembayaran" tabindex="-1" aria-labelledby="modalPembayaranLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="modalPembayaranLabel">
                                    <i class="bi bi-plus-circle me-2"></i>Tambah Pembayaran
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <form action="{{ route('admin.pembayarans.store') }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <input type="hidden" name="calon_siswa_id" value="{{ $calon->id }}" />
                                <input type="hidden" name="jenis_pembayaran" value="penuh" />
                                <input type="hidden" name="redirect_to" value="{{ route('admin.calon-siswas.show', $calon) }}" />
                                <div class="modal-body">
                                    <div class="mb-3">
                                        <label for="selected_biaya_id" class="form-label">
                                            <i class="bi bi-tag text-primary me-1"></i>Biaya <span class="text-danger">*</span>
                                        </label>
                                        <select class="form-select" id="selected_biaya_id" name="biaya_pendaftaran_id" required>
                                            <option value="">Pilih Biaya (wajib &amp; non-wajib)</option>
                                            @foreach ($tagihan->rincian() as $biayaOpt)
                                                <option value="{{ $biayaOpt['id'] }}"
                                                    data-dapat-diangsur="{{ $biayaOpt['dapat_diangsur'] ? 1 : 0 }}"
                                                    data-sisa="{{ $biayaOpt['sisa'] }}"
                                                    data-mata-uang="{{ $biayaOpt['mata_uang'] }}">
                                                    {{ $biayaOpt['jenis'] }} · {{ $biayaOpt['wajib'] ? 'Wajib' : 'Opsional' }} · Sisa Rp {{ number_format($biayaOpt['sisa'], 0, ',', '.') }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <div class="form-text">
                                            <i class="bi bi-info-circle me-1"></i>Pilih termasuk biaya non-wajib (mis. Ekstrakurikuler) untuk pembayaran susulan.
                                        </div>
                                    </div>
                                    <div class="form-check mb-3 d-none" id="angsuran-check-wrap">
                                        <input type="checkbox" name="buat_angsuran" value="1" id="modal_buat_angsuran" class="form-check-input" />
                                        <label class="form-check-label" for="modal_buat_angsuran">Buat sebagai angsuran <span class="text-muted" style="font-size:11px;">(tagihan + DP + jadwal cicilan sekaligus)</span></label>
                                    </div>
                                    <div class="row g-3 mb-3 d-none" id="modal-angsuran-fields">
                                        <div class="col-md-4">
                                            <label for="modal_dp_dibayar" class="form-label">DP Dibayar</label>
                                            <input type="number" step="0.01" class="form-control" id="modal_dp_dibayar" name="dp_dibayar" min="0" />
                                        </div>
                                        <div class="col-md-4">
                                            <label for="modal_jumlah_cicilan" class="form-label">Dicicil Berapa Kali <span class="text-muted" style="font-size:11px;">(cth: 3 = 3x bayar)</span></label>
                                            <input type="number" class="form-control" id="modal_jumlah_cicilan" name="jumlah_cicilan" min="1" max="60" />
                                        </div>
                                        <div class="col-md-4">
                                            <label for="modal_tanggal_mulai" class="form-label">Mulai Cicilan</label>
                                            <input type="date" class="form-control" id="modal_tanggal_mulai" name="tanggal_mulai" />
                                        </div>
                                    </div>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label for="jumlah" class="form-label">
                                                <i class="bi bi-currency-dollar text-success me-1"></i>Jumlah Pembayaran
                                            </label>
                                            <div class="input-group">
                                                <span class="input-group-text" id="mata-uang-addon">Rp</span>
                                                <input type="number" class="form-control" id="jumlah" name="jumlah" required placeholder="0" min="0" />
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="metode_pembayaran" class="form-label">
                                                <i class="bi bi-credit-card text-primary me-1"></i>Metode Pembayaran
                                            </label>
                                            <select class="form-select" id="metode_pembayaran" name="metode_pembayaran" required>
                                                <option value="">Pilih metode pembayaran</option>
                                                <option value="transfer">Transfer Bank</option>
                                                <option value="tunai">Tunai</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="tanggal_pembayaran" class="form-label">
                                                <i class="bi bi-calendar-event text-info me-1"></i>Tanggal Pembayaran
                                            </label>
                                            <input type="date" class="form-control" id="tanggal_pembayaran" name="tanggal_pembayaran" value="{{ date('Y-m-d') }}" />
                                        </div>
                                        <div class="col-md-6">
                                            <label for="bukti_pembayaran" class="form-label">
                                                <i class="bi bi-cloud-upload text-warning me-1"></i>Bukti Pembayaran
                                            </label>
                                            <input type="file" class="form-control" id="bukti_pembayaran" name="bukti_pembayaran_path" accept=".pdf,.jpg,.jpeg,.png" />
                                            <div class="form-text">
                                                <i class="bi bi-info-circle me-1"></i>Format: JPG, PNG, PDF. Maksimal: 5MB. <strong>Wajib</strong> bila transfer, opsional bila tunai.
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <label for="catatan" class="form-label">
                                                <i class="bi bi-chat-text text-secondary me-1"></i>Catatan
                                            </label>
                                            <textarea class="form-control" id="catatan" name="catatan" rows="3" placeholder="Tambahkan catatan jika diperlukan…"></textarea>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                        <i class="bi bi-x-lg me-1"></i>Batal
                                    </button>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-check-lg me-1"></i>Simpan Pembayaran
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Modal Pembayaran Lainnya -->
            @if (auth()->user()?->can('pembayaran-lainnyas.create') && $bolehDitagih)
                <div class="modal fade" id="modalPembayaranLainnya" tabindex="-1" aria-labelledby="modalPembayaranLainnyaLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="modalPembayaranLainnyaLabel">
                                    <i class="bi bi-plus-circle me-2"></i>Tambah Pembayaran Lainnya
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <form action="{{ route('admin.pembayaran-lainnyas.store') }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <input type="hidden" name="calon_siswa_id" value="{{ $calon->id }}" />
                                <input type="hidden" name="redirect_to" value="{{ route('admin.calon-siswas.show', $calon) }}" />
                                <div class="modal-body">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label for="lain_nama_biaya" class="form-label">
                                                <i class="bi bi-tag text-success me-1"></i>Nama Biaya <span class="text-danger">*</span>
                                            </label>
                                            <input type="text" class="form-control" id="lain_nama_biaya" name="nama_biaya" required placeholder="cth: Denda keterlambatan" />
                                        </div>
                                        <div class="col-md-6">
                                            <label for="lain_jumlah" class="form-label">
                                                <i class="bi bi-currency-dollar text-success me-1"></i>Jumlah <span class="text-danger">*</span>
                                            </label>
                                            <div class="input-group">
                                                <span class="input-group-text">Rp</span>
                                                <input type="number" class="form-control" id="lain_jumlah" name="jumlah" required placeholder="0" min="0" />
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="lain_metode" class="form-label">
                                                <i class="bi bi-credit-card text-primary me-1"></i>Metode Pembayaran <span class="text-danger">*</span>
                                            </label>
                                            <select class="form-select" id="lain_metode" name="metode_pembayaran" required>
                                                <option value="">Pilih metode pembayaran</option>
                                                <option value="transfer">Transfer Bank</option>
                                                <option value="tunai">Tunai</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="lain_tanggal" class="form-label">
                                                <i class="bi bi-calendar-event text-info me-1"></i>Tanggal Pembayaran
                                            </label>
                                            <input type="date" class="form-control" id="lain_tanggal" name="tanggal_pembayaran" value="{{ date('Y-m-d') }}" />
                                        </div>
                                        <div class="col-md-6">
                                            <label for="lain_bukti" class="form-label">
                                                <i class="bi bi-cloud-upload text-warning me-1"></i>Bukti Pembayaran
                                            </label>
                                            <input type="file" class="form-control" id="lain_bukti" name="bukti_pembayaran_path" accept=".pdf,.jpg,.jpeg,.png" />
                                            <div class="form-text">
                                                <i class="bi bi-info-circle me-1"></i><strong>Wajib</strong> bila transfer, opsional bila tunai.
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="lain_catatan" class="form-label">
                                                <i class="bi bi-chat-text text-secondary me-1"></i>Catatan
                                            </label>
                                            <textarea class="form-control" id="lain_catatan" name="catatan" rows="3" placeholder="Opsional…"></textarea>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                        <i class="bi bi-x-lg me-1"></i>Batal
                                    </button>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-check-lg me-1"></i>Simpan
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Modal Bayar Cicilan (tetap di show calon, tanpa pindah halaman) -->
            @if (auth()->user()?->can('pembayarans.create') && $bolehDitagih)
                <div class="modal fade" id="modalBayarCicilan" tabindex="-1" aria-labelledby="modalBayarCicilanLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="modalBayarCicilanLabel">
                                    <i class="bi bi-credit-card me-2"></i>Bayar Cicilan
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <form action="{{ route('admin.pembayarans.store') }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <input type="hidden" name="calon_siswa_id" value="{{ $calon->id }}" />
                                <input type="hidden" name="jenis_pembayaran" value="cicilan_angsuran" />
                                <input type="hidden" name="detail_angsuran_id" id="cicilan_detail_id" />
                                <input type="hidden" name="biaya_pendaftaran_id" id="cicilan_biaya_id" />
                                <input type="hidden" name="jumlah" id="cicilan_jumlah" />
                                <input type="hidden" name="redirect_to" value="{{ route('admin.calon-siswas.show', $calon) }}" />
                                <div class="modal-body">
                                    <div class="alert alert-info py-2" style="font-size:13px;" id="cicilan_info">Pilih cicilan yang akan dibayar.</div>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label for="cicilan_metode" class="form-label">Metode <span class="text-danger">*</span></label>
                                            <select class="form-select" id="cicilan_metode" name="metode_pembayaran" required>
                                                <option value="transfer">Transfer Bank</option>
                                                <option value="tunai">Tunai</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="cicilan_tanggal" class="form-label">Tanggal Bayar</label>
                                            <input type="date" class="form-control" id="cicilan_tanggal" name="tanggal_pembayaran" value="{{ date('Y-m-d') }}" />
                                        </div>
                                        <div class="col-12">
                                            <label for="cicilan_bukti" class="form-label">Bukti <span class="text-muted" style="font-size:11px;">(wajib bila transfer)</span></label>
                                            <input type="file" class="form-control" id="cicilan_bukti" name="bukti_pembayaran_path" accept=".pdf,.jpg,.jpeg,.png" />
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                        <i class="bi bi-x-lg me-1"></i>Batal
                                    </button>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-check-lg me-1"></i>Simpan Pembayaran
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        // Set biaya ID for payment modal (bayar penuh per biaya, atau angsuran bila bisa)
        function setBiayaId(biayaId, jumlah, mataUang, dapatDiangsur) {
            var select = document.getElementById('selected_biaya_id');
            if (select) {
                select.value = biayaId;
                select.dispatchEvent(new Event('change'));
            }
            document.getElementById('jumlah').value = jumlah;
            document.getElementById('mata-uang-addon').textContent = mataUang;
            syncAngsuranToggle(dapatDiangsur);
        }

        function syncAngsuranToggle(dapatDiangsur) {
            var wrap = document.getElementById('angsuran-check-wrap');
            var check = document.getElementById('modal_buat_angsuran');
            var fields = document.getElementById('modal-angsuran-fields');
            if (wrap && check && fields) {
                var bisa = String(dapatDiangsur) === '1';
                wrap.classList.toggle('d-none', !bisa);
                if (!bisa) {
                    check.checked = false;
                    fields.classList.add('d-none');
                }
            }
        }

        document.getElementById('selected_biaya_id')?.addEventListener('change', function () {
            var opt = this.selectedOptions[0];
            if (opt && opt.dataset.sisa) {
                document.getElementById('jumlah').value = opt.dataset.sisa;
            }
            syncAngsuranToggle(opt ? opt.dataset.dapatDiangsur : '0');
        });

        document.getElementById('modal_buat_angsuran')?.addEventListener('change', function () {
            document.getElementById('modal-angsuran-fields')?.classList.toggle('d-none', !this.checked);
        });

        // Bukti wajib bila metode transfer
        document.getElementById('metode_pembayaran')?.addEventListener('change', function () {
            document.getElementById('bukti_pembayaran').required = this.value === 'transfer';
        });

        // Modal bayar cicilan: isi detail + nominal, bukti wajib bila transfer
        document.getElementById('cicilan_metode')?.addEventListener('change', function () {
            document.getElementById('cicilan_bukti').required = this.value === 'transfer';
        });

        function setCicilan(detailId, biayaId, nominal, label) {
            document.getElementById('cicilan_detail_id').value = detailId;
            document.getElementById('cicilan_biaya_id').value = biayaId;
            document.getElementById('cicilan_jumlah').value = nominal;
            document.getElementById('cicilan_info').textContent = label;
            var metode = document.getElementById('cicilan_metode');
            if (metode) {
                document.getElementById('cicilan_bukti').required = metode.value === 'transfer';
            }
        }

        document.addEventListener('DOMContentLoaded', function() {

            // Confirm before changing status
            const statusForm = document.getElementById('form-status');
            if (statusForm) {
                statusForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const status = document.getElementById('status_pendaftaran').value;
                    const studentName = @js($calon->nama_lengkap);
                    const confirmMessage = status === 'diterima'
                        ? `Apakah Anda yakin ingin MENERIMA ${studentName}?`
                        : `Apakah Anda yakin ingin mengubah status ${studentName} menjadi ${status}?`;
                    if (confirm(confirmMessage)) {
                        const submitBtn = this.querySelector('button[type="submit"]');
                        submitBtn.textContent = 'Memproses…';
                        submitBtn.disabled = true;
                        this.submit();
                    }
                });
            }

            // Initialize Bootstrap tooltips if available
            if (typeof bootstrap !== 'undefined') {
                const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                tooltipTriggerList.map(function(tooltipTriggerEl) {
                    return new bootstrap.Tooltip(tooltipTriggerEl);
                });
            }
        });
    </script>
@endpush