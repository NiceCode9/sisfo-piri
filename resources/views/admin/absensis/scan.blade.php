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
    let memproses = false;
    let jumlah = 0;

    function tampilkan(ok, pesan) {
        hasil.classList.remove('d-none', 'alert-success', 'alert-danger');
        hasil.classList.add(ok ? 'alert-success' : 'alert-danger');
        hasil.textContent = pesan;
    }

    async function catat(token) {
        if (memproses || !token) return;
        memproses = true;
        try {
            const res = await fetch("{{ route('admin.absensis.scan.store') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                },
                body: JSON.stringify({ token: token.trim(), rombel_id: rombelSelect.value }),
            });
            const data = await res.json();
            if (!res.ok) {
                tampilkan(false, data.message || 'Gagal mencatat.');
            } else {
                tampilkan(true, data.nama + ' (' + data.nis + ') — ' + data.status + ' ' + (data.jam || '').substring(0, 5));
                terakhirNama.textContent = data.nama;
                if (data.baru) {
                    jumlah++;
                    tercatatJumlah.textContent = jumlah;
                }
            }
        } catch (e) {
            tampilkan(false, 'Jaringan bermasalah, coba lagi.');
        }
        memproses = false;
    }

    document.getElementById('form-token').addEventListener('submit', function (e) {
        e.preventDefault();
        const input = document.getElementById('token-manual');
        catat(input.value);
        input.value = '';
        input.focus();
    });

    if (typeof Html5Qrcode === 'undefined') {
        document.getElementById('kamera-gagal').classList.remove('d-none');
        return;
    }

    const pemindai = new Html5Qrcode('qr-reader');
    pemindai.start({ facingMode: 'environment' }, { fps: 10, qrbox: 250 }, catat)
        .catch(function () {
            document.getElementById('kamera-gagal').classList.remove('d-none');
        });
})();
</script>
@endpush
