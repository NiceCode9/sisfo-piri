<?php

namespace App\Imports;

use App\Models\Absensi;
use App\Models\Rombel;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

/**
 * Impor absensi harian dari berkas Excel/CSV.
 *
 * Bentuknya menempel grid manual: `rombel`, `tahun_ajaran`, `tanggal`, `nis`,
 * dan `status`. Status kosong berarti "tidak dicatat" — persis seperti grid,
 * jadi impor tidak bisa dipakai untuk mengisi seluruh kelas sekaligus.
 *
 * Impor hanya membuat catatan yang belum ada. Baris yang sudah ada dengan
 * status berbeda dilaporkan sebagai kegagalan, bukan ditimpa: koreksi atas
 * absensi yang sudah tercatat butuh `absensis.edit` dan alasan per perubahan,
 * dan itu tidak bisa dinyatakan dengan bermakna di berkas massal. Arahkan ke
 * grid untuk kasus itu.
 *
 * Nilai dari Excel tidak bisa dipercaya bentuknya. `4001` ditulis sebagai
 * angka, tanggal sebagai nomor serial, dan keduanya akan gagal kalau aturan
 * validasi menuntut `string`/`date`. Karena itu aturannya sengaja longgar dan
 * normalisasi justru dilakukan sendiri di sini.
 *
 * Setiap baris wajib melewati closure {@see rombelTerjangkau()} milik
 * controller. Tanpa itu impor menjadi celah yang melewati scoping rombel: guru
 * cukup mengunggah berkas untuk kelas yang bukan ampunya.
 */
class AbsensiImport implements SkipsEmptyRows, SkipsOnFailure, ToCollection, WithHeadingRow, WithValidation
{
    use SkipsFailures;

    /**
     * Nilai status yang diakui.
     *
     * @var array<int, string>
     */
    public const STATUS = ['hadir', 'sakit', 'izin', 'alpa', 'terlambat'];

    /**
     * Kolom yang dibaca dari berkas, sesuai template.
     *
     * @var array<int, string>
     */
    public const KOLOM = ['rombel', 'tahun_ajaran', 'tanggal', 'nis', 'status'];

    /**
     * Berapa baris yang benar-benar tercatat.
     */
    public int $imported = 0;

    /**
     * Berapa baris yang dilewati karena statusnya sudah sama.
     */
    public int $sudahSama = 0;

    /**
     * Pasangan rombel+tanggal yang punya status alpa, untuk notifikasi sekali
     * per pasangan alih-alih per baris.
     *
     * @var array<string, true>
     */
    public array $rombelTanggalAlpa = [];

    /**
     * Baris yang tidak bisa dicatat, dengan alasannya.
     *
     * @var array<int, string>
     */
    public array $customFailures = [];

    /**
     * Closure yang mengembalikan rombel hanya bila terjangkau pemanggil.
     *
     * @var callable(int): ?Rombel
     */
    protected $rombelTerjangkau;

    /**
     * Closure untuk menyimpan satu baris absensi, milik controller.
     *
     * @var callable(int, int, Carbon, string, string): Absensi
     */
    protected $catat;

    public function __construct(callable $rombelTerjangkau, callable $catat)
    {
        $this->rombelTerjangkau = $rombelTerjangkau;
        $this->catat = $catat;
    }

    /**
     * Aturannya sengaja tidak menuntut tipe. Excel mengirim angka untuk NIS
     * dan nomor serial untuk tanggal; menolak keduanya di sini hanya
     * memindahkan masalah ke pesan error yang tidak membantu pengguna.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'rombel' => ['required'],
            'tahun_ajaran' => ['nullable'],
            'tanggal' => ['required'],
            'nis' => ['required'],
            'status' => ['nullable'],
        ];
    }

    /**
     * @param  Collection<int, mixed>  $rows
     */
    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            $this->simpanBaris($this->bersihkan($row));
        }
    }

    /**
     * Ambil hanya kolom yang dikenal.
     *
     * Baris dari PhpSpreadsheet adalah objek, bukan array. Casting dengan
     * `(array)` justru membocorkan properti internalnya (`items`,
     * `escapeWhenCastingToString`) dan bukan isi baris, sehingga setiap kolom
     * terbaca null dan semua baris lolos tanpa dicatat. Karena itu kolom
     * diambil satu per satu lewat akses array.
     *
     * @return array<string, mixed>
     */
    private function bersihkan(mixed $row): array
    {
        $bersih = [];

        foreach (self::KOLOM as $kolom) {
            $nilai = $row[$kolom] ?? null;

            $bersih[$kolom] = is_string($nilai) ? trim($nilai) : $nilai;
        }

        return $bersih;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function simpanBaris(array $row): void
    {
        $kelas = $this->teks($row['rombel'] ?? null);
        $status = strtolower($this->teks($row['status'] ?? null));
        $nis = $this->teks($row['nis'] ?? null);

        // Status kosong = tidak dicatat, sama seperti grid manual.
        if ($status === '') {
            return;
        }

        if (! in_array($status, self::STATUS, true)) {
            $this->gagal("{$kelas}: status '{$status}' tidak dikenal. Pilihan: ".implode(', ', self::STATUS).'.');

            return;
        }

        $tanggal = $this->tanggalDari($row['tanggal'] ?? null);

        if ($tanggal === null) {
            $this->gagal("{$kelas}: tanggal tidak bisa dibaca.");

            return;
        }

        if ($tanggal->isFuture()) {
            $this->gagal("{$kelas}: tanggal {$tanggal->toDateString()} masih di masa depan.");

            return;
        }

        $tahun = $this->tahunAjaranDari($row['tahun_ajaran'] ?? null);

        if ($tahun === null) {
            $this->gagal('Tahun ajaran aktif belum ditentukan, tidak bisa menentukan rombel.');

            return;
        }

        $rombel = $this->cariRombel($kelas, $tahun->id);

        if ($rombel === null) {
            return;
        }

        // Penjaga wajib: tanpa ini impor bisa mengisi kelas yang bukan ampunan
        // pemanggil, melewati scoping yang sama seperti grid dan scan.
        if (($this->rombelTerjangkau)($rombel->id) === null) {
            $this->gagal("Rombel '{$kelas}' bukan ampunan Anda.");

            return;
        }

        $siswa = $this->cariSiswa($rombel, $nis);

        if ($siswa === null) {
            return;
        }

        $ada = Absensi::where('siswa_id', $siswa->id)
            ->where('rombel_id', $rombel->id)
            ->whereDate('tanggal', $tanggal->toDateString())
            ->first();

        if ($ada !== null) {
            if ($ada->status === $status) {
                $this->sudahSama++;

                return;
            }

            $this->gagal("{$kelas} NIS {$siswa->nis} tanggal {$tanggal->toDateString()} sudah tercatat '{$ada->status}'. Koreksi lewat grid, bukan impor.");

            return;
        }

        ($this->catat)($siswa->id, $rombel->id, $tanggal, $status, 'manual');

        $this->imported++;

        if ($status === 'alpa') {
            $this->rombelTanggalAlpa[$rombel->id.'|'.$tanggal->toDateString()] = true;
        }
    }

    /**
     * Ubah nilai apa pun menjadi teks bersih, termasuk angka dari Excel.
     */
    private function teks(mixed $nilai): string
    {
        if ($nilai === null || is_array($nilai)) {
            return '';
        }

        if (is_float($nilai)) {
            return rtrim(rtrim(number_format($nilai, 4, '.', ''), '0'), '.');
        }

        return trim((string) $nilai);
    }

    /**
     * Tanggal bisa datang sebagai objek (sel berformat tanggal), nomor serial
     * Excel (hari sejak 1899-12-30), atau teks biasa.
     */
    private function tanggalDari(mixed $nilai): ?Carbon
    {
        if ($nilai instanceof DateTimeInterface) {
            return Carbon::instance($nilai)->startOfDay();
        }

        // Angka — atau teks yang seluruhnya angka — berarti nomor serial Excel.
        // Ditangani lebih dulu supaya "20260101" tidak tertangkap sebagai serial
        // dan karena itu tidak ditafsirkan sebagai tanggal.
        if (is_int($nilai) || is_float($nilai)) {
            return $this->dariSerial((float) $nilai);
        }

        $teks = $this->teks($nilai);

        if ($teks === '') {
            return null;
        }

        if (is_numeric($teks)) {
            $serial = $this->dariSerial((float) $teks);

            if ($serial !== null) {
                return $serial;
            }
        }

        try {
            return Carbon::parse($teks)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Nomor serial di luar rentang kalender berarti bukan tanggal.
     */
    private function dariSerial(float $nomor): ?Carbon
    {
        if ($nomor < 1 || $nomor > 2958465) {
            return null;
        }

        return Carbon::create(1899, 12, 30)->addDays((int) $nomor)->startOfDay();
    }

    private function tahunAjaranDari(mixed $nilai): ?TahunAjaran
    {
        $teks = $this->teks($nilai);

        if ($teks === '') {
            return TahunAjaran::aktif()->first();
        }

        return TahunAjaran::where('nama', $teks)->first()
            ?? TahunAjaran::aktif()->first();
    }

    private function cariRombel(string $kelas, int $tahunAjaranId): ?Rombel
    {
        $rombel = Rombel::whereHas('kelas', fn ($q) => $q->where('nama_kelas', $kelas))
            ->where('tahun_ajaran_id', $tahunAjaranId)
            ->first();

        if ($rombel === null) {
            $this->gagal("Rombel '{$kelas}' tidak ditemukan pada tahun ajaran tersebut.");
        }

        return $rombel;
    }

    private function cariSiswa(Rombel $rombel, string $nis): ?Siswa
    {
        $siswa = Siswa::where('nis', $nis)
            ->where('kelas_id', $rombel->kelas_id)
            ->where('tahun_ajaran_id', $rombel->tahun_ajaran_id)
            ->first();

        if ($siswa === null) {
            $this->gagal("NIS '{$nis}' tidak terdaftar di kelas {$rombel->kelas->nama_kelas}.");
        }

        return $siswa;
    }

    private function gagal(string $pesan): void
    {
        $this->customFailures[] = $pesan;
    }
}
