@extends('layouts.app')

@section('title', 'Jurnalistik — Dashboard')
@section('breadcrumb', 'Jurnalistik')

@section('content')
<div class="page-header d-flex flex-wrap align-items-start justify-content-between gap-3">
    <div>
        <h1 class="page-title">Dashboard Jurnalistik</h1>
        <p class="page-subtitle mb-0">Ringkasan aktivitas redaksi.</p>
    </div>
</div>

@include('layouts.partials.alert')

{{-- Stat cards --}}
<div class="row g-3 mb-3">
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="stat-card h-100">
            <div class="stat-icon-wrap" style="background:rgba(79,70,229,.12);color:#4f46e5">
                <i class="fa-solid fa-newspaper"></i>
            </div>
            <div class="stat-value">{{ number_format($totalBerita) }}</div>
            <div class="stat-label">Total Berita</div>
            <div class="stat-trend trend-neutral">{{ $draft }} draft &middot; {{ $published }} terbit</div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
        <div class="stat-card h-100">
            <div class="stat-icon-wrap" style="background:rgba(245,158,11,.12);color:#f59e0b">
                <i class="fa-solid fa-clock"></i>
            </div>
            <div class="stat-value">{{ number_format($scheduled) }}</div>
            <div class="stat-label">Terjadwal</div>
            <div class="stat-trend trend-neutral">Belum terbit (future)</div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
        <div class="stat-card h-100">
            <div class="stat-icon-wrap" style="background:rgba(34,197,94,.12);color:#22c55e">
                <i class="fa-solid fa-folder"></i>
            </div>
            <div class="stat-value">{{ number_format($totalKategori) }}</div>
            <div class="stat-label">Kategori</div>
            <div class="stat-trend trend-neutral">{{ $totalTag }} tag</div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
        <div class="stat-card {{ $komentarPending > 0 ? 'stat-card-gradient' : '' }} h-100">
            <div class="stat-icon-wrap" style="background:rgba(239,68,68,.12);color:#ef4444">
                <i class="fa-solid fa-comments"></i>
            </div>
            <div class="stat-value">{{ number_format($komentarPending) }}</div>
            <div class="stat-label">Komentar Pending</div>
            <div class="stat-trend {{ $komentarPending > 0 ? 'trend-down' : 'trend-up' }}">
                <i class="fa-solid {{ $komentarPending > 0 ? 'fa-triangle-exclamation' : 'fa-check' }}"></i>
                {{ $komentarPending > 0 ? 'Perlu moderasi' : 'Semua bersih' }}
            </div>
        </div>
    </div>
</div>

{{-- Analitik --}}
<div class="card-nexus mt-3 mb-3">
    <div class="card-header-nexus">
        <div>
            <h5 class="card-title">Analitik Pengunjung</h5>
            <p class="card-subtitle">Data dari tracking internal (7 hari terakhir)</p>
        </div>
    </div>
    <div class="card-body-nexus">
        <div class="row g-3 mb-3">
            <div class="col-6 col-md-3">
                <div class="text-muted small">Total Views</div>
                <div class="h4 mb-0">{{ number_format($totalViews) }}</div>
            </div>
            <div class="col-6 col-md-3">
                <div class="text-muted small">Unique Visitor</div>
                <div class="h4 mb-0">{{ number_format($uniqueVisitors) }}</div>
            </div>
            <div class="col-6 col-md-3">
                <div class="text-muted small">Rata-rata Durasi Baca</div>
                <div class="h4 mb-0">{{ gmdate('i:s', $avgDuration) }}</div>
            </div>
            <div class="col-6 col-md-3">
                <div class="text-muted small">Views 7 Hari</div>
                <div class="h4 mb-0">{{ number_format($viewsPerDay->sum()) }}</div>
            </div>
        </div>
        @php
            // Lengkapi 7 hari terakhir (termasuk hari tanpa views) agar chart
            // tidak "bolong" dan skala tetap konsisten.
            $hariList = [];
            for ($i = 6; $i >= 0; $i--) {
                $d = now()->subDays($i)->toDateString();
                $hariList[$d] = (int) ($viewsPerDay[$d] ?? 0);
            }
            $maxView = max($hariList) ?: 1;
        @endphp
        <div class="small text-muted mb-2">Tren views per hari (7 hari terakhir):</div>
        <div class="d-flex align-items-end gap-1" style="height: 72px;">
            @foreach($hariList as $day => $count)
                @php $h = (int) round(($count / $maxView) * 56); @endphp
                <div class="text-center" style="flex:1; min-width:0;">
                    <div style="height: {{ $h }}px; min-height: {{ $count > 0 ? 4 : 1 }}px; background: var(--accent-primary); border-radius: 3px 3px 0 0; margin: 0 auto; width: 60%;" title="{{ \Carbon\Carbon::parse($day)->translatedFormat('d M') }}: {{ $count }} views"></div>
                    <small class="text-muted d-block" style="font-size:9px; line-height:1;">{{ \Carbon\Carbon::parse($day)->format('d/m') }}</small>
                </div>
            @endforeach
        </div>
        <div class="d-flex justify-content-between mt-1">
            <small class="text-muted" style="font-size:9px;">0</small>
            <small class="text-muted" style="font-size:9px;">max {{ $maxView }}</small>
        </div>
    </div>
</div>

<div class="card-nexus mt-3 mb-3">
    <div class="card-header-nexus">
        <div>
            <h5 class="card-title">Berita Terpopuler (30 hari)</h5>
            <p class="card-subtitle">Berdasarkan jumlah views</p>
        </div>
    </div>
    <div class="card-body-nexus p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Judul</th>
                        <th>Views</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($topBerita as $b)
                        <tr>
                            <td class="fw-semibold">{{ \Illuminate\Support\Str::limit($b->judul, 50) }}</td>
                            <td>{{ number_format($b->views_count) }}</td>
                            <td class="text-end">
                                <a href="{{ route('jurnalistik.beritas.edit', $b) }}" class="btn btn-sm btn-outline-secondary">
                                    <i class="fa-solid fa-pen"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-center text-muted py-4">Belum ada data.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="row g-3">
    {{-- Berita terbaru --}}
    <div class="col-12 col-lg-7">
        <div class="card-nexus h-100">
            <div class="card-header-nexus">
                <div>
                    <h5 class="card-title">Berita Terbaru</h5>
                    <p class="card-subtitle">5 berita terakhir yang dibuat</p>
                </div>
                <a href="{{ route('jurnalistik.beritas.index') }}" class="btn btn-nexus-outline btn-sm">Lihat semua</a>
            </div>
            <div class="card-body-nexus p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead>
                            <tr>
                                <th>Judul</th>
                                <th>Kategori</th>
                                <th>Status</th>
                                <th>Tanggal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($beritaTerbaru as $b)
                                <tr>
                                    <td class="fw-semibold">{{ \Illuminate\Support\Str::limit($b->judul, 50) }}</td>
                                    <td>{{ $b->kategori?->nama }}</td>
                                    <td>
                                        @if($b->status === 'published')
                                            <span class="badge bg-success">Published</span>
                                        @else
                                            <span class="badge bg-secondary">Draft</span>
                                        @endif
                                    </td>
                                    <td><small class="text-muted">{{ $b->created_at->diffForHumans() }}</small></td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted py-4">Belum ada berita.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Komentar pending --}}
    <div class="col-12 col-lg-5">
        <div class="card-nexus h-100">
            <div class="card-header-nexus">
                <div>
                    <h5 class="card-title">Komentar Pending</h5>
                    <p class="card-subtitle">Menunggu moderasi</p>
                </div>
                <a href="{{ route('jurnalistik.komentars.index') }}" class="btn btn-nexus-outline btn-sm">Moderasi</a>
            </div>
            <div class="card-body-nexus p-0">
                <ul class="list-group list-group-flush">
                    @forelse($komentarTerbaru as $k)
                        <li class="list-group-item">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="fw-semibold small">{{ $k->nama ?: $k->user?->name }}</div>
                                    <div class="text-muted small">{{ \Illuminate\Support\Str::limit($k->isi, 60) }}</div>
                                    <small class="text-muted">{{ $k->berita?->judul }}</small>
                                </div>
                                <small class="text-muted">{{ $k->created_at->diffForHumans() }}</small>
                            </div>
                        </li>
                    @empty
                        <li class="list-group-item text-center text-muted py-4">Tidak ada komentar pending.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection