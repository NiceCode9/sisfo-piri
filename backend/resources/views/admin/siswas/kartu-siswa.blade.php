<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Kartu Pelajar</title>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --brand-primary: #2563eb;
            --brand-primary-dark: #1e3a8a;
            --brand-secondary: #f59e0b;
            --brand-accent: #047857;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: 'Poppins', Arial, sans-serif;
            background: #e2e8f0;
            color: #1f2937;
        }

        /* ===== TOOLBAR (screen only) ===== */
        .toolbar {
            position: sticky;
            top: 0;
            z-index: 10;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: center;
            gap: 10px 16px;
            padding: 12px 16px;
            background: #fff;
            border-bottom: 1px solid #cbd5e1;
        }

        .toolbar h1 {
            margin: 0;
            font-size: 16px;
            font-weight: 700;
        }

        .toolbar .hint {
            margin: 0;
            font-size: 12px;
            color: #64748b;
        }

        .btn-print {
            border: 0;
            border-radius: 8px;
            background: var(--brand-primary);
            color: #fff;
            font: 600 14px 'Poppins', Arial, sans-serif;
            padding: 12px 18px;
            min-height: 44px;
            cursor: pointer;
        }

        .btn-print:hover {
            background: var(--brand-primary-dark);
        }

        /* ===== SHEET ===== */
        .preview {
            padding: 20px 12px 40px;
        }

        .paper {
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto 20px;
            padding: 8mm;
            background: #fff;
            box-shadow: 0 6px 24px rgba(15, 23, 42, 0.12);
        }

        .sheet-title {
            margin: 0 0 4mm;
            font-size: 12px;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .sheet {
            display: grid;
            grid-template-columns: repeat(2, 85.6mm);
            grid-auto-rows: 54mm;
            gap: 2mm;
            justify-content: center;
            align-content: start;
        }

        /* ===== CARD (CR80: 85.6mm x 54mm) ===== */
        .id-card {
            position: relative;
            width: 85.6mm;
            height: 54mm;
            overflow: hidden;
            border-radius: 3.5mm;
            background: #fff;
            box-shadow: 0 6px 18px rgba(15, 23, 42, 0.16);
            break-inside: avoid;
        }

        .id-card-face {
            width: 100%;
            height: 100%;
            background: #fff;
        }

        /* ===== FRONT ===== */
        .card-front-header {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 14px;
            background: linear-gradient(135deg, var(--brand-primary) 0%, var(--brand-primary-dark) 100%);
            color: #fff;
        }

        .card-front-header img.logo,
        .card-front-header .logo {
            width: 28px;
            height: 28px;
            flex-shrink: 0;
            border-radius: 6px;
            background: #fff;
            padding: 2px;
            object-fit: contain;
        }

        .card-front-header .logo-fallback {
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 15px;
            font-weight: 700;
            color: var(--brand-primary);
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

        .card-body-front {
            display: flex;
            gap: 12px;
            padding: 12px 14px;
        }

        .card-photo {
            width: 68px;
            height: 82px;
            flex-shrink: 0;
            border: 2px solid var(--brand-secondary);
            border-radius: 8px;
            background: #e5e7eb;
            object-fit: cover;
        }

        .card-photo-fallback {
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 26px;
            font-weight: 700;
            color: #64748b;
        }

        .card-info {
            min-width: 0;
            padding-top: 4px;
        }

        .siswa-name {
            margin-bottom: 2px;
            font-size: 13px;
            font-weight: 700;
            line-height: 1.2;
            color: #1f2937;
            overflow-wrap: anywhere;
        }

        .siswa-meta {
            margin-bottom: 4px;
            font-size: 9px;
            color: #6b7280;
        }

        .siswa-meta:last-child {
            margin-bottom: 0;
        }

        .siswa-meta strong {
            color: #374151;
        }

        .card-front-footer {
            position: absolute;
            right: 0;
            bottom: 0;
            left: 0;
            display: flex;
            justify-content: space-between;
            padding: 4px 14px;
            background: var(--brand-accent);
            color: #fff;
            font-size: 7px;
        }

        /* ===== BACK ===== */
        .card-back-inner {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            padding: 10px 14px 18px;
            text-align: center;
        }

        .card-back-qr {
            width: 32mm;
            height: 32mm;
            margin-bottom: 2mm;
            background: #fff;
        }

        .card-back-qr svg {
            display: block;
            width: 100%;
            height: 100%;
        }

        .card-back-rules {
            font-size: 6.5px;
            line-height: 1.4;
            color: #6b7280;
        }

        .card-back-footer {
            position: absolute;
            right: 0;
            bottom: 0;
            left: 0;
            padding: 4px 14px;
            background: var(--brand-primary-dark);
            color: #fff;
            font-size: 6.5px;
            text-align: center;
        }

        /* ===== PRINT ===== */
        @page {
            size: A4 portrait;
            margin: 8mm;
        }

        @media print {
            body {
                background: #fff;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .no-print,
            .sheet-title {
                display: none !important;
            }

            .preview {
                padding: 0;
            }

            .paper {
                width: auto;
                min-height: 0;
                margin: 0;
                padding: 0;
                box-shadow: none;
            }

            .paper-back {
                break-before: page;
                page-break-before: always;
            }

            .id-card {
                border-radius: 0;
                box-shadow: none;
                outline: 0.2mm dashed #cbd5e1;
            }
        }

        @media screen and (max-width: 840px) {
            .paper {
                width: 100%;
                min-height: 0;
                padding: 12px;
            }

            .sheet {
                grid-template-columns: 85.6mm;
            }
        }
    </style>
</head>
<body>

    <div class="toolbar no-print">
        <h1>Cetak Kartu Pelajar</h1>
        <button type="button" class="btn-print" onclick="window.print()">Cetak / Simpan PDF</button>
        <p class="hint"><strong>Penting:</strong> di dialog cetak, buka "More settings" dan matikan "Headers and footers". Untuk menyimpan PDF, pilih "Save as PDF" di tujuan cetak.</p>
    </div>

    @php
        $schoolName = trim((string) $sekolah?->nama_sekolah) ?: 'Sekolah';
        $schoolLogo = $sekolah?->logo_path ? Storage::disk('public')->url($sekolah->logo_path) : null;
        $tahunAjaran = $tahunAjaran ?? date('Y').'/'.(date('Y') + 1);
        $siswaList = $siswaList ?? collect();
    @endphp

    <div class="preview">
        <section class="paper">
            <h2 class="sheet-title">Sisi Depan</h2>
            <div class="sheet">
                @foreach ($siswaList as $siswa)
                    <div class="id-card">
                        <div class="id-card-face id-card-front">
                            <div class="card-front-header">
                                @if($schoolLogo)
                                    <img src="{{ $schoolLogo }}" alt="Logo" class="logo">
                                @else
                                    <div class="logo logo-fallback">{{ strtoupper(mb_substr($schoolName, 0, 1)) }}</div>
                                @endif
                                <div>
                                    <div class="school-name">{{ $schoolName }}</div>
                                    <div class="card-label">KARTU PELAJAR</div>
                                </div>
                            </div>

                            <div class="card-body-front">
                                @if(!empty($siswa->foto))
                                    <img src="{{ $siswa->foto }}" alt="Foto {{ $siswa->nama }}" class="card-photo">
                                @else
                                    <div class="card-photo card-photo-fallback">{{ strtoupper(mb_substr($siswa->nama, 0, 1)) }}</div>
                                @endif
                                <div class="card-info">
                                    <div class="siswa-name">{{ $siswa->nama }}</div>
                                    <div class="siswa-meta">NISN: <strong>{{ $siswa->nisn }}</strong></div>
                                    <div class="siswa-meta">Kelas: <strong>{{ $siswa->kelas }}</strong></div>
                                    <div class="siswa-meta">T.A. <strong>{{ $tahunAjaran }}</strong></div>
                                </div>
                            </div>

                            <div class="card-front-footer">
                                <span>Berlaku selama menjadi siswa aktif</span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="paper paper-back">
            <h2 class="sheet-title">Sisi Belakang</h2>
            <div class="sheet">
                @foreach ($siswaList as $siswa)
                    <div class="id-card">
                        <div class="id-card-face id-card-back">
                            <div class="card-back-inner">
                                <div class="card-back-qr" aria-label="QR absensi {{ $siswa->nama }}">
                                    {!! $siswa->qr !!}
                                </div>
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
                @endforeach
            </div>
        </section>
    </div>

</body>
</html>
