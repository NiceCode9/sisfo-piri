<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <title>Rekap Absensi</title>
    <style>
        body { font-family: sans-serif; font-size: 11px; }
        h2 { margin-bottom: 2px; }
        p { margin-top: 0; color: #555; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #999; padding: 4px 6px; text-align: left; }
        th { background: #eee; }
        td.c { text-align: center; }
    </style>
</head>
<body>
    <h2>Rekap Absensi — {{ $rekap['rombel']->kelas->nama_kelas ?? '-' }}</h2>
    <p>{{ $rekap['mulai'] }} s.d. {{ $rekap['selesai'] }}</p>
    <table>
        <thead>
            <tr><th>Nama</th><th>NIS</th><th>H</th><th>S</th><th>I</th><th>A</th><th>T</th><th>%</th></tr>
        </thead>
        <tbody>
            @foreach($rekap['siswas'] as $s)
                @php $b = $rekap['matriks'][$s->id]; @endphp
                <tr>
                    <td>{{ $s->user->name ?? '-' }}</td>
                    <td>{{ $s->nis ?? $s->nisn ?? '-' }}</td>
                    <td class="c">{{ $b['hitung']['hadir'] }}</td>
                    <td class="c">{{ $b['hitung']['sakit'] }}</td>
                    <td class="c">{{ $b['hitung']['izin'] }}</td>
                    <td class="c">{{ $b['hitung']['alpa'] }}</td>
                    <td class="c">{{ $b['hitung']['terlambat'] }}</td>
                    <td class="c">{{ $b['persen'] === null ? '–' : $b['persen'].'%' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
