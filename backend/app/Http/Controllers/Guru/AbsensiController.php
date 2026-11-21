<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\Absensi;
use App\Models\Pengaturan;
use App\Models\Rombel;
use App\Models\Siswa;
use App\Services\PencatatanAbsensi;
use App\Services\TokenKartuSiswa;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Absensi kelas oleh guru: scan kartu siswa, antrian manual, dan riwayat
 * hari itu. Kelas selalu diturunkan dari `RiwayatKelas` aktif siswa, bukan
 * dari pilihan guru.
 */
class AbsensiController extends Controller
{
    /** Info jendela jam yang ditemukan di layar guru, dari satu sumber pengaturan. */
    private function jendela(): array
    {
        return [
            'jam_buka' => Pengaturan::nilai('jam_buka_absen', '07:00'),
            'batas_terlambat' => Pengaturan::nilai('batas_terlambat', '07:15'),
            'jam_tutup' => Pengaturan::nilai('jam_tutup_absen', '07:45'),
        ];
    }

    public function show(Request $request): View
    {
        $user = $request->user();

        // Data kartu Izin/Sakit: cakupan sama dengan area absensi, fail-closed
        // untuk guru tanpa penugasan (spek 2026-10-23).
        $rombelsIzin = Rombel::query()->with('kelas')
            ->terjangkauUser($user, Rombel::ROLE_ABSENSI_UNIVERSAL)
            ->get();
        $rombelIzinTerpilih = $rombelsIzin->firstWhere(
            'id',
            (int) $request->query('rombel_id') ?: $rombelsIzin->first()?->id
        );
        $siswaKartu = $rombelIzinTerpilih
            ? Siswa::with('user')
                ->whereIn('id', $rombelIzinTerpilih->anggotaIdsAktif())
                ->get()
                ->sortBy(fn ($s) => $s->user?->name ?? '')
                ->values()
            : collect();
        $absensiHariIni = Absensi::query()
            ->where('rombel_id', $rombelIzinTerpilih?->id)
            ->where('tanggal', today()->toDateString())
            ->get()
            ->keyBy('siswa_id');

        $rombelAbsensi = $this->rombelSesi($user);
        $daftar = $rombelAbsensi
            ? $this->daftar($rombelAbsensi, (int) $request->query('siswa_id') ?: null)
            : null;

        return view('guru.absensi', $this->jendela() + [
            'rombelsIzin' => $rombelsIzin,
            'rombelIzinTerpilih' => $rombelIzinTerpilih,
            'siswaKartu' => $siswaKartu,
            'absensiHariIni' => $absensiHariIni,
            'daftar' => $daftar,
            'mode' => $this->mode($request->query('mode')),
        ]);
    }

    public function scan(Request $request): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'kode' => ['required', 'string', 'max:500'],
            'mode' => ['nullable', 'in:antrian,semua'],
        ]);
        $kode = trim($data['kode']);
        $mode = $this->mode($data['mode'] ?? null);

        if (str_starts_with($kode, TokenKartuSiswa::AWALAN.'|')) {
            $siswaId = TokenKartuSiswa::baca($kode);

            if ($siswaId === null) {
                return $this->gagalScan('Kartu tidak berlaku.', $mode);
            }

            $siswa = Siswa::with('user')->find($siswaId);
        } else {
            $cocok = Siswa::with('user')
                ->where('nis', $kode)
                ->orWhere('nisn', $kode)
                ->get();

            if ($cocok->count() !== 1) {
                return $this->gagalScan('Kartu atau NISN tidak dikenali.', $mode);
            }

            $siswa = $cocok->first();
        }

        if ($siswa === null) {
            return $this->gagalScan('Siswa tidak ditemukan.', $mode);
        }

        if (! $siswa->is_aktif) {
            return $this->gagalScan('Siswa sudah tidak aktif.', $mode);
        }

        $rombel = Rombel::aktifUntukSiswa($siswa);

        if ($rombel === null) {
            return $this->gagalScan('Kelas siswa belum terdaftar.', $mode);
        }

        abort_unless(
            Rombel::terjangkauOleh($request->user(), $rombel->id, Rombel::ROLE_ABSENSI_UNIVERSAL),
            403,
            'Kelas ini bukan cakupan Anda.'
        );

        abort_unless(
            in_array($siswa->id, $rombel->anggotaIdsAktif(), true),
            403,
            'Siswa bukan anggota kelas ini.'
        );

        $jendela = $this->jendela();
        $jam = now()->format('H:i');

        if ($jam < $jendela['jam_buka']) {
            return $this->gagalScan("Absen belum dibuka (mulai {$jendela['jam_buka']}).", $mode);
        }

        if ($jam >= $jendela['jam_tutup']) {
            return $this->gagalScan("Absen sudah ditutup ({$jendela['jam_tutup']}). Catat lewat antrian manual.", $mode);
        }

        $status = $jam >= $jendela['batas_terlambat'] ? 'terlambat' : 'hadir';
        $kunci = [
            'siswa_id' => $siswa->id,
            'rombel_id' => $rombel->id,
            'tanggal' => today()->toDateString(),
        ];
        $sebelum = Absensi::where($kunci)->first();

        $absensi = PencatatanAbsensi::catat($siswa->id, $rombel->id, now(), $status, 'qr', $request->user()->id);

        session(['absensi_rombel_id' => $rombel->id]);

        $nama = $siswa->user?->name ?? $siswa->nis;

        $pesan = match (true) {
            $sebelum?->metode === 'manual' => "{$nama} sudah dicatat manual ({$sebelum->status}). Tidak diubah.",
            $sebelum !== null => "{$nama} sudah tercatat {$absensi->status}.",
            default => "{$nama} tercatat {$absensi->status}.",
        };

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $pesan,
                'status' => $absensi->status,
                'baru' => $sebelum === null,
                'siswa' => [
                    'id' => $siswa->id,
                    'nama' => $nama,
                    'nis' => $siswa->nis,
                ],
                'rombel' => [
                    'id' => $rombel->id,
                    'nama' => $rombel->kelas?->nama_kelas ?? (string) $rombel->id,
                ],
                'html' => view('guru._absensi-kelas', $this->daftar($rombel, $siswa->id) + ['mode' => $mode])->render(),
            ]);
        }

        return redirect()->route('guru.absensi', ['mode' => $mode])->with('success', $pesan);
    }

    private function rombelSesi($user): ?Rombel
    {
        $id = (int) session('absensi_rombel_id');

        if ($id === 0) {
            return null;
        }

        $rombel = Rombel::with('kelas')
            ->terjangkauUser($user, Rombel::ROLE_ABSENSI_UNIVERSAL)
            ->find($id);

        if ($rombel === null) {
            session()->forget('absensi_rombel_id');
        }

        return $rombel;
    }

    /**
     * @return array{rombel: Rombel, tanggal: \Carbon\CarbonInterface, baris: array<int, array{siswa: Siswa, absensi: ?Absensi, nama: string, nis: string}>, ringkasan: array<string, int>, siswaTerpilih: ?int}
     */
    private function daftar(Rombel $rombel, ?int $siswaTerpilih = null): array
    {
        $siswas = Siswa::with('user')
            ->whereIn('id', $rombel->anggotaIdsAktif())
            ->where('is_aktif', true)
            ->orderBy('nis')
            ->get();

        $absensis = Absensi::with('pencatat')
            ->where('rombel_id', $rombel->id)
            ->whereDate('tanggal', today()->toDateString())
            ->get()
            ->keyBy('siswa_id');

        $baris = $siswas->map(fn (Siswa $siswa) => [
            'siswa' => $siswa,
            'absensi' => $absensis->get($siswa->id),
            'nama' => $siswa->user?->name ?? $siswa->nis,
            'nis' => $siswa->nis,
        ])->all();

        $ringkasan = ['hadir' => 0, 'terlambat' => 0, 'izin' => 0, 'sakit' => 0, 'alpa' => 0, 'belum' => 0];

        foreach ($baris as $row) {
            $status = $row['absensi']?->status;

            if ($status !== null && array_key_exists($status, $ringkasan)) {
                $ringkasan[$status]++;
            } else {
                $ringkasan['belum']++;
            }
        }

        $ringkasan['total'] = count($baris);

        return [
            'rombel' => $rombel,
            'tanggal' => today(),
            'baris' => $baris,
            'ringkasan' => $ringkasan,
            'siswaTerpilih' => $siswaTerpilih,
        ];
    }

    private function mode(?string $mode): string
    {
        return $mode === 'semua' ? 'semua' : 'antrian';
    }

    private function gagalScan(string $pesan, string $mode): JsonResponse|RedirectResponse
    {
        if (request()->expectsJson()) {
            return response()->json(['message' => $pesan], 422);
        }

        return redirect()->route('guru.absensi', ['mode' => $mode])->with('error', $pesan);
    }
}
