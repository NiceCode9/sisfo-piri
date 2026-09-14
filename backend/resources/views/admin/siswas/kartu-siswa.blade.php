<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Kartu Pelajar</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --brand-primary: #2563eb;
            --brand-primary-dark: #1e3a8a;
            --brand-secondary: #f59e0b;
            --brand-accent: #10b981;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: #f1f5f9;
        }

        /* Ukuran kartu standar CR80 (kartu pelajar/ID card): 85.6mm x 54mm */
        .id-card-wrapper {
            perspective: 1200px;
            width: 337px;  /* ~85.6mm pada 96dpi screen preview */
            height: 212px; /* ~54mm */
        }

        .id-card {
            position: relative;
            width: 100%;
            height: 100%;
            transition: transform 0.6s;
            transform-style: preserve-3d;
            cursor: pointer;
        }

        .id-card.is-flipped {
            transform: rotateY(180deg);
        }

        .id-card-face {
            position: absolute;
            inset: 0;
            backface-visibility: hidden;
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
            background: #fff;
        }

        .id-card-back {
            transform: rotateY(180deg);
        }

        /* ===== FRONT ===== */
        .card-front-header {
            background: linear-gradient(135deg, var(--brand-primary) 0%, var(--brand-primary-dark) 100%);
            padding: 10px 14px;
            display: flex;
            align-items: center;
            gap: 8px;
            color: #fff;
        }

        .card-front-header img.logo {
            width: 28px;
            height: 28px;
            object-fit: contain;
            background: #fff;
            border-radius: 6px;
            padding: 2px;
        }

        .card-front-header .school-name {
            font-size: 10px;
            font-weight: 700;
            line-height: 1.1;
        }

        .card-front-header .card-label {
            font-size: 7.5px;
            letter-spacing: 1.5px;
            opacity: 0.85;
        }

        .card-photo {
            width: 68px;
            height: 82px;
            object-fit: cover;
            border-radius: 8px;
            border: 2px solid var(--brand-secondary);
            background: #e5e7eb;
            flex-shrink: 0;
        }

        .card-body-front {
            padding: 12px 14px;
        }

        .siswa-name {
            font-size: 13px;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 2px;
            line-height: 1.2;
        }

        .siswa-meta {
            font-size: 9px;
            color: #6b7280;
        }

        .siswa-meta strong {
            color: #374151;
        }

        .card-front-footer {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: var(--brand-accent);
            color: #fff;
            font-size: 7px;
            padding: 4px 14px;
            display: flex;
            justify-content: space-between;
        }

        /* ===== BACK ===== */
        .card-back-inner {
            padding: 14px;
            height: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
        }

        .qr-box {
            background: #fff;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            padding: 6px;
            display: inline-block;
        }

        .qr-box img, .qr-box svg {
            width: 100px;
            height: 100px;
            display: block;
        }

        .qr-caption {
            font-size: 8.5px;
            font-weight: 600;
            color: var(--brand-primary-dark);
            margin-top: 6px;
            letter-spacing: 0.5px;
        }

        .card-back-rules {
            font-size: 6.5px;
            color: #9ca3af;
            margin-top: 8px;
            line-height: 1.4;
        }

        .card-back-footer {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: var(--brand-primary-dark);
            color: #fff;
            font-size: 6.5px;
            padding: 4px 14px;
            text-align: center;
        }

        .flip-hint {
            font-size: 11px;
            color: #6b7280;
        }

        /* ===== PRINT ===== */
        @media print {
            body {
                background: #fff;
            }
            .no-print {
                display: none !important;
            }
            .id-card {
                transform: none !important;
                transition: none !important;
            }
            .id-card-back {
                transform: none !important;
                position: static;
            }
            .id-card-face {
                position: static !important;
                box-shadow: none !important;
                page-break-inside: avoid;
            }
            .print-pair {
                display: flex !important;
                gap: 6mm;
                margin-bottom: 6mm;
                page-break-inside: avoid;
            }
            .id-card-wrapper {
                cursor: default;
            }
        }
    </style>
</head>
<body>

    @php
        $schoolName = $sekolah->nama_sekolah ?? 'SMK Negeri 1 Ngaglik';
        $schoolLogo = $sekolah?->logo_path ? Storage::disk('public')->url($sekolah->logo_path) : null;
        $tahunAjaran = $tahunAjaran ?? date('Y').'/'.(date('Y') + 1);
        $siswaList = $siswaList ?? collect();
        $qrTersedia = class_exists(\SimpleSoftwareIO\QrCode\Facades\QrCode::class);
    @endphp

    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4 no-print">
            <div>
                <h1 class="h4 fw-bold mb-1">Cetak Kartu Pelajar</h1>
                <p class="text-muted small mb-0">Klik kartu untuk membalik (preview) &middot; QR code di belakang dipakai untuk absensi</p>
            </div>
            <button type="button" class="btn btn-primary" onclick="window.print()">
                Cetak Kartu
            </button>
        </div>

        <div class="row row-cols-1 row-cols-md-2 g-4">
            @foreach ($siswaList as $siswa)
                <div class="col print-pair-wrapper">
                    <div class="d-flex flex-wrap gap-3 print-pair">

                        {{-- ===== KARTU (depan + belakang, flip di layar) ===== --}}
                        <div class="id-card-wrapper js-flip-card">
                            <div class="id-card">

                                {{-- FRONT --}}
                                <div class="id-card-face id-card-front">
                                    <div class="card-front-header">
                                        @if($schoolLogo)
                                            <img src="{{ $schoolLogo }}" alt="Logo" class="logo">
                                        @else
                                            <div class="logo d-flex align-items-center justify-content-center fw-bold" style="color: var(--brand-primary);">{{ strtoupper(mb_substr($schoolName, 0, 1)) }}</div>
                                        @endif
                                        <div>
                                            <div class="school-name">{{ $schoolName }}</div>
                                            <div class="card-label">KARTU PELAJAR</div>
                                        </div>
                                    </div>

                                    <div class="card-body-front d-flex gap-3">
                                        @if(!empty($siswa->foto))
                                            <img src="{{ $siswa->foto }}" alt="Foto {{ $siswa->nama }}" class="card-photo">
                                        @else
                                            <div class="card-photo d-flex align-items-center justify-content-center fw-bold fs-4 text-secondary">{{ strtoupper(mb_substr($siswa->nama, 0, 1)) }}</div>
                                        @endif
                                        <div class="pt-1">
                                            <div class="siswa-name">{{ $siswa->nama }}</div>
                                            <div class="siswa-meta mb-1">NISN: <strong>{{ $siswa->nisn }}</strong></div>
                                            <div class="siswa-meta mb-1">Kelas: <strong>{{ $siswa->kelas }}</strong></div>
                                            <div class="siswa-meta">T.A. <strong>{{ $tahunAjaran }}</strong></div>
                                        </div>
                                    </div>

                                    <div class="card-front-footer">
                                        <span>Berlaku selama menjadi siswa aktif</span>
                                    </div>
                                </div>

                                {{-- BACK --}}
                                <div class="id-card-face id-card-back">
                                    <div class="card-back-inner">
                                        <div class="qr-box">
                                            @if($qrTersedia)
                                                {!! \SimpleSoftwareIO\QrCode\Facades\QrCode::size(150)->generate($siswa->qr_token ?? $siswa->nisn) !!}
                                            @else
                                                <div class="d-flex align-items-center justify-content-center fw-bold" style="width:100px;height:100px;font-size:11px;color:var(--brand-primary-dark);">{{ $siswa->nisn }}</div>
                                            @endif
                                        </div>
                                        <div class="qr-caption">SCAN UNTUK ABSENSI</div>
                                        <div class="card-back-rules">
                                            Kartu ini milik {{ $schoolName }}.<br>
                                            Jika ditemukan, harap dikembalikan ke bagian Tata Usaha sekolah.
                                        </div>
                                    </div>
                                    <div class="card-back-footer">
                                        NISN: {{ $siswa->nisn }} &middot; {{ $schoolName }}
                                    </div>
                                </div>

                            </div>
                        </div>

                        <div class="flip-hint no-print d-flex align-items-center">
                            👆 Klik kartu untuk lihat sisi belakang
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <script>
        document.querySelectorAll('.js-flip-card').forEach((wrapper) => {
            wrapper.addEventListener('click', () => {
                wrapper.querySelector('.id-card').classList.toggle('is-flipped');
            });
        });
    </script>

</body>
</html>
