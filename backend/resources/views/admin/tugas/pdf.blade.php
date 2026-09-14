<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <title>Rekap Tugas</title>
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
    <h2>Rekap Tugas — {{ $rekap['rombel']->kelas->nama_kelas ?? '-' }}</h2>
    <table>
        <thead>
            <tr><th>Nama</th><th>NIS</th>@foreach($rekap['tugasList'] as $t)<th>{{ $t->judul }}</th>@endforeach<th>Rata</th></tr>
        </thead>
        <tbody>
            @foreach($rekap['siswas'] as $s)
                <tr>
                    <td>{{ $s->user->name ?? '-' }}</td>
                    <td>{{ $s->nis ?? $s->nisn ?? '-' }}</td>
                    @foreach($rekap['tugasList'] as $t)
                        @php $p = $rekap['matriks'][$s->id][$t->id] ?? null; @endphp
                        <td class="c">{{ $p?->nilai ?? '—' }}</td>
                    @endforeach
                    <td class="c">{{ $rekap['rataPerSiswa'][$s->id] ?? '—' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
