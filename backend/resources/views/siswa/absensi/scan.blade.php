@extends('layouts.app')

@section('title', 'Absen Mandiri')
@section('breadcrumb', 'Absen Mandiri')

@section('content')
<div class="page-header d-flex flex-wrap align-items-start justify-content-between gap-3">
    <div>
        <h1 class="page-title">Absen Mandiri</h1>
        <p class="page-subtitle mb-0">Scan QR yang ditampilkan guru di kelas jam pertama</p>
    </div>
</div>

@include('layouts.partials.alert')

@include('siswa._nav', ['tabAktif' => 'absen'])

<div class="row g-3">
    <div class="col-12 col-lg-7">
        <div class="card-nexus">
            <div class="card-header-nexus">
                <div>
                    <h5 class="card-title">Scan QR Guru</h5>
                    <p class="card-subtitle">Jendela absen {{ $jam_buka }} sampai {{ $jam_tutup }}; lewat {{ $batas_terlambat }} tercatat terlambat</p>
                </div>
            </div>
            <div class="card-body-nexus">
                @if($tercatat)
                    {{-- Baris hari ini sudah ada (hasil scan sebelumnya atau catatan
                         manual guru): kamera tetap menyala, tapi keputusan yang
                         berbeda perlu lewat guru agar ada jejak koreksinya. --}}
                    <div class="alert alert-info mb-3" role="alert">
                        Absensi hari ini sudah tercatat: <strong>{{ $tercatat->status }}</strong>
                        @if($tercatat->jam_datang)
                            jam {{ substr((string) $tercatat->jam_datang, 0, 5) }}
                        @endif
                        ({{ $tercatat->metode === 'manual' ? 'dicatat guru' : 'hasil scan' }}).
                    </div>
                @endif

                <div id="kamera-gagal" class="alert alert-warning d-none" role="alert">
                    Kamera tidak dapat diakses. Izinkan akses kamera di browser, atau
                    minta guru mencatat manual bila HP tidak bisa memindai.
                </div>
                <div id="hasil-scan" class="alert d-none" role="alert" aria-live="polite"></div>
                <div id="qr-reader" style="max-width:420px;"></div>
            </div>
        </div>
    </div>
    <div class="col-12 col-lg-5">
        <div class="card-nexus">
            <div class="card-header-nexus"><h5 class="card-title">Cara Pakai</h5></div>
            <div class="card-body-nexus" style="font-size:13.5px;">
                <p>1. Datang ke kelas saat guru jam pertama menampilkan QR di layar.</p>
                <p>2. Tekan kamera di sini lalu arahkan ke QR guru.</p>
                <p>3. Status kehadiran muncul di layar ini.</p>
                <hr>
                <p class="mb-1">Jam HP tidak dipakai sistem; keputusan hadir atau terlambat memakai jam server.</p>
                <p class="mb-0">QR guru berganti setiap beberapa menit, jadi yang difoto untuk disebarkan ke teman tidak akan berlaku lama.</p>
            </div>
        </div>
        <div class="card-nexus">
            <div class="card-header-nexus"><h5 class="card-title">Data Kamu</h5></div>
            <div class="card-body-nexus" style="font-size:13.5px;">
                <div>Nama: <strong>{{ $nama }}</strong></div>
                <div>Kelas berjalan: <strong>{{ $kelas ?? 'belum terdaftar' }}</strong></div>
            </div>
        </div>
    </div>
</div>
@endsection

@once
@push('scripts')
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
(function () {
    const hasil = document.getElementById('hasil-scan');
    const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    // Kembar alur tambung scan piket lama: html5-qrcode memanggil callback
    // tiap frame yang berhasil dibaca, jadi permintaan identik harus
    // ditalangi lewat jeda dan antrean tanpa membuang scan yang sah.
    const jedaScanMs = 2500;

    let memproses = false;
    let jedaSelesai = 0;

    function tampilkan(ok, pesan) {
        hasil.classList.remove('d-none', 'alert-success', 'alert-danger', 'alert-warning');
        hasil.classList.add(ok ? 'alert-success' : 'alert-warning');
        hasil.textContent = pesan;
    }

    async function kirim(token) {
        const res = await fetch("{{ route('siswa.absensi.scan.store') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrf,
            },
            body: JSON.stringify({ token: token.trim() }),
        });

        return { ok: res.ok, data: await res.json().catch(() => ({})) };
    }

    async function catat(token) {
        if (! token || memproses || Date.now() < jedaSelesai) return;

        memproses = true;
        tampilkan(false, 'Mengirim ke server…');

        try {
            const { ok, data } = await kirim(token);

            tampilkan(ok, data.message || (ok ? 'Tercatat.' : 'Gagal mencatat.'));
        } catch (e) {
            tampilkan(false, 'Jaringan bermasalah, coba lagi.');
        }

        // Jeda terlepas dari hasil: tanpa ini kamera yang terus membaca token
        // sama mengundang loop permintaan hingga menabrak rate limit.
        memproses = false;
        jedaSelesai = Date.now() + jedaScanMs;
    }

    if (typeof Html5Qrcode === 'undefined') {
        document.getElementById('kamera-gagal').classList.remove('d-none');
        return;
    }

    const pemindai = new Html5Qrcode('qr-reader');

    pemindai.start({ facingMode: 'environment' }, { fps: 10, qrbox: 280 }, catat)
        .catch(function () {
            document.getElementById('kamera-gagal').classList.remove('d-none');
        });
})();
</script>
@endpush
@endonce
