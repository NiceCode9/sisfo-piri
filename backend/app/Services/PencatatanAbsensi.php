<?php

namespace App\Services;

use App\Models\Absensi;
use Carbon\Carbon;
use Illuminate\Database\QueryException;

/**
 * Otak pencatatan absensi yang dipakai semua jalur tulis: scan mandiri,
 * grid manual, dan impor Excel.
 *
 * Dipindahkan verbatim dari `AbsensiController` dengan satu perbedaan:
 * `dicatat_oleh` kini dibawa eksplisit oleh pemanggil, karena jalur scan
 * mandiri mencatat atas nama guru pemilik QR, bukan atas nama pengguna
 * yang mengirim permintaan.
 */
class PencatatanAbsensi
{
    /**
     * Mencatat kehadiran dengan aturan "arrival pertama yang dihitung".
     */
    public static function catat(int $siswaId, int $rombelId, Carbon $sekarang, string $status, string $metode, ?int $dicatatOleh): Absensi
    {
        $kunci = [
            // rombel_id masuk kunci, bukan hanya atribut. Tanpa itu, scan
            // untuk kelas yang berbeda pada tanggal sama akan menimpa baris
            // kelas lama alih-alih membuat baris sendiri.
            'siswa_id' => $siswaId,
            'rombel_id' => $rombelId,
            'tanggal' => $sekarang->toDateString(),
        ];

        $absensi = Absensi::firstOrNew($kunci);
        self::isiArrivalPertama($absensi, $status, $sekarang, $metode, $dicatatOleh);
        $tersimpan = self::simpan($absensi, $kunci);

        if ($tersimpan->is($absensi)) {
            return $tersimpan;
        }

        // Balapan: baris ini milik pencatatan perangkat lain, jadi perlakukan
        // sebagai pencatatan susulan — jam pertama dan status `hadir` tidak
        // boleh ditimpa.
        self::isiArrivalPertama($tersimpan, $status, $sekarang, $metode, $dicatatOleh);
        $tersimpan->save();

        return $tersimpan;
    }

    /**
     * Menyimpan baris absensi, aman terhadap balapan pada unique.
     *
     * Dua perangkat bisa memindai kartu yang sama pada saat bersamaan. Keduanya
     * membaca tabel kosong lalu sama-sama mencoba insert, dan yang kedua menabrak
     * `absensis_siswa_rombel_tanggal_unique` sehingga berakhir sebagai 500.
     *
     * Mengembalikan baris yang benar-benar tersimpan: model milik pemanggil
     * bila sukses, atau baris pesaing bila balapan terjadi. Pemanggil
     * memeriksa identitasnya untuk tahu perlu menerapkan aturan pencatatan lagi.
     *
     * @param  array<string, mixed>  $kunci
     */
    public static function simpan(Absensi $absensi, array $kunci): Absensi
    {
        try {
            $absensi->save();

            return $absensi;
        } catch (QueryException $e) {
            // 23000 = integrity constraint violation di MySQL maupun SQLite.
            // Pelanggaran lain (mis. foreign key) harus tetap surfaced.
            if ($e->getCode() !== '23000') {
                throw $e;
            }

            return Absensi::where($kunci)->first() ?? throw $e;
        }
    }

    /**
     * Menerapkan aturan "arrival pertama yang dihitung".
     *
     * `jam_datang` hanya diisi bila masih kosong, dan status `hadir` tidak
     * pernah diturunkan ke `terlambat` oleh pencatatan susulan. Siswa yang scan
     * 06:55 lalu scan lagi 08:05 tidak boleh kehilangan catatan kehadiran tepat
     * waktunya dan tidak boleh dianggap terlambat karena scan kedua.
     */
    protected static function isiArrivalPertama(Absensi $absensi, string $status, Carbon $sekarang, string $metode, ?int $dicatatOleh): void
    {
        if (! $absensi->exists) {
            $absensi->status = $status;
            $absensi->jam_datang = $sekarang->format('H:i:s');
        } else {
            $absensi->status = $absensi->status === 'hadir' ? 'hadir' : $status;

            if (! $absensi->jam_datang) {
                $absensi->jam_datang = $sekarang->format('H:i:s');
            }
        }

        $absensi->rombel_id = (int) $absensi->rombel_id;
        $absensi->metode = $metode;
        $absensi->dicatat_oleh = $dicatatOleh;
    }
}
