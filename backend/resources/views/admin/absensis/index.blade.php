@extends('layouts.app')

@section('title', 'Nexus Admin — Absensi')
@section('breadcrumb', 'Absensi')

@section('content')
<div class="page-header d-flex flex-wrap align-items-start justify-content-between gap-3">
    <div>
        <h1 class="page-title">Absensi Harian</h1>
        <p class="page-subtitle mb-0">Input manual per rombel, satu simpan untuk semua</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        @can('absensis.create')
            <a href="{{ route('admin.absensis.scan', ['rombel_id' => $rombel?->id]) }}" class="btn btn-primary btn-sm"><i class="fa-solid fa-qrcode"></i> Scan QR</a>
        @endcan
    </div>
</div>

@include('layouts.partials.alert')

<div class="card-nexus">
    <div class="card-header-nexus">
        <div>
            <h5 class="card-title">Filter</h5>
            <p class="card-subtitle">Rombel tahun aktif dan tanggal pencatatan</p>
        </div>
        <form method="GET" action="{{ route('admin.absensis.index') }}" class="d-flex gap-2 flex-wrap align-items-end">
            <div>
                <label class="form-label" style="font-size:12px;" for="rombel_id">Rombel</label>
                <select name="rombel_id" id="rombel_id" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()">
                    @foreach($rombels as $r)<option value="{{ $r->id }}" @selected($rombel && $rombel->id==$r->id)>{{ $r->kelas->nama_kelas }} — {{ $r->tahunAjaran->nama_tahun_ajaran }}</option>@endforeach
                </select>
            </div>
            <div>
                <label class="form-label" style="font-size:12px;" for="tanggal">Tanggal</label>
                <input type="date" name="tanggal" id="tanggal" value="{{ $tanggal }}" max="{{ now()->toDateString() }}" class="form-control form-control-sm" style="width:auto" onchange="this.form.submit()" />
            </div>
        </form>
    </div>
</div>

<div class="card-nexus mt-3">
    <div class="card-header-nexus">
        <div>
            <h5 class="card-title">Daftar Siswa ({{ $siswas->count() }})</h5>
            <p class="card-subtitle">{{ $rombel ? $rombel->kelas->nama_kelas.' — '.$tanggal : 'Pilih rombel dulu' }}</p>
        </div>
        @can('absensis.create')
            @if($siswas->isNotEmpty())
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-nexus-outline btn-sm" data-tandai-semua="hadir"><i class="fa-solid fa-check-double"></i> Tandai semua hadir</button>
                    <button type="submit" form="form-absensi" class="btn btn-primary btn-sm"><i class="fa-solid fa-floppy-disk"></i> Simpan</button>
                </div>
            @endif
        @endcan
    </div>
    <div class="card-body-nexus p-0">
        @can('absensis.create')
            <form id="form-absensi" method="POST" action="{{ route('admin.absensis.batch') }}">
                @csrf
                <input type="hidden" name="rombel_id" value="{{ $rombel?->id }}" />
                <input type="hidden" name="tanggal" value="{{ $tanggal }}" />
            </form>
            @if($errors->has('status'))<div class="alert alert-danger m-3 mb-0" role="alert">{{ $errors->first('status') }}</div>@endif
        @endcan
        <div class="table-responsive">
            <table class="table-nexus w-100">
                <thead><tr><th>Nama</th><th>Status</th><th>Jam</th></tr></thead>
                <tbody>
                    @forelse($siswas as $s)
                        @php $catat = $tercatat->get($s->id); @endphp
                        <tr>
                            <td style="font-weight:600;font-size:13px;">{{ $s->user->name ?? '-' }}<div style="font-size:11px;color:var(--text-muted);font-weight:400;">{{ $s->nis ?? $s->nisn ?? '-' }}</div></td>
                            <td style="min-width:170px;">
                                @can('absensis.create')
                                    @php
                                        // `old()` bisa balik `null` alih-alih default-nya,
                                        // dan `null === ''` bernilai salah. Tanpa
                                        // pemrosesan ini opsi "Belum dicatat" justru tidak
                                        // terpilih sehingga browser jatuh ke "Hadir".
                                        $terpilih = (string) old('status.'.$s->id, $catat?->status ?? '');
                                    @endphp
                                    <select name="status[{{ $s->id }}]" form="form-absensi" class="form-select form-select-sm @error('status.'.$s->id) is-invalid @enderror" aria-label="Status {{ $s->nis ?? $s->id }}">
                                        @foreach(['' => 'Belum dicatat', 'hadir' => 'Hadir', 'terlambat' => 'Terlambat', 'sakit' => 'Sakit', 'izin' => 'Izin', 'alpa' => 'Alpa'] as $val => $label)
                                            <option value="{{ $val }}" @selected($terpilih === (string) $val)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    @error('status.'.$s->id)<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                @else
                                    <span class="badge-nexus badge-info">{{ ucfirst($catat?->status ?? '—') }}</span>
                                @endcan
                            </td>
                            <td style="font-size:12.5px;">{{ $catat?->jam_datang ? substr($catat->jam_datang, 0, 5) : '—' }}{{ $catat ? ' • '.($catat->metode === 'qr' ? 'QR' : 'Manual') : '' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-center py-4" style="color:var(--text-muted);">Tidak ada siswa pada rombel ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Pintasan lama "buka grid lalu Simpan berarti semua hadir" sengaja
    // dihapus karena menulis hadir tanpa pilihan guru. Tombol di bawah
    // mengembalikan jalur cepat itu sebagai aksi yang terlihat dan disengaja.
    document.querySelectorAll('[data-tandai-semua]').forEach(function (tombol) {
        tombol.addEventListener('click', function () {
            // Select berada di luar <form> dan menunjukkannya lewat atribut
            // `form`, jadi selector yang benar bukan berbasis hierarki.
            document.querySelectorAll('select[form="form-absensi"]').forEach(function (select) {
                select.value = tombol.dataset.tandaiSemua;
            });
        });
    });
</script>
@endpush
