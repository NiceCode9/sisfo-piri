@extends('layouts.app')

@section('title', 'Dashboard')
@section('breadcrumb', 'Dashboard')

@section('content')
    @php
        $totalAbsenHariIni = array_sum($absensiHariIni);
        $totalAbsen30Hari = collect($trenKehadiran)->sum('tercatat');
        $totalPendaftar = collect($ppdb)->sum('total');
    @endphp

    <div class="page-header d-flex flex-wrap align-items-start justify-content-between gap-3">
        <div>
            <h1 class="page-title">Dashboard</h1>
            <p class="page-subtitle mb-0">{{ $hariIni->translatedFormat('l, d F Y') }}</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('admin.absensis.index') }}" class="btn btn-primary btn-sm">
                <i class="fa-solid fa-clipboard-check"></i> Absensi Harian
            </a>
            <a href="{{ route('admin.cbt.exams.index') }}" class="btn btn-nexus-outline btn-sm">
                <i class="fa-solid fa-file-question"></i> Ujian CBT
            </a>
        </div>
    </div>

    @include('layouts.partials.alert')

    {{-- Kartu utama. Setiap angka punya bentuk teks di dalam markup, bukan
         hanya digambar chart, supaya tetap terbaca tanpa JavaScript. --}}
    <div class="row g-3 mb-3">
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="stat-card h-100">
                <div class="stat-icon-wrap" style="background:rgba(79,70,229,.12);color:#4f46e5">
                    <i class="fa-solid fa-user-graduate"></i>
                </div>
                <div class="stat-value">{{ number_format($jumlahSiswa, 0, ',', '.') }}</div>
                <div class="stat-label">Siswa Aktif</div>
                <div class="stat-trend trend-neutral">{{ number_format($jumlahRombel, 0, ',', '.') }} rombel berjalan</div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="stat-card h-100">
                <div class="stat-icon-wrap" style="background:rgba(124,58,237,.12);color:#7c3aed">
                    <i class="fa-solid fa-chalkboard-user"></i>
                </div>
                <div class="stat-value">{{ number_format($jumlahGuru, 0, ',', '.') }}</div>
                <div class="stat-label">Guru Aktif</div>
                <div class="stat-trend trend-neutral">Termasuk guru piket &amp; wali kelas</div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="stat-card stat-card-gradient h-100">
                <div class="stat-icon-wrap" style="background:rgba(255,255,255,.18);color:#fff">
                    <i class="fa-solid fa-clipboard-check"></i>
                </div>
                <div class="stat-value">{{ number_format($totalAbsenHariIni, 0, ',', '.') }}</div>
                <div class="stat-label">Absensi Tercatat Hari Ini</div>
                <div class="stat-trend">
                    <i class="fa-solid fa-calendar-day"></i>
                    {{ $absensiHariIni['hadir'] + $absensiHariIni['terlambat'] }} hadir
                    @if ($absensiHariIni['alpa'] > 0)
                        &middot; {{ $absensiHariIni['alpa'] }} alpa
                    @endif
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="stat-card h-100">
                <div class="stat-icon-wrap" style="background:rgba(34,197,94,.12);color:#22c55e">
                    <i class="fa-solid fa-clipboard-question"></i>
                </div>
                <div class="stat-value">{{ number_format($antreanPenilaian['total'], 0, ',', '.') }}</div>
                <div class="stat-label">Menunggu Penilaian</div>
                <div class="stat-trend {{ $antreanPenilaian['total'] > 0 ? 'trend-down' : 'trend-up' }}">
                    <i class="fa-solid {{ $antreanPenilaian['total'] > 0 ? 'fa-triangle-exclamation' : 'fa-check' }}"></i>
                    Pengumpulan tugas
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        {{-- Komposisi absensi hari ini. Angkanya tetap ditulis sebagai teks di
             bawah chart supaya tidak hilang kalau CDN Chart.js gagal. --}}
        <div class="col-12 col-xl-4">
            <div class="card-nexus h-100">
                <div class="card-header-nexus">
                    <div>
                        <h5 class="card-title">Absensi Hari Ini</h5>
                        <p class="card-subtitle">Terlambat dihitung sebagai hadir</p>
                    </div>
                </div>
                <div class="card-body-nexus">
                    @if ($totalAbsenHariIni === 0)
                        <div class="text-center py-4">
                            <i class="fa-solid fa-inbox fa-2x text-muted mb-2"></i>
                            <p class="mb-0 text-muted">Belum ada absensi tercatat hari ini.</p>
                        </div>
                    @else
                        <div class="chart-box position-relative" style="height:200px">
                            <canvas id="chartAbsensiHariIni"></canvas>
                        </div>
                        @php
                            $konfigurasiAbsensi = [
                                'type' => 'doughnut',
                                'data' => [
                                    'labels' => array_map(fn ($s) => ucfirst($s), $statusAbsensi),
                                    'datasets' => [[
                                        'data' => array_values($absensiHariIni),
                                        'backgroundColor' => [
                                            'rgba(34,197,94,.85)',
                                            'rgba(245,158,11,.85)',
                                            'rgba(59,130,246,.85)',
                                            'rgba(59,130,246,.55)',
                                            'rgba(239,68,68,.85)',
                                        ],
                                        'borderWidth' => 0,
                                    ]],
                                ],
                            ];
                        @endphp
                        <script type="application/json" data-chart data-target="chartAbsensiHariIni">@json($konfigurasiAbsensi)</script>
                        <ul class="list-unstyled mt-3 mb-0">
                            @foreach ($statusAbsensi as $status)
                                <li class="d-flex justify-content-between align-items-center py-1">
                                    <span>
                                        <span class="badge-nexus {{ $warnaAbsensi[$status] }}">{{ $absensiHariIni[$status] }}</span>
                                        <span class="ms-2">{{ ucfirst($status) }}</span>
                                    </span>
                                    <span class="text-muted small">
                                        {{ $totalAbsenHariIni > 0 ? round($absensiHariIni[$status] / $totalAbsenHariIni * 100) : 0 }}%
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>

        {{-- Tren kehadiran 30 hari --}}
        <div class="col-12 col-xl-8">
            <div class="card-nexus h-100">
                <div class="card-header-nexus">
                    <div>
                        <h5 class="card-title">Tren Kehadiran 30 Hari</h5>
                        <p class="card-subtitle">
                            {{ number_format($totalAbsen30Hari, 0, ',', '.') }} catatan absensi
                        </p>
                    </div>
                </div>
                <div class="card-body-nexus">
                    @if ($totalAbsen30Hari === 0)
                        <div class="text-center py-5">
                            <i class="fa-solid fa-chart-area fa-2x text-muted mb-2"></i>
                            <p class="mb-0 text-muted">Belum ada data absensi 30 hari terakhir.</p>
                        </div>
                    @else
                        <div class="chart-box position-relative" style="height:280px">
                            <canvas id="chartTrenKehadiran"></canvas>
                        </div>
                        @php
                            $konfigurasiKehadiran = [
                                'type' => 'line',
                                'data' => [
                                    'labels' => array_column($trenKehadiran, 'label'),
                                    'datasets' => [[
                                        'label' => 'Hadir (%)',
                                        'data' => array_column($trenKehadiran, 'persen'),
                                        'borderColor' => '#4f46e5',
                                        'backgroundColor' => 'rgba(79,70,229,.12)',
                                        'fill' => true,
                                        'tension' => 0.3,
                                        'pointRadius' => 0,
                                        'borderWidth' => 2,
                                    ]],
                                ],
                            ];
                        @endphp
                        <script type="application/json" data-chart data-target="chartTrenKehadiran">@json($konfigurasiKehadiran)</script>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        {{-- PPDB --}}
        <div class="col-12 col-xl-4">
            <div class="card-nexus h-100">
                <div class="card-header-nexus">
                    <div>
                        <h5 class="card-title">Pendaftaran PPDB</h5>
                        <p class="card-subtitle">{{ number_format($totalPendaftar, 0, ',', '.') }} pendaftar</p>
                    </div>
                </div>
                <div class="card-body-nexus">
                    @forelse ($ppdb as $baris)
                        @php $persen = $totalPendaftar > 0 ? round($baris['total'] / $totalPendaftar * 100) : 0; @endphp
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span>{{ $baris['label'] }}</span>
                                <span class="text-muted">{{ $baris['total'] }} ({{ $persen }}%)</span>
                            </div>
                            <div class="progress-nexus">
                                <div class="progress-bar bg-{{ $baris['status'] === 'diterima' ? 'success' : ($baris['status'] === 'ditolak' ? 'danger' : 'primary') }}" style="width:{{ $persen }}%"></div>
                            </div>
                        </div>
                    @empty
                        <p class="text-muted mb-0">Belum ada pendaftar.</p>
                    @endforelse
                    <a href="{{ route('admin.calon-siswas.index') }}" class="btn btn-nexus-outline btn-sm w-100">
                        Kelola Pendaftar
                    </a>
                </div>
            </div>
        </div>

        {{-- Tren pendaftaran 6 bulan --}}
        <div class="col-12 col-xl-4">
            <div class="card-nexus h-100">
                <div class="card-header-nexus">
                    <div>
                        <h5 class="card-title">Pendaftar per Bulan</h5>
                        <p class="card-subtitle">6 bulan terakhir</p>
                    </div>
                </div>
                <div class="card-body-nexus">
                    @if ($totalPendaftar === 0)
                        <div class="text-center py-5">
                            <i class="fa-solid fa-user-plus fa-2x text-muted mb-2"></i>
                            <p class="mb-0 text-muted">Belum ada pendaftar.</p>
                        </div>
                    @else
                        <div class="chart-box position-relative" style="height:220px">
                            <canvas id="chartTrenPendaftaran"></canvas>
                        </div>
                        @php
                            $konfigurasiPendaftaran = [
                                'type' => 'bar',
                                'data' => [
                                    'labels' => array_column($trenPendaftaran, 'label'),
                                    'datasets' => [[
                                        'label' => 'Pendaftar',
                                        'data' => array_column($trenPendaftaran, 'total'),
                                        'backgroundColor' => 'rgba(124,58,237,.75)',
                                        'borderRadius' => 4,
                                        'maxBarThickness' => 40,
                                    ]],
                                ],
                            ];
                        @endphp
                        <script type="application/json" data-chart data-target="chartTrenPendaftaran">@json($konfigurasiPendaftaran)</script>
                    @endif
                </div>
            </div>
        </div>

        {{-- Keuangan --}}
        <div class="col-12 col-xl-4">
            <div class="card-nexus h-100">
                <div class="card-header-nexus">
                    <div>
                        <h5 class="card-title">Penerimaan {{ $pembayaranBulanIni['bulan'] }}</h5>
                        <p class="card-subtitle">{{ $pembayaranBulanIni['transaksi'] }} transaksi berhasil</p>
                    </div>
                </div>
                <div class="card-body-nexus">
                    <div class="stat-value mb-2">Rp {{ number_format($pembayaranBulanIni['total'], 0, ',', '.') }}</div>
                    @if ($pembayaranBulanIni['transaksi'] === 0)
                        <p class="text-muted small mb-3">Belum ada pembayaran diterima bulan ini.</p>
                    @else
                        <p class="text-muted small mb-3">
                            Rata-rata
                            Rp {{ number_format($pembayaranBulanIni['total'] / $pembayaranBulanIni['transaksi'], 0, ',', '.') }}
                            per transaksi.
                        </p>
                    @endif
                    <a href="{{ route('admin.pembayarans.index') }}" class="btn btn-nexus-outline btn-sm w-100">
                        Lihat Pembayaran
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        {{-- Antrean penilaian --}}
        <div class="col-12 col-xl-7">
            <div class="card-nexus h-100">
                <div class="card-header-nexus">
                    <div>
                        <h5 class="card-title">Menunggu Penilaian</h5>
                        <p class="card-subtitle">{{ $antreanPenilaian['total'] }} pengumpulan belum dinilai</p>
                    </div>
                    <a href="{{ route('admin.tugas.index') }}" class="btn btn-nexus-outline btn-sm">Semua Tugas</a>
                </div>
                <div class="card-body-nexus">
                    @forelse ($antreanPenilaian['contoh'] as $kumpulan)
                        <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                            <div>
                                <div class="fw-semibold">{{ $kumpulan->tugas?->judul ?? 'Tugas dihapus' }}</div>
                                <div class="small text-muted">
                                    {{ $kumpulan->tugas?->rombel?->kelas?->nama_kelas ?? '-' }}
                                    &middot; {{ $kumpulan->siswa?->user?->name ?? 'Siswa dihapus' }}
                                    @if ($kumpulan->is_terlambat)
                                        <span class="badge-nexus badge-warning ms-1">Terlambat</span>
                                    @endif
                                </div>
                            </div>
                            <a href="{{ route('admin.tugas.index') }}" class="btn btn-sm btn-nexus-outline">Nilai</a>
                        </div>
                    @empty
                        <div class="text-center py-4">
                            <i class="fa-solid fa-circle-check fa-2x text-success mb-2"></i>
                            <p class="mb-0 text-muted">Semua pengumpulan sudah dinilai.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Ujian CBT --}}
        <div class="col-12 col-xl-5">
            <div class="card-nexus h-100">
                <div class="card-header-nexus">
                    <div>
                        <h5 class="card-title">Ujian CBT Berjalan</h5>
                        <p class="card-subtitle">{{ $sesiOngoing }} sesi siswa sedang dikerjakan</p>
                    </div>
                    <a href="{{ route('admin.cbt.exams.index') }}" class="btn btn-nexus-outline btn-sm">Kelola</a>
                </div>
                <div class="card-body-nexus">
                    @forelse ($ujianBerjalan as $ujian)
                        <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                            <div>
                                <div class="fw-semibold">{{ $ujian->name }}</div>
                                <div class="small text-muted">
                                    {{ $ujian->rombel?->kelas?->nama_kelas ?? '-' }}
                                    @if ($ujian->mataPelajaran)
                                        &middot; {{ $ujian->mataPelajaran->nama }}
                                    @endif
                                    &middot; {{ $ujian->tokens_count }} token
                                </div>
                            </div>
                            <div class="text-end">
                                <span class="badge-nexus badge-info">{{ $ujian->duration_minutes }} menit</span>
                                @if ($ujian->available_until)
                                    <div class="small text-muted mt-1">
                                        s/d {{ $ujian->available_until->format('j M H:i') }}
                                    </div>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-4">
                            <i class="fa-solid fa-file-circle-check fa-2x text-muted mb-2"></i>
                            <p class="mb-0 text-muted">Tidak ada ujian yang sedang berlangsung.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection
