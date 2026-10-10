@extends('layouts.app')

@section('title', 'Nexus Admin — Scan Absensi')
@section('breadcrumb', 'Scan Absensi')

@section('content')
<div class="page-header d-flex flex-wrap align-items-start justify-content-between gap-3">
    <div>
        <h1 class="page-title">Scan QR Absensi</h1>
        <p class="page-subtitle mb-0">Arahkan kamera device sekolah ke kartu siswa</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('admin.absensis.index') }}" class="btn btn-nexus-outline btn-sm"><i class="fa-solid fa-arrow-left"></i> Input Manual</a>
    </div>
</div>

@include('layouts.partials.alert')

<div class="card-nexus">
    <div class="card-header-nexus">
        <div>
            <h5 class="card-title">Rombel</h5>
            <p class="card-subtitle">Hasil scan tercatat untuk rombel ini</p>
        </div>
        <select id="scan-rombel" class="form-select form-select-sm" style="width:auto" aria-label="Rombel">
            @foreach($rombels as $r)<option value="{{ $r->id }}" @selected($rombelId==$r->id)>{{ $r->kelas->nama_kelas }} — {{ $r->tahunAjaran->nama_tahun_ajaran }}</option>@endforeach
        </select>
    </div>
    <div class="card-body-nexus">
        @if($rombels->isEmpty())
            {{-- Tanpa rombel terjangkau, dropdown kosong dan JS akan mengirim
                 rombel_id kosong yang berakhir sebagai "Gagal mencatat." yang
                 tidak menjelaskan apa pun. --}}
            <div class="alert alert-warning mb-3" role="alert">
                <strong>Belum ada rombel yang bisa dipindai.</strong>
                Anda hanya bisa mencatat kehadiran untuk rombel yang Anda wali
                atau ampu. Hubungi admin bila ini keliru.
            </div>
        @endif
        <div id="kamera-gagal" class="alert alert-warning d-none" role="alert">
            Kamera tidak dapat diakses. Gunakan form token manual di bawah.
        </div>
        <div id="hasil-scan" class="alert d-none" role="alert"></div>
        <div class="row g-3">
            <div class="col-12 col-md-6">
                <div id="qr-reader" style="max-width:420px;"></div>
            </div>
            <div class="col-12 col-md-6">
                <h6 style="font-size:13px;font-weight:700;">Token manual (fallback QR rusak)</h6>
                <form id="form-token" class="d-flex gap-2">
                    <input type="text" id="token-manual" class="form-control form-control-sm" placeholder="Tempel token QR…" autocomplete="off" />
                    <button type="submit" class="btn btn-primary btn-sm">Catat</button>
                </form>
                <div class="mt-3" style="font-size:12.5px;color:var(--text-muted);">
                    <div>Terakhir: <strong id="terakhir-nama">—</strong></div>
                    <div>Tercatat hari ini: <strong id="tercatat-jumlah">0</strong></div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
(function () {
    const hasil = document.getElementById('hasil-scan');
    const rombelSelect = document.getElementById('scan-rombel');
    const terakhirNama = document.getElementById('terakhir-nama');
    const tercatatJumlah = document.getElementById('tercatat-jumlah');
    const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    // Jeda setelah satu kartu tercatat. Tanpa ini kamera tetap memindai kartu
    // yang masih di dalam frame — html5-qrcode memanggil callback tiap frame
    // yang berhasil dibaca — sehingga satu kartu bisa menghasilkan puluhan
    // POST identik.
    const jedaScanMs = 2500;
    const maksAntrean = 20;

    let antrean = [];
    let memproses = false;
    let jumlah = 0;
    let jedaSelesai = 0;

    function tampilkan(ok, pesan) {
        hasil.classList.remove('d-none', 'alert-success', 'alert-danger');
        hasil.classList.add(ok ? 'alert-success' : 'alert-danger');
        hasil.textContent = pesan;
    }

    async function kirim(token) {
        const res = await fetch("{{ route('admin.absensis.scan.store') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrf,
            },
            body: JSON.stringify({ token: token.trim(), rombel_id: rombelSelect.value }),
        });

        return { ok: res.ok, data: await res.json() };
    }

    async function catat(token) {
        if (! token) return;

        if (memproses) {
            // Versi lama membuang scan yang datang saat request masih jalan.
            // Di gerbang sekolah itu berarti satu siswa hilang dari daftar
            // tanpa umpan balik apa pun, jadi tokennya diantrekan.
            if (antrean.length < maksAntrean) {
                antrean.push(token);
            }

            return;
        }

        memproses = true;

        try {
            const { ok, data } = await kirim(token);

            if (!ok) {
                tampilkan(false, data.message || 'Gagal mencatat.');
            } else {
                tampilkan(true, data.nama + ' (' + data.nis + ') — ' + data.status + ' ' + (data.jam || '').substring(0, 5));
                terakhirNama.textContent = data.nama;

                if (data.baru) {
                    jumlah++;
                    tercatatJumlah.textContent = jumlah;
                }

                jedaPemindai();
            }
        } catch (e) {
            tampilkan(false, 'Jaringan bermasalah, coba lagi.');
        }

        memproses = false;

        if (antrean.length) {
            jedaSelesai = 0;
            const berikutnya = antrean.shift();

            await new Promise(function (selesai) { setTimeout(selesai, 400); });
            catat(berikutnya);
        }
    }

    document.getElementById('form-token').addEventListener('submit', function (e) {
        e.preventDefault();
        const input = document.getElementById('token-manual');
        catat(input.value);
        input.value = '';
        input.focus();
    });

    if (!rombelSelect.value) {
        // Tidak ada rombel terjangkau: jangan nyalakan kamera, karena setiap
        // scan pasti ditolak dengan pesan yang tidak menjelaskan masalahnya.
        return;
    }

    if (typeof Html5Qrcode === 'undefined') {
        document.getElementById('kamera-gagal').classList.remove('d-none');
        return;
    }

    const pemindai = new Html5Qrcode('qr-reader');

    // `pause()` hanya menahan decode; kamera tetap hidup dan pemindai otomatis
    // lanjut lagi setelah jeda, jadi tidak ada alur untuk lupa dinyalakan lagi.
    function jedaPemindai() {
        jedaSelesai = Date.now() + jedaScanMs;

        pemindai.pause()
            .then(function () {
                setTimeout(function () {
                    pemindai.resume().catch(function () {});
                }, jedaScanMs);
            })
            .catch(function () {});
    }

    pemindai.start({ facingMode: 'environment' }, { fps: 10, qrbox: 250 }, function (token) {
        if (Date.now() < jedaSelesai) return;
        catat(token);
    })
        .catch(function () {
            document.getElementById('kamera-gagal').classList.remove('d-none');
        });
})();
</script>
@endpush
