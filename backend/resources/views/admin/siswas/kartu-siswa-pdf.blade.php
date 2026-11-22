<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak Kartu Pelajar (PDF)</title>
    <style>
        @page { size: A4 portrait; margin: 10mm; }
        body { font-family: DejaVu Sans, sans-serif; color: #1f2937; }
        .sheet-title { font-size: 12px; font-weight: 700; margin: 0 0 6mm; color: #1e3a8a; }
        .page-break { page-break-after: always; }
        .grid { width: 100%; }
        .card {
            display: inline-block;
            width: 85.6mm;
            height: 54mm;
            margin: 0 4mm 4mm 0;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            overflow: hidden;
            vertical-align: top;
            background: #fff;
        }
        .card:nth-child(2n) { margin-right: 0; }

        /* FRONT */
        .front-header {
            background: #2563eb;
            color: #fff;
            padding: 3mm 4mm;
            font-size: 8px;
            font-weight: 700;
            line-height: 1.1;
        }
        .front-header .sub { font-size: 6px; font-weight: 400; opacity: 0.9; letter-spacing: 1px; }
        .front-body { padding: 3mm 4mm; display: table; width: 100%; }
        .front-photo { display: table-cell; width: 22mm; vertical-align: top; }
        .front-photo img { width: 20mm; height: 24mm; object-fit: cover; border: 1px solid #f59e0b; border-radius: 4px; }
        .front-photo .placeholder { width: 20mm; height: 24mm; background: #e5e7eb; border-radius: 4px; }
        .front-info { display: table-cell; vertical-align: top; padding-left: 3mm; }
        .front-name { font-size: 10px; font-weight: 700; margin-bottom: 1mm; }
        .front-meta { font-size: 7.5px; color: #4b5563; line-height: 1.35; }
        .front-meta strong { color: #1f2937; }
        .front-footer {
            position: absolute;
            bottom: 0; left: 0; right: 0;
            background: #f59e0b;
            color: #1f2937;
            font-size: 6px;
            font-weight: 700;
            text-align: center;
            padding: 1.5mm;
            letter-spacing: 1px;
        }
        .card { position: relative; }

        /* BACK */
        .back-inner { padding: 4mm; text-align: center; height: 100%; box-sizing: border-box; }
        .back-title { font-size: 9px; font-weight: 700; color: #1e3a8a; margin-bottom: 2mm; }
        .back-qr { margin: 2mm auto; width: 26mm; height: 26mm; }
        .back-qr svg { width: 100%; height: 100%; }
        .back-text { font-size: 7px; color: #6b7280; line-height: 1.3; }
        .back-id { font-size: 6px; color: #9ca3af; margin-top: 2mm; font-family: monospace; }
    </style>
</head>
<body>
    <div class="sheet-title">Kartu Pelajar — Sisi Depan</div>
    <div class="grid">
        @foreach ($siswaList as $siswa)
            <div class="card">
                <div class="front-header">
                    {{ $sekolah?->nama_sekolah ?? 'Sekolah' }}
                    <div class="sub">KARTU PELAJAR</div>
                </div>
                <div class="front-body">
                    <div class="front-photo">
                        @if ($siswa->foto)
                            <img src="{{ $siswa->foto }}" alt="Foto">
                        @else
                            <div class="placeholder"></div>
                        @endif
                    </div>
                    <div class="front-info">
                        <div class="front-name">{{ $siswa->nama }}</div>
                        <div class="front-meta">
                            NISN: <strong>{{ $siswa->nisn }}</strong><br>
                            Kelas: <strong>{{ $siswa->kelas }}</strong><br>
                            TA: <strong>{{ $tahunAjaran ?? '-' }}</strong>
                        </div>
                    </div>
                </div>
                <div class="front-footer">SCAN QR DI BELAKANG KARTU UNTUK ABSENSI</div>
            </div>
        @endforeach
    </div>

    <div class="page-break"></div>

    <div class="sheet-title">Kartu Pelajar — Sisi Belakang</div>
    <div class="grid">
        @foreach ($siswaList as $siswa)
            <div class="card">
                <div class="back-inner">
                    <div class="back-title">Scan untuk Absensi</div>
                    <div class="back-qr">{!! $siswa->qr !!}</div>
                    <div class="back-text">
                        Arahkan kamera guru ke QR ini saat jam masuk.<br>
                        Kartu ini milik {{ $sekolah?->nama_sekolah ?? 'sekolah' }}.
                    </div>
                    <div class="back-id">{{ $siswa->nisn }}</div>
                </div>
            </div>
        @endforeach
    </div>
</body>
</html>
