@extends('layouts.app')

@section('title', 'Nexus Admin — Tahun Ajaran Baru')
@section('breadcrumb', 'Tahun Ajaran Baru')

@section('content')
<div class="page-header">
    <h1 class="page-title">Tahun Ajaran Baru</h1>
    <p class="page-subtitle mb-0">Salin rombel tahun tujuan, lalu pindahkan siswa — dalam satu proses</p>
</div>

@include('layouts.partials.alert')

@if(count($tahunAjarans) < 2)
    <div class="card-nexus">
        <div class="card-body-nexus">
            <div class="alert alert-warning mb-0" style="font-size:13px;">
                @foreach($peringatan as $p)<div>{{ $p }}</div>@endforeach
            </div>
        </div>
    </div>
@else
    <div class="card-nexus mb-3">
        <div class="card-body-nexus">
            <div class="alert alert-info mb-0" style="font-size:13px;">
                Satu proses ini menggantikan dua langkah yang sebelumnya harus dijalankan manual dan bisa salah urutan.
                Rombel, wali, dan penugasan tahun tujuan disalin lebih dulu; <strong>baru</strong> siswa dipindahkan.
                Kenaikan tidak akan dijalankan bila ada kelas tujuan yang belum punya rombel — kalau tidak, siswa akan
                menunjuk kelas yang tidak ada dan lenyap dari rekap absensi, nilai tugas, dan nilai ujian.
                <br>
                <strong>Override per siswa ada di halaman ini juga</strong>, jadi tidak ada lagi halaman terpisah yang
                bisa memindahkan siswa tanpa melewati langkah salin.
            </div>
        </div>
    </div>

    <form method="GET" action="{{ route('admin.tahun-ajaran-baru.index') }}" class="card-nexus mb-3">
        <div class="card-header-nexus">
            <h5 class="card-title">Periode</h5>
            <p class="card-subtitle mb-0">Tahun asal siswa aktif dan tahun tujuan yang akan disiapkan</p>
        </div>
        <div class="card-body-nexus">
            <div class="d-flex gap-3 flex-wrap align-items-end">
                <div>
                    <label class="form-label" style="font-size:12px;" for="tahun_asal_id">Dari Tahun Ajaran</label>
                    <select name="tahun_asal_id" id="tahun_asal_id" class="form-select form-select-sm" onchange="this.form.submit()">
                        @foreach($tahunAjarans as $t)
                            <option value="{{ $t->id }}" @selected($t->id === $tahunAsalId)>{{ $t->nama_tahun_ajaran }}{{ $tahunAktif && $tahunAktif->id === $t->id ? ' (aktif)' : '' }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label" style="font-size:12px;" for="tahun_tujuan_id">Ke Tahun Ajaran</label>
                    <select name="tahun_tujuan_id" id="tahun_tujuan_id" class="form-select form-select-sm" onchange="this.form.submit()">
                        @foreach($tahunAjarans as $t)
                            <option value="{{ $t->id }}" @selected($t->id === $tahunTujuanId)>{{ $t->nama_tahun_ajaran }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </form>

    <form method="POST" action="{{ route('admin.tahun-ajaran-baru.proses') }}" id="form-naik-tahun">
        @csrf
        <input type="hidden" name="tahun_asal_id" value="{{ $tahunAsalId }}">
        <input type="hidden" name="tahun_tujuan_id" value="{{ $tahunTujuanId }}">

        <div class="card-nexus mb-3">
            <div class="card-header-nexus"><h5 class="card-title">Langkah 1 — Salin Rombel</h5></div>
            <div class="card-body-nexus">
                <div class="d-flex gap-2 flex-wrap">
                    <span class="badge-nexus badge-info">Rombel di tahun asal: {{ $ringkasanSalin['rombel'] }}</span>
                    <span class="badge-nexus badge-info">Penugasan di tahun asal: {{ $ringkasanSalin['penugasan'] }}</span>
                    <span class="badge-nexus badge-neutral">Rombel tahun tujuan sekarang: {{ $ringkasanSalin['sudah ada'] ?? 0 }}</span>
                </div>
                <p style="font-size:12.5px;color:var(--text-muted);margin-top:8px;">
                    Rombel, wali, dan penugasan akan disalin. Anggota siswa tidak ikut disalin — itu ditangani kenaikan kelas di bawah.
                </p>
            </div>
        </div>

        <div class="card-nexus mb-3">
            <div class="card-header-nexus">
                <div><h5 class="card-title">Langkah 2 — Pemetaan Kelas</h5>
                <p class="card-subtitle mb-0">Default: tingkat berikutnya dengan huruf sama. Kelas tingkat terakhir otomatis diluluskan.</p></div>
            </div>
            <div class="card-body-nexus p-0">
                @if($errors->has('pemetaan'))
                    <div class="alert alert-danger m-3 mb-0" role="alert">{{ $errors->first('pemetaan') }}</div>
                @endif
                <div class="table-responsive"><table class="table-nexus w-100">
                    <thead><tr><th>Kelas Asal</th><th>Jumlah Siswa</th><th style="min-width:240px;">Kelas Tujuan</th></tr></thead>
                    <tbody>
                        @forelse($grup as $kelasId => $data)
                            <tr>
                                <td style="font-weight:600;font-size:13px;">
                                    {{ $data['kelas']->nama_kelas ?? '?' }}
                                    <span style="font-weight:400;font-size:11px;color:var(--text-muted);">tingkat {{ $data['kelas']->tingkat ?? '-' }}</span>
                                </td>
                                <td style="font-size:12.5px;">{{ $data['siswas']->count() }} siswa</td>
                                <td>
                                    <select name="pemetaan[{{ $kelasId }}]" class="form-select form-select-sm @error('pemetaan.'.$kelasId) is-invalid @enderror"
                                            data-pemetaan="{{ $kelasId }}"
                                            aria-label="Tujuan {{ $data['kelas']->nama_kelas ?? '' }}">
                                        <option value="">— Lewati —</option>
                                        <option value="LULUS" @selected(($pemetaan[$kelasId] ?? '') === 'LULUS')>Luluskan</option>
                                        @foreach($kelasList as $k)
                                            <option value="{{ $k->id }}" @selected((string)($pemetaan[$kelasId] ?? '') === (string)$k->id)>{{ $k->nama_kelas }} (tingkat {{ $k->tingkat }})</option>
                                        @endforeach
                                    </select>
                                    @error('pemetaan.'.$kelasId)<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                    @if(($petaOtomatis[$kelasId] ?? null) === null && (int)($data['kelas']->tingkat ?? 0) !== $tingkatAkhir)
                                        <div style="font-size:11.5px;color:var(--text-muted);">Pasangan otomatis tidak ditemukan — pilih manual.</div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-center py-4" style="color:var(--text-muted);">Tidak ada siswa aktif di tahun asal.</td></tr>
                        @endforelse
                    </tbody>
                </table></div>
            </div>
        </div>

        @if($kelasTujuan->isNotEmpty())
            <div class="card-nexus mb-3">
                <div class="card-header-nexus"><h5 class="card-title">Cek Kelas Tujuan</h5></div>
                <div class="card-body-nexus">
                    <div class="d-flex gap-2 flex-wrap">
                        @foreach($kelasTujuan as $t)
                            <span class="badge-nexus badge-{{ $t['adaRombel'] ? 'success' : 'danger' }}">
                                {{ $t['kelas'] }} {{ $t['adaRombel'] ? 'sudah ada rombel' : 'belum ada rombel' }}
                            </span>
                        @endforeach
                    </div>
                    @if($kelasTujuan->contains(fn($t) => ! $t['adaRombel']))
                        <p style="font-size:12.5px;margin-top:8px;">
                            Kelas bertanda <strong>belum ada rombel</strong> akan dibuat otomatis oleh langkah salin bila kelasnya
                            sudah ada di tahun asal. Kalau kelas itu memang belum pernah dibuat, buat dulu di menu
                            <strong>Kelas</strong> lalu jalankan ulang.
                        </p>
                    @endif
                </div>
            </div>
        @endif

        <div class="card-nexus mb-3">
            <div class="card-header-nexus">
                <h5 class="card-title">Langkah 3 — Pratinjau Berdasarkan Pemetaan</h5>
                <p class="card-subtitle mb-0">Angka di bawah mengikuti pemetaan kelas saja, belum memperhitungkan override per siswa.</p>
            </div>
            <div class="card-body-nexus p-0">
                <div class="table-responsive"><table class="table-nexus w-100">
                    <thead><tr><th>Kelas</th><th>Naik</th><th>Tinggal</th><th>Lulus</th><th>Dilewati</th></tr></thead>
                    <tbody>
                        @forelse($pratinjau as $nama => $hitung)
                            <tr>
                                <td style="font-weight:600;font-size:13px;">{{ $nama }}</td>
                                <td>{{ $hitung['naik'] }}</td>
                                <td>{{ $hitung['tinggal'] }}</td>
                                <td>{{ $hitung['lulus'] }}</td>
                                <td>{{ $hitung['dilewati'] }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center py-3" style="color:var(--text-muted);">Tidak ada siswa aktif di tahun asal.</td></tr>
                        @endforelse
                    </tbody>
                </table></div>
            </div>
        </div>

        <div class="card-nexus mb-3">
            <div class="card-header-nexus">
                <h5 class="card-title">Langkah 4 — Rincian Siswa &amp; Override</h5>
                <p class="card-subtitle mb-0">
                    Koreksi per siswa bila ada yang harus tinggal kelas atau lulus di luar pemetaan kelas.
                    <span data-ringkasan-override>{{ $jumlahOverride }}</span> override dipilih.
                </p>
            </div>
            <div class="card-body-nexus">
                @forelse($grup as $kelasId => $data)
                    <details>
                        <summary style="font-weight:600;font-size:13px;cursor:pointer;">
                            {{ $data['kelas']->nama_kelas ?? '?' }}
                            <span style="font-weight:400;font-size:11.5px;color:var(--text-muted);">{{ $data['siswas']->count() }} siswa</span>
                        </summary>
                        <div class="table-responsive mt-2">
                            <table class="table-nexus w-100">
                                <thead><tr><th style="min-width:200px;">Nama</th><th style="min-width:130px;">Hasil</th><th style="min-width:170px;">Override</th></tr></thead>
                                <tbody>
                                    @foreach($data['siswas'] as $s)
                                        @php
                                            $ov = old('override.'.$s->id, $override[$s->id] ?? 'ikuti');
                                            $tujuan = $pemetaan[$kelasId] ?? '';
                                            $efektif = $ov !== 'ikuti' ? $ov : ($tujuan === 'LULUS' ? 'lulus' : ($tujuan !== '' ? 'naik' : ''));
                                            $namaTujuan = $kelasList->firstWhere('id', (int) $tujuan)?->nama_kelas;
                                        @endphp
                                        <tr data-baris-siswa data-kelas="{{ $kelasId }}">
                                            <td style="font-weight:600;font-size:13px;">{{ $s->user->name ?? $s->calonSiswa->nama_lengkap ?? '-' }}<div style="font-size:11px;color:var(--text-muted);font-weight:400;">{{ $s->nis ?? $s->nisn ?? '-' }}</div></td>
                                            <td data-slot-hasil>
                                                @if($efektif === 'naik')<span class="badge-nexus badge-info">Naik ke {{ $namaTujuan ?? '?' }}</span>
                                                @elseif($efektif === 'lulus')<span class="badge-nexus badge-success">Lulus</span>
                                                @elseif($efektif === 'tinggal')<span class="badge-nexus badge-warning">Tinggal</span>
                                                @else<span class="badge-nexus">Dilewati</span>@endif
                                            </td>
                                            <td>
                                                @can('tahun-ajaran-baru.execute')
                                                    <select name="override[{{ $s->id }}]" class="form-select form-select-sm" data-override aria-label="Override {{ $s->nis ?? $s->id }}">
                                                        <option value="ikuti" @selected($ov==='ikuti')>Ikuti pemetaan</option>
                                                        <option value="tinggal" @selected($ov==='tinggal')>Tinggal kelas</option>
                                                        <option value="lulus" @selected($ov==='lulus')>Luluskan</option>
                                                    </select>
                                                @else
                                                    <span style="font-size:12.5px;color:var(--text-muted);">Ikuti pemetaan</span>
                                                @endcan
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </details>
                @empty
                    <p style="font-size:12.5px;color:var(--text-muted);margin-bottom:0;">Tidak ada siswa aktif di tahun asal.</p>
                @endforelse
            </div>
        </div>

        @can('tahun-ajaran-baru.execute')
            <div class="card-nexus">
                <div class="card-body-nexus d-flex justify-content-end gap-2">
                    <button type="submit" class="btn btn-primary btn-sm" data-confirm="Jalankan salin rombel lalu kenaikan kelas dari tahun #{{ $tahunAsalId }} ke #{{ $tahunTujuanId }}? Tindakan ini memindahkan siswa dan bisa dijalankan berulang dengan aman.">
                        <i class="fa-solid fa-play"></i> Jalankan Salin + Kenaikan
                    </button>
                </div>
            </div>
        @endcan
    </form>
@endif
@endsection

@push('scripts')
<script>
    (function () {
        var form = document.getElementById('form-naik-tahun');
        if (!form) { return; }

        // `kelasList` berisi seluruh kelas. Yang dipakai hanya yang jadi pilihan
        // tujuan, supaya tidak ada kelas tak relevan yang ikut terbawa ke HTML.
        var namaKelas = @json($kelasList->pluck('nama_kelas', 'id'));

        function badge(teks, jenis) {
            return '<span class="badge-nexus badge-' + jenis + '">' + teks + '</span>';
        }

        function hitungBaris(baris) {
            var selectPemetaan = form.querySelector('[data-pemetaan="' + baris.dataset.kelas + '"]');
            var selectOverride = baris.querySelector('[data-override]');
            var tujuan = selectPemetaan ? selectPemetaan.value : '';
            var aksi = (selectOverride ? selectOverride.value : 'ikuti');

            if (aksi === 'ikuti') {
                aksi = tujuan === 'LULUS' ? 'lulus' : (tujuan !== '' ? 'naik' : '');
            }

            if (aksi === 'naik') {
                return badge('Naik ke ' + (namaKelas[tujuan] || '?'), 'info');
            }
            if (aksi === 'lulus') { return badge('Lulus', 'success'); }
            if (aksi === 'tinggal') { return badge('Tinggal', 'warning'); }
            return '<span class="badge-nexus">Dilewati</span>';
        }

        function segarkanBaris(baris) {
            var slot = baris.querySelector('[data-slot-hasil]');
            if (slot) { slot.innerHTML = hitungBaris(baris); }
        }

        function segarkanKelas(kelasId) {
            form.querySelectorAll('[data-baris-siswa][data-kelas="' + kelasId + '"]').forEach(segarkanBaris);
        }

        function hitungJumlahOverride() {
            var n = 0;
            form.querySelectorAll('[data-override]').forEach(function (s) {
                if (s.value !== 'ikuti') { n++; }
            });
            var target = form.querySelector('[data-ringkasan-override]');
            if (target) { target.textContent = n; }
        }

        form.addEventListener('change', function (event) {
            var el = event.target;

            if (el.matches('[data-override]')) {
                segarkanBaris(el.closest('[data-baris-siswa]'));
                hitungJumlahOverride();
                return;
            }

            if (el.matches('[data-pemetaan]')) {
                segarkanKelas(el.dataset.pemetaan);
            }
        });
    })();
</script>
@endpush