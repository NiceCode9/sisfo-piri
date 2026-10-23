@extends('layouts.app')

@section('title', 'QR Absen Hari Ini')
@section('breadcrumb', 'QR Absen')

@section('content')
<div class="page-header d-flex flex-wrap align-items-start justify-content-between gap-3">
    <div>
        <h1 class="page-title">QR Absen Hari Ini</h1>
        <p class="page-subtitle mb-0">Tampilkan QR ini di kelas saat jam mata pelajaran pertama</p>
    </div>
</div>

@include('layouts.partials.alert', ['errorDitampilkan' => ['berkas']])

@include('guru._nav', ['tabAktif' => 'absensi-qr'])

<div class="row g-3">
    <div class="col-12 col-lg-7">
        <div class="card-nexus">
            <div class="card-header-nexus">
                <div>
                    <h5 class="card-title">QR Untuk Dipindai Siswa</h5>
                    <p class="card-subtitle mb-0">QR milik akun Anda, berlaku hari ini, berganti otomatis tiap {{ $menit_ganti }} menit</p>
                </div>
            </div>
            <div class="card-body-nexus text-center">
                {{-- SVG QR dirender server; ganti slot hanya perlu mengganti isinya --}}
                <div id="qr-guru">{!! $qrSvg !!}</div>
                <p class="mb-0 mt-3" style="font-size:13px;color:var(--text-muted);">
                    Ganti berikutnya jam <strong id="ganti-pada">sekitar {{ $menit_ganti }} menit lagi</strong><span id="ganti-gagal-muat" class="d-none"> (gagal muat, akan dicoba lagi otomatis)</span>
                </p>
            </div>
        </div>
    </div>
    <div class="col-12 col-lg-5">
        <div class="card-nexus">
            <div class="card-header-nexus"><h5 class="card-title">Jendela Absen Siswa</h5></div>
            <div class="card-body-nexus">
                <table class="table-nexus w-100" style="font-size:13px;">
                    <tbody>
                        <tr><td>Scan dibuka</td><td class="text-end"><strong>{{ $jam_buka }}</strong></td></tr>
                        <tr><td>Batas terlambat</td><td class="text-end"><strong>{{ $batas_terlambat }}</strong></td></tr>
                        <tr><td>Scan ditutup</td><td class="text-end"><strong>{{ $jam_tutup }}</strong></td></tr>
                    </tbody>
                </table>
                <p class="mb-0 mt-2" style="font-size:12.5px;color:var(--text-muted);">
                    Scan di luar jendela ditolak sistem. Siswa yang hadir setelah ditutup
                    atau yang tidak bisa scan dicatat lewat grid absensi manual seperti biasa.
                </p>
            </div>
        </div>
        <div class="card-nexus">
            <div class="card-header-nexus"><h5 class="card-title">Catatan</h5></div>
            <div class="card-body-nexus" style="font-size:13px;">
                <p class="mb-1">Siswa scan dari HP-nya sendiri; QR di kartu pelajar tidak dipakai lagi.</p>
                <p class="mb-0">QR hari ini tidak berlaku untuk esok hari, dan yang difoto tidak berumur panjang karena otomatis berganti. Setiap scan siswa tercatat atas nama Anda di baris absensinya.</p>
            </div>
        </div>
    </div>
</div>

{{-- Kartu Izin/Sakit: penandaan + bukti satu-satu (spek 2026-10-23). --}}
<div class="card-nexus mt-3" id="kartu-izin">
    <div class="card-header-nexus">
        <div>
            <h5 class="card-title">Catatan Izin/Sakit</h5>
            <p class="card-subtitle mb-0">Tandai siswa yang tidak memindai QR karena bawa surat</p>
        </div>
        @if ($rombelsIzin->count() > 1)
            <form method="GET" action="{{ route('guru.absensi-qr') }}" class="d-flex gap-1">
                <select name="rombel_id" class="form-select form-select-sm" style="max-width:160px;font-size:12px;" aria-label="Pilih kelas">
                    @foreach ($rombelsIzin as $r)
                        <option value="{{ $r->id }}" @selected($r->is($rombelIzinTerpilih))>{{ $r->kelas?->nama_kelas ?? $r->id }}</option>
                    @endforeach
                </select>
                <button class="btn btn-nexus-outline btn-sm" style="font-size:12px;">Tampilkan</button>
            </form>
        @endif
    </div>
    <div class="card-body-nexus" style="font-size:13px;">
        @if ($rombelsIzin->isEmpty())
            <p class="mb-0 text-center py-3" style="color:var(--text-muted);">Tidak ada rombel yang diampu.</p>
        @elseif ($siswaKartu->isEmpty())
            <p class="mb-0 text-center py-3" style="color:var(--text-muted);">Tidak ada siswa pada rombel ini.</p>
        @else
            <div class="table-responsive">
                <table class="table-nexus w-100">
                    <thead>
                        <tr><th>Siswa</th><th style="width:92px;">Status</th><th style="width:300px;">Tindakan</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($siswaKartu as $siswa)
                            @php $barisKartu = $absensiHariIni->get($siswa->id); @endphp
                            <tr>
                                <td>{{ $siswa->user?->name }} <span class="text-muted" style="font-size:11px;">{{ $siswa->nis }}</span></td>
                                <td>
                                    <span class="badge-nexus {{ in_array($barisKartu?->status, ['izin', 'sakit'], true) ? 'badge-info' : 'badge-neutral' }}">{{ ucfirst($barisKartu?->status ?? '—') }}</span>
                                </td>
                                <td>
                                    @if ($barisKartu === null)
                                        {{-- Belum tercatat: satu form kecil, status + bukti opsional --}}
                                        <form method="POST" action="{{ route('guru.absensi-qr.izin') }}" enctype="multipart/form-data" class="d-flex gap-1">
                                            @csrf
                                            <input type="hidden" name="rombel_id" value="{{ $rombelIzinTerpilih->id }}">
                                            <input type="hidden" name="siswa_id" value="{{ $siswa->id }}">
                                            <select name="status" class="form-select form-select-sm" style="max-width:92px;font-size:12px;" aria-label="Status {{ $siswa->nis }}">
                                                <option value="izin">Izin</option>
                                                <option value="sakit">Sakit</option>
                                            </select>
                                            <input type="file" name="berkas" accept=".jpg,.jpeg,.png,.webp" class="form-control form-control-sm @error('berkas') is-invalid @enderror" style="font-size:11px;padding:2px 4px;" aria-label="Bukti {{ $siswa->nis }}" />
                                            <button type="submit" class="btn btn-nexus-outline btn-sm" style="font-size:11px;padding:2px 8px;">Simpan</button>
                                        </form>
                                        @error('berkas')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                    @elseif (in_array($barisKartu->status, ['izin', 'sakit'], true) && ! $barisKartu->berkas_path)
                                        <form method="POST" action="{{ route('guru.absensi-qr.izin.bukti', $barisKartu) }}" enctype="multipart/form-data" class="d-flex gap-1">
                                            @csrf
                                            <input type="file" name="berkas" accept=".jpg,.jpeg,.png,.webp" required class="form-control form-control-sm @error('berkas') is-invalid @enderror" style="font-size:11px;padding:2px 4px;" aria-label="Unggah bukti {{ $siswa->nis }}" />
                                            <button type="submit" class="btn btn-nexus-outline btn-sm" style="font-size:11px;padding:2px 8px;">Unggah</button>
                                        </form>
                                        @error('berkas')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                    @elseif ($barisKartu->berkas_path)
                                        <a href="{{ route('dokumen.absensi', $barisKartu) }}" target="_blank" rel="noopener" style="font-size:11px;">Lihat bukti</a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p class="mb-0 mt-2" style="font-size:12px;color:var(--text-muted);">
                Koreksi status yang sudah tercatat lewat grid absensi; kartu ini hanya menandai siswa tanpa catatan.
            </p>
        @endif
    </div>
</div>
@endsection

@once
@push('scripts')
<script>
(function () {
    const box = document.getElementById('qr-guru');
    const gantiPada = document.getElementById('ganti-pada');
    const gagalMuat = document.getElementById('ganti-gagal-muat');
    const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    // Baca interval dari penampil server biar tidak ada dua sumber kebenaran.
    const intervalMs = ({{ $menit_ganti }}) * 60 * 1000 + 2000;

    async function gantiQrLagi() {
        try {
            const res = await fetch("{{ $refreshRoute }}", {
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
            });

            if (! res.ok) throw new Error('refresh gagal');

            const data = await res.json();

            box.innerHTML = data.qr;
            gantiPada.textContent = 'jam ' + data.ganti_pada;
            gagalMuat.classList.add('d-none');
        } catch (e) {
            gagalMuat.classList.remove('d-none');
        }

        setTimeout(gantiQrLagi, intervalMs);
    }

    setTimeout(gantiQrLagi, intervalMs);
})();
</script>
@endpush
@endonce
