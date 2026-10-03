@extends('layouts.app')

@section('title', 'Nexus Admin — Tahun Ajaran Baru')
@section('breadcrumb', 'Tahun Ajaran Baru')

@section('content')
<div class="page-header">
    <h1 class="page-title">Tahun Ajaran Baru</h1>
    <p class="page-subtitle mb-0">Bentuk rombel tahun tujuan, lalu pindahkan siswa dalam satu langkah</p>
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
                Wizard ini menjalankan dua langkah yang biasanya harus dilakukan manual dan bisa salah urutan:
                <strong>salin rombel</strong> (rombel + wali + penugasan) lalu <strong>kenaikan kelas</strong> (memindahkan siswa).
                Kenaikan tidak akan dijalankan bila ada kelas tujuan yang belum punya rombel — kalau tidak, siswa akan
                menunjuk kelas yang tidak ada dan lenyap dari rekap absensi, nilai tugas, dan nilai ujian.
            </div>
        </div>
    </div>

    <form method="GET" action="{{ route('admin.tahun-ajaran-baru.index') }}" class="card-nexus mb-3">
        <div class="card-body-nexus">
            <div class="d-flex gap-3 flex-wrap align-items-end">
                <div>
                    <label class="form-label" style="font-size:12px;">Dari Tahun Ajaran</label>
                    <select name="tahun_asal_id" class="form-select form-select-sm" onchange="this.form.submit()">
                        @foreach($tahunAjarans as $t)
                            <option value="{{ $t->id }}" @selected($t->id === $tahunAsalId)>{{ $t->nama_tahun_ajaran }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label" style="font-size:12px;">Ke Tahun Ajaran</label>
                    <select name="tahun_tujuan_id" class="form-select form-select-sm" onchange="this.form.submit()">
                        @foreach($tahunAjarans as $t)
                            <option value="{{ $t->id }}" @selected($t->id === $tahunTujuanId)>{{ $t->nama_tahun_ajaran }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </form>

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

    <form method="POST" action="{{ route('admin.tahun-ajaran-baru.proses') }}">
        @csrf
        <input type="hidden" name="tahun_asal_id" value="{{ $tahunAsalId }}">
        <input type="hidden" name="tahun_tujuan_id" value="{{ $tahunTujuanId }}">

        <div class="card-nexus mb-3">
            <div class="card-header-nexus">
                <div><h5 class="card-title">Langkah 2 — Pemetaan Kelas</h5>
                <p class="card-subtitle mb-0">Default: tingkat berikutnya dengan huruf sama. Kelas tingkat terakhir otomatis diluluskan.</p></div>
            </div>
            <div class="card-body-nexus p-0">
                <div class="table-responsive"><table class="table-nexus w-100">
                    <thead><tr><th>Kelas Asal</th><th>Jumlah Siswa</th><th>Kelas Tujuan</th></tr></thead>
                    <tbody>
                        @foreach($grup as $kelasId => $data)
                            <tr>
                                <td style="font-weight:600;font-size:13px;">{{ $data['kelas']->nama_kelas ?? '?' }}</td>
                                <td style="font-size:12.5px;">{{ $data['siswas']->count() }}</td>
                                <td>
                                    <select name="pemetaan[{{ $kelasId }}]" class="form-select form-select-sm">
                                        <option value="">— Lewati —</option>
                                        <option value="LULUS" @selected(($pemetaan[$kelasId] ?? '') === 'LULUS')>Luluskan</option>
                                        @foreach(\App\Models\Kelas::orderBy('tingkat')->orderBy('nama_kelas')->get() as $k)
                                            <option value="{{ $k->id }}" @selected((string)($pemetaan[$kelasId] ?? '') === (string)$k->id)>{{ $k->nama_kelas }}</option>
                                        @endforeach
                                    </select>
                                </td>
                            </tr>
                        @endforeach
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
            <div class="card-header-nexus"><h5 class="card-title">Langkah 3 — Pratinjau Kenaikan</h5></div>
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

        @can('tahun-ajaran-baru.execute')
            <div class="card-nexus">
                <div class="card-body-nexus d-flex justify-content-end gap-2">
                    <a href="{{ route('admin.kenaikan.index', ['tahun_asal_id' => $tahunAsalId, 'tahun_tujuan_id' => $tahunTujuanId]) }}" class="btn btn-nexus-outline btn-sm">Atur Kenaikan Detail</a>
                    <button type="submit" class="btn btn-primary btn-sm" data-confirm="Jalankan salin rombel lalu kenaikan kelas untuk {{ $tahunAsalId }} ke {{ $tahunTujuanId }}? Tindakan ini memindahkan siswa dan bisa dijalankan berulang dengan aman.">
                        <i class="fa-solid fa-play"></i> Jalankan Salin + Kenaikan
                    </button>
                </div>
            </div>
        @endcan
    </form>
@endif
@endsection