@extends('layouts.app')

@section('title', 'Nexus Admin — Kenaikan Kelas')
@section('breadcrumb', 'Kenaikan Kelas')

@section('content')
<div class="page-header d-flex flex-wrap align-items-start justify-content-between gap-3">
    <div>
        <h1 class="page-title">Kenaikan Kelas & Kelulusan</h1>
        <p class="page-subtitle mb-0">Langkah 1: pilih periode — Langkah 2: petakan kelas dan pratinjau — Langkah 3: proses</p>
    </div>
</div>

@include('layouts.partials.alert')

<div class="card-nexus">
    <div class="card-header-nexus">
        <div>
            <h5 class="card-title">Langkah 1 — Periode</h5>
            <p class="card-subtitle">Tahun asal siswa aktif dan tahun tujuan riwayat baru</p>
        </div>
        <form method="GET" action="{{ route('admin.kenaikan.index') }}" class="d-flex gap-2 flex-wrap align-items-end">
            <div>
                <label class="form-label" style="font-size:12px;" for="tahun_asal_id">Tahun Asal</label>
                <select name="tahun_asal_id" id="tahun_asal_id" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()">
                    @foreach($tahunAjarans as $t)<option value="{{ $t->id }}" @selected($tahunAsalId==$t->id)>{{ $t->nama_tahun_ajaran }}{{ $tahunAktif && $tahunAktif->id==$t->id ? ' (aktif)' : '' }}</option>@endforeach
                </select>
            </div>
            <div>
                <label class="form-label" style="font-size:12px;" for="tahun_tujuan_id">Tahun Tujuan</label>
                <select name="tahun_tujuan_id" id="tahun_tujuan_id" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()">
                    @foreach($tahunAjarans as $t)<option value="{{ $t->id }}" @selected($tahunTujuanId==$t->id)>{{ $t->nama_tahun_ajaran }}</option>@endforeach
                </select>
            </div>
        </form>
    </div>
</div>

@if(isset($grup))
<form method="POST" action="{{ route('admin.kenaikan.proses') }}">
    @csrf
    <input type="hidden" name="tahun_asal_id" value="{{ $tahunAsalId }}" />
    <input type="hidden" name="tahun_tujuan_id" value="{{ $tahunTujuanId }}" />

    <div class="card-nexus mt-3">
        <div class="card-header-nexus">
            <div>
                <h5 class="card-title">Langkah 2 — Pemetaan Kelas</h5>
                <p class="card-subtitle">Tujuan tiap kelas asal, terisi otomatis bila pasangannya ada</p>
            </div>
        </div>
        <div class="card-body-nexus p-0">
            <div class="table-responsive">
                <table class="table-nexus w-100">
                    <thead><tr><th>Kelas Asal</th><th>Jumlah</th><th style="min-width:240px;">Tujuan</th></tr></thead>
                    <tbody>
                        @forelse($grup as $kelasId => $g)
                            @php $petaAwal = old('pemetaan.'.$kelasId, $petaOtomatis[$kelasId] ?? ''); @endphp
                            <tr>
                                <td style="font-weight:600;font-size:13px;">{{ $g['kelas']->nama_kelas ?? '-' }} <span style="font-weight:400;font-size:11px;color:var(--text-muted);">tingkat {{ $g['kelas']->tingkat ?? '-' }}</span></td>
                                <td style="font-size:13px;">{{ $g['siswas']->count() }} siswa</td>
                                <td>
                                    <select name="pemetaan[{{ $kelasId }}]" class="form-select form-select-sm @error('pemetaan.'.$kelasId) is-invalid @enderror" aria-label="Tujuan {{ $g['kelas']->nama_kelas ?? '' }}">
                                        <option value="">— Tanpa tujuan (dilewati) —</option>
                                        <option value="LULUS" @selected($petaAwal==='LULUS')>Luluskan</option>
                                        @foreach($kelasList as $k)<option value="{{ $k->id }}" @selected((string) $petaAwal === (string) $k->id)>{{ $k->nama_kelas }} (tingkat {{ $k->tingkat }})</option>@endforeach
                                    </select>
                                    @error('pemetaan.'.$kelasId)<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                    @if(($petaOtomatis[$kelasId] ?? null) === null && (int) ($g['kelas']->tingkat ?? 0) !== $tingkatAkhir)
                                        <div style="font-size:11.5px;color:var(--text-muted);">Pasangan otomatis tidak ditemukan — pilih manual.</div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-center py-4" style="color:var(--text-muted);">Tidak ada siswa aktif pada tahun asal ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card-nexus mt-3">
        <div class="card-header-nexus">
            <div>
                <h5 class="card-title">Langkah 3 — Pratinjau & Proses</h5>
                <p class="card-subtitle">Override per siswa bila ada yang tinggal kelas atau lulus di luar pemetaan</p>
            </div>
            @can('kenaikan-kelas.execute')
                @if(collect($grup)->isNotEmpty())
                    <button type="submit" class="btn btn-primary btn-sm" data-confirm="Proses kenaikan sesuai pemetaan dan override?"><i class="fa-solid fa-arrow-up"></i> Proses</button>
                @endif
            @endcan
        </div>
        <div class="card-body-nexus p-0">
            @if($errors->has('pemetaan'))<div class="alert alert-danger m-3 mb-0" role="alert">{{ $errors->first('pemetaan') }}</div>@endif
            @foreach($grup as $kelasId => $g)
                @php $petaBaris = old('pemetaan.'.$kelasId, $petaOtomatis[$kelasId] ?? ''); @endphp
                <div class="px-3 pt-3 pb-1" style="font-weight:700;font-size:13px;">{{ $g['kelas']->nama_kelas ?? '-' }} ({{ $g['siswas']->count() }})</div>
                <div class="table-responsive">
                    <table class="table-nexus w-100">
                        <thead><tr><th>Nama</th><th>Hasil</th><th style="min-width:180px;">Override</th></tr></thead>
                        <tbody>
                            @foreach($g['siswas'] as $s)
                                @php
                                    $ov = old('override.'.$s->id, 'ikuti');
                                    $efektif = $ov !== 'ikuti' ? $ov : ($petaBaris === 'LULUS' ? 'lulus' : ($petaBaris ? 'naik' : '-'));
                                    $namaTujuan = $kelasList->firstWhere('id', (int) $petaBaris)?->nama_kelas;
                                @endphp
                                <tr>
                                    <td style="font-weight:600;font-size:13px;">{{ $s->user->name ?? $s->calonSiswa->nama_lengkap ?? '-' }}<div style="font-size:11px;color:var(--text-muted);font-weight:400;">{{ $s->nis ?? $s->nisn ?? '-' }}</div></td>
                                    <td>
                                        @if($efektif==='naik')<span class="badge-nexus badge-info">Naik ke {{ $namaTujuan }}</span>
                                        @elseif($efektif==='lulus')<span class="badge-nexus badge-success">Lulus</span>
                                        @elseif($efektif==='tinggal')<span class="badge-nexus badge-warning">Tinggal</span>
                                        @else<span class="badge-nexus">Dilewati</span>@endif
                                    </td>
                                    <td>
                                        @can('kenaikan-kelas.execute')
                                            <select name="override[{{ $s->id }}]" class="form-select form-select-sm" aria-label="Override {{ $s->nis ?? $s->id }}">
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
            @endforeach
        </div>
    </div>
</form>
@endif
@endsection
