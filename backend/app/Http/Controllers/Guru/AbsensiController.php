<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Http\Requests\Guru\StoreAbsensiManualRequest;
use App\Models\Absensi;
use App\Models\Pengaturan;
use App\Models\Rombel;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Services\NotifikasiAlpa;
use App\Services\PencatatanAbsensi;
use App\Services\TokenKartuSiswa;
use Carbon\CarbonInterface;
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
        return view('guru.absensi', $this->jendela() + $this->dataHalaman($request));
    }

    public function cari(Request $request): View
    {
        $request->validate(['q' => ['nullable', 'string', 'max:100']]);

        return view('guru.absensi', $this->jendela() + $this->dataHalaman($request, true));
    }

    public function pilih(Request $request): RedirectResponse
    {
        $request->validate(['siswa_id' => ['required', 'integer', 'exists:siswas,id']]);

        $user = $request->user();
        $siswa = Siswa::with('user')->find((int) $request->input('siswa_id'));

        abort_if($siswa === null || ! $siswa->is_aktif, 404, 'Siswa tidak aktif.');

        $rombel = Rombel::aktifUntukSiswa($siswa);

        abort_if($rombel === null, 404, 'Kelas siswa belum terdaftar.');

        abort_unless(
            Rombel::terjangkauOleh($user, $rombel->id, Rombel::ROLE_ABSENSI_UNIVERSAL),
            403,
            'Kelas ini bukan cakupan Anda.'
        );

        abort_unless(
            in_array($siswa->id, $rombel->anggotaIdsAktif(), true),
            403,
            'Siswa bukan anggota kelas ini.'
        );

        session(['absensi_rombel_id' => $rombel->id]);

        return redirect()->route('guru.absensi', [
            'siswa_id' => $siswa->id,
            'mode' => 'semua',
        ])->with('success', 'Kelas '.($rombel->kelas?->nama_kelas ?? $rombel->id).' dibuka.');
    }

    /**
     * Data bersama halaman absensi, agar `show()` dan `cari()` tidak
     * menduplikasi query.
     *
     * @return array<string, mixed>
     */
    private function dataHalaman(Request $request, bool $cari = false): array
    {
        $user = $request->user();

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

        $q = (string) $request->query('q', '');
        $hasilCari = collect();

        if ($cari && $q !== '') {
            $tahunAktif = TahunAjaran::aktif()->first();
            $rombels = Rombel::query()->with('kelas')
                ->terjangkauUser($user, Rombel::ROLE_ABSENSI_UNIVERSAL);

            if ($tahunAktif) {
                $rombels->where('tahun_ajaran_id', $tahunAktif->id);
            }

            $rombelList = $rombels->get();
            $ids = $rombelList->flatMap(fn (Rombel $r) => $r->anggotaIdsAktif())->unique()->values()->all();

            $hasilCari = Siswa::with('user')
                ->whereIn('id', $ids)
                ->where('is_aktif', true)
                ->where(function ($query) use ($q) {
                    $query->where('nis', 'like', "%{$q}%")
                        ->orWhere('nisn', 'like', "%{$q}%")
                        ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$q}%"));
                })
                ->orderBy('nis')
                ->limit(10)
                ->get()
                ->map(function (Siswa $s) {
                    $s->kelas_aktif = Rombel::aktifUntukSiswa($s)?->kelas?->nama_kelas;

                    return $s;
                });
        }

        return [
            'rombelsIzin' => $rombelsIzin,
            'rombelIzinTerpilih' => $rombelIzinTerpilih,
            'siswaKartu' => $siswaKartu,
            'absensiHariIni' => $absensiHariIni,
            'daftar' => $daftar,
            'mode' => $this->mode($request->query('mode')),
            'q' => $q,
            'hasilCari' => $hasilCari,
        ];
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

    public function manual(StoreAbsensiManualRequest $request): JsonResponse|RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated();
        $mode = $this->mode($data['mode'] ?? null);

        $siswa = Siswa::with('user')->find((int) $data['siswa_id']);

        if ($siswa === null || ! $siswa->is_aktif) {
            return $this->gagalScan('Siswa tidak aktif atau tidak ditemukan.', $mode);
        }

        $rombel = Rombel::aktifUntukSiswa($siswa);

        if ($rombel === null) {
            return $this->gagalScan('Kelas siswa belum terdaftar.', $mode);
        }

        abort_unless(
            Rombel::terjangkauOleh($user, $rombel->id, Rombel::ROLE_ABSENSI_UNIVERSAL),
            403,
            'Kelas ini bukan cakupan Anda.'
        );

        abort_unless(
            in_array($siswa->id, $rombel->anggotaIdsAktif(), true),
            403,
            'Siswa bukan anggota kelas ini.'
        );

        $kunci = [
            'siswa_id' => $siswa->id,
            'rombel_id' => $rombel->id,
            'tanggal' => today()->toDateString(),
        ];

        $absensi = Absensi::firstOrNew($kunci);

        if ($absensi->exists) {
            return $this->gagalScan('Sudah tercatat, koreksi lewat grid.', $mode);
        }

        $status = (string) $data['status'];
        $absensi->rombel_id = $rombel->id;
        $absensi->status = $status;
        $absensi->metode = 'manual';
        $absensi->dicatat_oleh = $user->id;

        if (in_array($status, ['hadir', 'terlambat'], true)) {
            $absensi->jam_datang = now()->format('H:i:s');
        } else {
            $absensi->jam_datang = null;
        }

        $tersimpan = PencatatanAbsensi::simpan($absensi, $kunci);

        if (! $tersimpan->is($absensi)) {
            return $this->gagalScan('Sudah tercatat, koreksi lewat grid.', $mode);
        }

        if ($status === 'alpa') {
            NotifikasiAlpa::antrekan($rombel->id, today()->toDateString(), [$siswa->id]);
        }

        session(['absensi_rombel_id' => $rombel->id]);

        $nama = $siswa->user?->name ?? $siswa->nis;
        $pesan = "{$nama} dicatat {$status} (manual).";

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $pesan,
                'status' => $absensi->status,
                'baru' => true,
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
     * @return array{rombel: Rombel, tanggal: CarbonInterface, baris: array<int, array{siswa: Siswa, absensi: ?Absensi, nama: string, nis: string}>, ringkasan: array<string, int>, siswaTerpilih: ?int}
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
