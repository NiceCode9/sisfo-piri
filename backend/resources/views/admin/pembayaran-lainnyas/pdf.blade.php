<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <title>Laporan Pembayaran Lainnya PPDB</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111; }
        h1 { font-size: 18px; margin: 0 0 4px; }
        .meta { color: #555; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #999; padding: 5px 7px; text-align: left; }
        th { background: #eee; }
        td.num, th.num { text-align: right; }
        tfoot td { font-weight: bold; }
    </style>
</head>
<body>
    <h1>Laporan Pembayaran Lainnya PPDB</h1>
    <div class="meta">
        Dicetak: {{ now()->format('d M Y H:i') }} • {{ $pembayarans->count() }} data
        @if(!empty(array_filter($filters ?? [])))
            • Filter:
            @foreach(array_filter($filters ?? []) as $k => $v){{ $k }}={{ $v }}@endforeach
        @endif
    </div>
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Kode</th>
                <th>Calon</th>
                <th>Nama Biaya</th>
                <th class="num">Jumlah</th>
                <th>Metode</th>
                <th>Status</th>
                <th>Tanggal</th>
            </tr>
        </thead>
        <tbody>
            @forelse($pembayarans as $i => $p)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $p->kode_pembayaran }}</td>
                    <td>{{ $p->calonSiswa->nama_lengkap ?? '-' }} ({{ $p->calonSiswa->no_pendaftaran ?? '-' }})</td>
                    <td>{{ $p->nama_biaya }}</td>
                    <td class="num">{{ number_format($p->jumlah, 0, ',', '.') }}</td>
                    <td>{{ $p->metode_pembayaran }}</td>
                    <td>{{ $p->status }}</td>
                    <td>{{ $p->tanggal_pembayaran ? \Carbon\Carbon::parse($p->tanggal_pembayaran)->format('d M Y') : '-' }}</td>
                </tr>
            @empty
                <tr><td colspan="8" style="text-align:center;">Belum ada data.</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4">Total</td>
                <td class="num">Rp {{ number_format($total, 0, ',', '.') }}</td>
                <td colspan="3"></td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
