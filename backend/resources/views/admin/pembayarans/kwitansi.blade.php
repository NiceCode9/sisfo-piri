<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <title>Kwitansi {{ $pembayaran->kode_pembayaran }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111; }
        .kop { text-align: center; margin-bottom: 6px; }
        .kop .nama { font-size: 20px; font-weight: bold; }
        .kop .alamat { font-size: 11px; color: #444; }
        hr.ganda { border: none; border-top: 3px double #111; margin: 8px 0 14px; }
        h2 { text-align: center; font-size: 17px; margin: 0 0 2px; letter-spacing: 2px; }
        .nomor { text-align: center; color: #444; margin-bottom: 14px; }
        table.rincian { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        table.rincian td { padding: 5px 4px; vertical-align: top; }
        table.rincian td.label { width: 170px; color: #444; }
        .nominal { border: 1px solid #111; padding: 8px 10px; font-size: 15px; font-weight: bold; margin: 10px 0; }
        .terbilang { background: #f2f2f2; padding: 8px 10px; font-style: italic; margin-bottom: 18px; }
        .ttd { width: 100%; margin-top: 24px; }
        .ttd td { width: 50%; text-align: center; vertical-align: top; }
        .footer { margin-top: 22px; font-size: 10px; color: #666; text-align: center; }
    </style>
</head>
<body>
    <div class="kop">
        <div class="nama">{{ $sekolah['nama'] }}</div>
        <div class="alamat">{{ $sekolah['alamat'] }} • Telp {{ $sekolah['telp'] }}</div>
    </div>
    <hr class="ganda" />

    <h2>KWITANSI PEMBAYARAN PPDB</h2>
    <div class="nomor">No: {{ $pembayaran->kode_pembayaran }}</div>

    <table class="rincian">
        <tr>
            <td class="label">Telah diterima dari</td>
            <td>: <strong>{{ $pembayaran->calonSiswa->nama_lengkap ?? '-' }}</strong>
                ({{ $pembayaran->calonSiswa->no_pendaftaran ?? '-' }} • NIK {{ $pembayaran->calonSiswa->nik ?? '-' }})</td>
        </tr>
        <tr>
            <td class="label">Untuk pembayaran</td>
            <td>: {{ $pembayaran->biayaPendaftaran->jenis_biaya ?? '-' }}
                @if($pembayaran->jenis_pembayaran === 'dp_angsuran')
                    (DP Angsuran)
                @elseif($pembayaran->jenis_pembayaran === 'cicilan_angsuran')
                    (Cicilan ke-{{ $pembayaran->detailAngsuran->cicilan_ke ?? '?' }})
                @endif
                via {{ $pembayaran->metode_pembayaran }}</td>
        </tr>
        <tr>
            <td class="label">Tanggal bayar</td>
            <td>: {{ $pembayaran->tanggal_pembayaran ? \Carbon\Carbon::parse($pembayaran->tanggal_pembayaran)->format('d M Y') : '-' }}</td>
        </tr>
    </table>

    <div class="nominal">Rp {{ number_format($pembayaran->jumlah, 0, ',', '.') }}</div>
    <div class="terbilang">Terbilang: "{{ ucfirst($terbilang) }}"</div>

    <table class="ttd">
        <tr>
            <td></td>
            <td>
                Sleman, {{ now()->format('d M Y') }}<br />
                Petugas,<br /><br /><br /><br />
                <strong><u>{{ $petugas }}</u></strong>
            </td>
        </tr>
    </table>

    <div class="footer">Dicetak otomatis dari Sistem Informasi PPDB pada {{ now()->format('d M Y H:i') }}.</div>
</body>
</html>
