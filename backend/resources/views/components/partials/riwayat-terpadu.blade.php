{{--
    Riwayat terpadu: kehadiran, e-learning, dan nilai CBT per rombel.

    Dipakai tiga area (siswa, guru, orang tua) dengan judul halaman yang
    berbeda saja — datanya identik karena semuanya lewat
    App\Services\RiwayatSiswa. Read-only dan sengaja tidak memuat nilai rapor,
    predikat, atau peringkat.
--}}
@if(($baris ?? []) === [])
    <div class="card-nexus">
        <div class="card-body-nexus text-center py-4" style="color:var(--text-muted);">
            Belum ada riwayat yang bisa ditampilkan. Riwayat muncul setelah siswa
            memiliki catatan kehadiran, tugas, atau ujian pada suatu rombel.
        </div>
    </div>
@else
    <div class="d-flex gap-2 flex-wrap mb-3">
        <span class="badge-nexus badge-info">Rombel: {{ $ringkas['rombel'] }}</span>
        <span class="badge-nexus badge-info">Tahun ajaran: {{ $ringkas['tahun'] }}</span>
        <span class="badge-nexus badge-neutral">Hari tercatat: {{ $ringkas['hariAbsen'] }}</span>
        @if($ringkas['persenAbsen'] !== null)
            <span class="badge-nexus badge-{{ $ringkas['persenAbsen'] >= 80 ? 'success' : ($ringkas['persenAbsen'] >= 60 ? 'warning' : 'danger') }}">
                Kehadiran {{ $ringkas['persenAbsen'] }}%
            </span>
        @endif
        <span class="badge-nexus badge-neutral">Tugas: {{ $ringkas['tugas'] }}</span>
    </div>

    @foreach($baris as $r)
        <div class="card-nexus mb-3">
            <div class="card-header-nexus d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <h5 class="card-title mb-0">{{ $r['kelas'] ?? 'Tanpa kelas' }} — {{ $r['tahun'] ?? 'Tanpa tahun' }}</h5>
                    @if($r['rombel']->waliGuru)
                        <p class="card-subtitle mb-0">Wali: {{ $r['rombel']->waliGuru->nama }}</p>
                    @endif
                </div>
                @if($r['absensi']['persen'] !== null)
                    <span class="badge-nexus badge-{{ $r['absensi']['persen'] >= 80 ? 'success' : ($r['absensi']['persen'] >= 60 ? 'warning' : 'danger') }}">
                        {{ $r['absensi']['persen'] }}% hadir
                    </span>
                @endif
            </div>
            <div class="card-body-nexus">

                <h6 class="mb-2" style="font-size:13px;font-weight:600;">Kehadiran</h6>
                @if($r['absensi']['total'] === 0)
                    <p style="font-size:13px;color:var(--text-muted);">Tidak ada catatan kehadiran.</p>
                @else
                    <div class="d-flex gap-2 flex-wrap mb-2">
                        <span class="badge-nexus badge-info">Hadir: {{ $r['absensi']['hadir'] }}</span>
                        <span class="badge-nexus badge-warning">Terlambat: {{ $r['absensi']['terlambat'] }}</span>
                        <span class="badge-nexus badge-neutral">Sakit: {{ $r['absensi']['sakit'] }}</span>
                        <span class="badge-nexus badge-neutral">Izin: {{ $r['absensi']['izin'] }}</span>
                        <span class="badge-nexus badge-danger">Alpa: {{ $r['absensi']['alpa'] }}</span>
                        <span class="badge-nexus badge-neutral">Total hari: {{ $r['absensi']['total'] }}</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table-nexus w-100" style="font-size:12.5px;">
                            <thead><tr><th>Periode</th><th>Hadir</th><th>Alpa</th><th>Total hari</th><th>Persentase</th></tr></thead>
                            <tbody>
                                @foreach(['ganjil' => 'Semester Ganjil', 'genap' => 'Semester Genap'] as $kunci => $label)
                                    <tr>
                                        <td>{{ $label }}</td>
                                        <td>{{ $r['periode'][$kunci]['hadir'] }}</td>
                                        <td>{{ $r['periode'][$kunci]['alpa'] }}</td>
                                        <td>{{ $r['periode'][$kunci]['total'] }}</td>
                                        <td>{{ $r['periode'][$kunci]['persen'] === null ? '—' : $r['periode'][$kunci]['persen'].'%' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                <h6 class="mb-2 mt-3" style="font-size:13px;font-weight:600;">E-Learning</h6>
                @if($r['elearning'] === [])
                    <p style="font-size:13px;color:var(--text-muted);">Tidak ada tugas di rombel ini.</p>
                @else
                    <div class="table-responsive">
                        <table class="table-nexus w-100" style="font-size:12.5px;">
                            <thead><tr><th>Mapel</th><th>Ditugas</th><th>Dikumpulkan</th><th>Belum</th><th>Dinilai</th><th>Rata-rata</th></tr></thead>
                            <tbody>
                                @foreach($r['elearning'] as $m)
                                    <tr>
                                        <td><span class="badge-nexus badge-info">{{ $m['mapel']?->kode ?? '—' }}</span></td>
                                        <td>{{ $m['ditugas'] }}</td>
                                        <td>{{ $m['dikumpul'] }}</td>
                                        <td>{{ $m['belumDikumpul'] }}</td>
                                        <td>{{ $m['dinilai'] }}</td>
                                        <td>{{ $m['rata'] === null ? '—' : $m['rata'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                <h6 class="mb-2 mt-3" style="font-size:13px;font-weight:600;">Nilai CBT</h6>
                @if($r['cbt'] === [])
                    <p style="font-size:13px;color:var(--text-muted);">Tidak ada ujian yang diikuti.</p>
                @else
                    <div class="table-responsive">
                        <table class="table-nexus w-100" style="font-size:12.5px;">
                            <thead><tr><th>Ujian</th><th>Mapel</th><th>Skor</th><th>Status</th></tr></thead>
                            <tbody>
                                @foreach($r['cbt'] as $u)
                                    <tr>
                                        <td>{{ $u['nama'] }}</td>
                                        <td>{{ $u['mapel'] ?? '—' }}</td>
                                        <td>{{ $u['skor'] === null ? '—' : $u['skor'] }}</td>
                                        <td>{{ $u['status'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if($r['cbtRata'] !== null)
                        <p style="font-size:12.5px;margin-top:6px;">Rata-rata skor ujian: <strong>{{ $r['cbtRata'] }}</strong></p>
                    @endif
                @endif

            </div>
        </div>
    @endforeach
@endif