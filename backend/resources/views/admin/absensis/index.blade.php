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

@can('absensis.create')
    <div class="card-nexus mt-3">
        <div class="card-header-nexus">
            <div>
                <h5 class="card-title">Impor dari Excel/CSV</h5>
                <p class="card-subtitle">
                    Untuk memindahkan catatan dari buku atau berkas lama. Hanya
                    mencatat yang belum ada — koreksi atas absensi yang sudah
                    tercatat tetap lewat grid supaya alasannya tercatat.
                </p>
            </div>
            <a href="{{ route('admin.absensis.impor.template') }}" class="btn btn-nexus-outline btn-sm">
                <i class="fa-solid fa-download"></i> Unduh template
            </a>
        </div>
        <div class="card-body-nexus">
            <form method="POST" action="{{ route('admin.absensis.impor') }}" enctype="multipart/form-data" class="d-flex gap-2 flex-wrap align-items-start">
                @csrf
                <input type="file" name="file" accept=".xlsx,.xls,.csv" required
                       class="form-control form-control-sm @error('file') is-invalid @enderror"
                       style="max-width:320px;" aria-label="Berkas absensi" />
                <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-file-import"></i> Impor</button>
                @error('file')<div class="invalid-feedback d-block w-100">{{ $message }}</div>@enderror
            </form>
        </div>
    </div>
@endcan

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
                <div id="wrap-alasan" class="px-4 pt-3 pb-0 d-none">
                    <label class="form-label" for="alasan" style="font-size:12px;">
                        Alasan koreksi <span class="text-danger">*</span>
                    </label>
                    <input type="text" name="alasan" id="alasan" maxlength="500"
                           class="form-control form-control-sm @error('alasan') is-invalid @enderror"
                           placeholder="Contoh: salah input, siswa menunjukkan surat dokter"
                           value="{{ old('alasan') }}" />
                    <div class="form-text" style="font-size:11px;">
                        Wajib diisi selama status siswa yang sudah tercatat berubah.
                    </div>
                    @error('alasan')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
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
                                    <select name="status[{{ $s->id }}]" form="form-absensi"
                                            data-tercatat="{{ $catat?->status ?? '' }}"
                                            class="form-select form-select-sm @error('status.'.$s->id) is-invalid @enderror" aria-label="Status {{ $s->nis ?? $s->id }}">
                                        @foreach(['' => 'Belum dicatat', 'hadir' => 'Hadir', 'terlambat' => 'Terlambat', 'sakit' => 'Sakit', 'izin' => 'Izin', 'alpa' => 'Alpa'] as $val => $label)
                                            <option value="{{ $val }}" @selected($terpilih === (string) $val)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                    @error('status.'.$s->id)<div class="invalid-feedback d-block">{{ $message }}</div>@enderror

                                    {{-- Bukti hanya bermakna untuk sakit dan izin, jadi
                                         tombolnya baru muncul setelah status itu
                                         benar-benar tercatat dan dipilih. --}}
                                    @can('absensis.create')
                                        @if($catat && in_array($catat->status, ['sakit', 'izin'], true))
                                            <form method="POST" action="{{ route('admin.absensis.bukti', $catat) }}" enctype="multipart/form-data" class="mt-1 d-flex gap-1">
                                                @csrf
                                                <input type="file" name="berkas" accept=".jpg,.jpeg,.png,.webp" required
                                                       class="form-control form-control-sm @error('berkas') is-invalid @enderror"
                                                       style="font-size:11px;padding:2px 4px;"
                                                       aria-label="Unggah bukti {{ $s->nis ?? $s->id }}" />
                                                <button type="submit" class="btn btn-nexus-outline btn-sm" style="font-size:11px;padding:2px 8px;">Unggah</button>
                                            </form>
                                            @error('berkas')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                        @endif
                                    @endcan
                                @else
                                    <span class="badge-nexus badge-info">{{ ucfirst($catat?->status ?? '—') }}</span>
                                @endcan
                            </td>
                            <td style="font-size:12.5px;">
                                {{ $catat?->jam_datang ? substr($catat->jam_datang, 0, 5) : '—' }}{{ $catat ? ' • '.($catat->metode === 'qr' ? 'QR' : 'Manual') : '' }}
                                @if($catat?->berkas_path)
                                    <div class="mt-1"><a href="{{ route('dokumen.absensi', $catat) }}" target="_blank" rel="noopener" style="font-size:11px;">Lihat bukti</a></div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-center py-4" style="color:var(--text-muted);">Tidak ada siswa pada rombel ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@if($koreksi->isNotEmpty())    <div class="card-nexus mt-3">
        <div class="card-header-nexus">
            <div>
                <h5 class="card-title">Riwayat Koreksi ({{ $koreksi->count() }})</h5>
                <p class="card-subtitle">Perubahan terhadap absensi yang sudah tercatat pada {{ $tanggal }}</p>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table-nexus w-100">
                <thead><tr><th>Siswa</th><th>Dari</th><th>Ke</th><th>Alasan</th><th>Oleh</th><th>Waktu</th></tr></thead>
                <tbody>
                    @foreach($koreksi as $k)
                        <tr>
                            <td style="font-weight:600;font-size:13px;">{{ $k->siswa?->user->name ?? '-' }}</td>
                            <td style="font-size:12.5px;">{{ ucfirst($k->status_sebelum) }}{{ $k->jam_sebelum ? ' ('.substr($k->jam_sebelum, 0, 5).')' : '' }}</td>
                            <td style="font-size:12.5px;">{{ ucfirst($k->status_sesudah) }}{{ $k->jam_sesudah ? ' ('.substr($k->jam_sesudah, 0, 5).')' : '' }}</td>
                            <td style="font-size:12.5px;">{{ $k->alasan }}</td>
                            <td style="font-size:12.5px;">{{ $k->pencatat?->name ?? 'Sistem' }}</td>
                            <td style="font-size:12px;color:var(--text-muted);white-space:nowrap;">{{ $k->created_at?->format('d/m H:i') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif
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
            perbaruiKoreksi();
        });
    });

    // Kolom alasan hanya muncul saat ada siswa yang status tercatatnya
    // diubah. Field-nya tetap kosong kalau tidak ada koreksi, jadi teacher
    // tidak perlu mengisinya saat mencatat hari biasa.
    function perbaruiKoreksi() {
        const wrap = document.getElementById('wrap-alasan');
        if (!wrap) return;

        const adaKoreksi = Array.from(
            document.querySelectorAll('select[form="form-absensi"][data-tercatat]')
        ).some(function (select) {
            return select.dataset.tercatat !== '' && select.value !== select.dataset.tercatat;
        });

        wrap.classList.toggle('d-none', !adaKoreksi);
    }

    document.querySelectorAll('select[form="form-absensi"][data-tercatat]').forEach(function (select) {
        select.addEventListener('change', perbaruiKoreksi);
    });

    // Setelah validasi gagal, alasannya wajib tampil lagi supaya guru melihat
    // apa yang harus diperbaiki.
    @error('alasan')
        document.getElementById('wrap-alasan')?.classList.remove('d-none');
    @enderror
    perbaruiKoreksi();
</script>
@endpush
