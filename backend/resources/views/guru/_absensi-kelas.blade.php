@php
    $labelStatus = [
        'hadir' => 'Hadir',
        'terlambat' => 'Terlambat',
        'izin' => 'Izin',
        'sakit' => 'Sakit',
        'alpa' => 'Alpa',
    ];
    $badgeStatus = [
        'hadir' => 'badge-success',
        'terlambat' => 'badge-warning',
        'izin' => 'badge-info',
        'sakit' => 'badge-info',
        'alpa' => 'badge-danger',
    ];
    $tampil = $mode === 'antrian'
        ? array_values(array_filter($baris, fn ($row) => $row['absensi'] === null))
        : $baris;
@endphp

<div class="card-nexus">
    <div class="card-header-nexus flex-wrap gap-2">
        <div>
            <h5 class="card-title">Kelas {{ $rombel->kelas?->nama_kelas ?? $rombel->id }}</h5>
            <p class="card-subtitle mb-0">{{ $tanggal->translatedFormat('l, d F Y') }}</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <span class="badge-nexus badge-success">Hadir {{ $ringkasan['hadir'] }}</span>
            <span class="badge-nexus badge-warning">Terlambat {{ $ringkasan['terlambat'] }}</span>
            <span class="badge-nexus badge-info">Izin {{ $ringkasan['izin'] }}</span>
            <span class="badge-nexus badge-info">Sakit {{ $ringkasan['sakit'] }}</span>
            <span class="badge-nexus badge-danger">Alpa {{ $ringkasan['alpa'] }}</span>
            <span class="badge-nexus badge-neutral">Belum {{ $ringkasan['belum'] }}</span>
        </div>
    </div>
    <div class="card-body-nexus">
        <div class="d-flex gap-2 flex-wrap mb-3" role="tablist" aria-label="Filter daftar absensi">
            <a
                href="{{ route('guru.absensi', ['mode' => 'antrian']) }}"
                class="btn btn-sm {{ $mode === 'antrian' ? 'btn-primary' : 'btn-nexus-outline' }}"
                @if ($mode === 'antrian') aria-current="page" @endif
            >Belum tercatat ({{ $ringkasan['belum'] }})</a>
            <a
                href="{{ route('guru.absensi', ['mode' => 'semua']) }}"
                class="btn btn-sm {{ $mode === 'semua' ? 'btn-primary' : 'btn-nexus-outline' }}"
                @if ($mode === 'semua') aria-current="page" @endif
            >Semua ({{ $ringkasan['total'] }})</a>
        </div>

        @if ($ringkasan['total'] === 0)
            <p class="mb-0 text-center py-3" style="color:var(--text-muted);">Belum ada siswa aktif di kelas ini.</p>
        @elseif ($tampil === [])
            <p class="mb-0 text-center py-3" style="color:var(--text-muted);">Semua siswa sudah tercatat.</p>
        @else
            <div class="table-responsive">
                <table class="table-nexus w-100">
                    <thead>
                        <tr>
                            <th>Siswa</th>
                            <th style="width:120px;">Status</th>
                            <th style="width:70px;">Jam</th>
                            <th style="width:84px;">Metode</th>
                            <th style="width:150px;">Dicatat oleh</th>
                            <th style="width:160px;">Tindakan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($tampil as $row)
                            @php
                                $absensi = $row['absensi'];
                                $terpilih = $siswaTerpilih !== null && (int) $siswaTerpilih === (int) $row['siswa']->id;
                            @endphp
                            <tr
                                data-tercatat="{{ $absensi ? '1' : '0' }}"
                                @if ($terpilih) data-terpilih="1" style="box-shadow: inset 3px 0 0 var(--accent-primary);" @endif
                            >
                                <td>
                                    <span style="font-weight:600;">{{ $row['nama'] }}</span>
                                    <span class="text-muted" style="font-size:11px;">{{ $row['nis'] }}</span>
                                </td>
                                <td>
                                    @if ($absensi)
                                        <span class="badge-nexus {{ $badgeStatus[$absensi->status] ?? 'badge-neutral' }}">{{ $labelStatus[$absensi->status] ?? ucfirst($absensi->status) }}</span>
                                    @else
                                        <span class="badge-nexus badge-neutral">Belum tercatat</span>
                                    @endif
                                </td>
                                <td style="font-size:12.5px;">{{ $absensi?->jam_datang ? substr($absensi->jam_datang, 0, 5) : '-' }}</td>
                                <td style="font-size:12.5px;">{{ $absensi ? ($absensi->metode === 'qr' ? 'QR' : 'Manual') : '-' }}</td>
                                <td style="font-size:12.5px;">{{ $absensi?->pencatat?->name ?? ($absensi ? 'Sistem' : '-') }}</td>
                                <td style="font-size:12px;">
                                    @if ($absensi)
                                        @can('absensis.create')
                                            @can('absensis.edit')
                                                <a href="{{ route('admin.absensis.index', ['rombel_id' => $rombel->id, 'tanggal' => $tanggal->toDateString()]) }}">Koreksi di grid</a>
                                            @else
                                                <span style="color:var(--text-muted);">Koreksi oleh admin.</span>
                                            @endcan
                                        @else
                                            <span style="color:var(--text-muted);">Koreksi oleh admin.</span>
                                        @endcan
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
