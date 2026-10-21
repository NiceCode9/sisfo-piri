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

@include('layouts.partials.alert')

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
