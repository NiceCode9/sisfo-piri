<?php

namespace App\Support;

use App\Models\ExamQuestion;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use ZipArchive;

/**
 * Membuka paket soal (ZIP berisi spreadsheet + folder gambar) dengan aman.
 *
 * Empat ancaman yang ditangani di sini:
 *
 * 1. **Zip Slip** — entri bernama `../../config/app.php` akan keluar dari folder
 *    tujuan kalau diekstrak dengan `extractTo()`. Kode ini TIDAK memakai
 *    `extractTo()` sama sekali: setiap entri ditulis manual lewat `getStream()`,
 *    jadi setiap tujuan sepenuhnya terkendali.
 * 2. **Zip bomb** — arsip kecil yang melebar jadi beberapa GB. Total ukuran
 *    dijumlahkan dari `getSize()` SEBELUM satu byte ditulis; kalau menunggu
 *    sampai setelah ekstraksi, kerusakannya terlanjur terjadi.
 * 3. **Berkas palsu** — `kode.php` yang diganti namanya `.png`. Validasi memakai
 *    `getimagesize()`, jadi yang diperiksa adalah isinya, bukan ekstensi.
 * 4. **Beban** — jumlah entri, ukuran tiap gambar, dan dimensinya dibatasi.
 *
 * Nama berkas pada disk SELALU acak (40 hex) dan tidak pernah berasal dari
 * input pengguna; pemetaan ke nama asli disimpan di `manifest.json`.
 */
class PaketSoal
{
    public const MAKS_ENTRI = 500;

    /** Batas total ukuran setelah ekstraksi — penangkal zip bomb. */
    public const MAKS_UKURAN_TERTEKAN = 200 * 1024 * 1024;

    public const MAKS_GAMBAR = 10 * 1024 * 1024;

    public const MAKS_LEBAR = 3000;

    public const MAKS_TINGGI = 3000;

    public const EKSTENSI_GAMBAR = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

    public const MANIFEST = 'manifest.json';

    public const NAMA_SOAL = 'soal.xlsx';

    public function __construct(
        public readonly string $token,
        public readonly string $berkas,
    ) {}

    public static function dari(UploadedFile $zip): self
    {
        if (! $zip->isValid()) {
            throw new RuntimeException('Berkas unggahan tidak valid.');
        }

        $arsip = new ZipArchive;
        $kode = $arsip->open($zip->getRealPath(), ZipArchive::RDONLY);

        if ($kode !== true) {
            throw new RuntimeException('Berkas ZIP tidak bisa dibuka. Pastikan filenya benar-benar .zip.');
        }

        try {
            return self::dariArsip($arsip);
        } finally {
            $arsip->close();
        }
    }

    private static function dariArsip(ZipArchive $arsip): self
    {
        $jumlah = $arsip->numFiles;

        if ($jumlah === 0 || $jumlah > self::MAKS_ENTRI) {
            throw new RuntimeException("Paket berisi {$jumlah} entri; batasnya ".self::MAKS_ENTRI.'.');
        }

        // Tahap 1 — periksa dan hitung dulu, jangan tulis apa pun.
        $total = 0;
        for ($i = 0; $i < $jumlah; $i++) {
            $stat = $arsip->statIndex($i);

            if ($stat === false) {
                continue;
            }

            $total += (int) $stat['size'];
            self::namaAman($stat['name']);
        }

        if ($total > self::MAKS_UKURAN_TERTEKAN) {
            throw new RuntimeException(sprintf(
                'Isi paket %.1f MB, batasnya %d MB. Kompres gambar atau kurangi jumlah soal.',
                $total / 1048576,
                self::MAKS_UKURAN_TERTEKAN / 1048576,
            ));
        }

        $token = Str::random(40);
        $paket = new self($token, ExamQuestion::IMPORT_DIR.$token.'/');
        $disk = Storage::disk(ExamQuestion::DISK);
        $peta = [];

        // Tahap 2 — tulis. Hanya entri yang lolos validasi nama yang diteruskan.
        for ($i = 0; $i < $jumlah; $i++) {
            $stat = $arsip->statIndex($i);

            if ($stat === false) {
                continue;
            }

            $nama = self::namaAman($stat['name']);

            if ($nama === '') {
                continue; // entri direktori
            }

            if (str_ends_with(strtolower($nama), '.xlsx')) {
                self::tulisKe($arsip, $stat['name'], $disk, $paket->berkas.self::NAMA_SOAL);

                continue;
            }

            $ekstensi = strtolower(pathinfo($nama, PATHINFO_EXTENSION));

            if (! in_array($ekstensi, self::EKSTENSI_GAMBAR, true)) {
                continue; // bukan gambar dan bukan spreadsheet — abaikan
            }

            if ((int) $stat['size'] > self::MAKS_GAMBAR) {
                throw new RuntimeException("Gambar {$nama} melebihi 10 MB.");
            }

            $sementara = tempnam(sys_get_temp_dir(), 'paket-soal-');
            self::tulisKe($arsip, $stat['name'], null, $semporaryPath = $sementara);

            try {
                self::tipeGambar($semporaryPath, $nama);

                $tersimpan = self::namaAcak().'.'.$ekstensi;
                $disk->put($paket->berkas.$tersimpan, file_get_contents($semporaryPath));

                // Yang dipetakan ke nama asli adalah BASENAME — guru boleh
                // menaruh gambarnya di folder mana pun di dalam paket.
                $peta[basename($nama)] = $tersimpan;
            } finally {
                @unlink($sementara);
            }
        }

        $buang = function (string $pesan) use ($disk, $token): never {
            $disk->deleteDirectory(ExamQuestion::IMPORT_DIR.$token);

            throw new RuntimeException($pesan);
        };

        if (! $disk->exists($paket->berkas.self::NAMA_SOAL)) {
            $buang('Paket harus berisi satu berkas .xlsx.');
        }

        if ($peta === []) {
            $buang('Paket tidak berisi gambar sama sekali.');
        }

        $disk->put($paket->berkas.self::MANIFEST, json_encode($peta, JSON_PRETTY_PRINT));

        return $paket;
    }

    /**
     * Nama entri harus aman: relatif, tanpa `..`, tanpa backslash.
     *
     * Entri direktori (nama berakhiran `/`) dikembalikan sebagai string kosong
     * supaya pemanggil melewatinya.
     */
    private static function namaAman(string $nama): string
    {
        if ($nama === '' || str_contains($nama, "\0") || str_contains($nama, '\\')) {
            throw new RuntimeException("Nama berkas di paket tidak valid: {$nama}");
        }

        if (str_starts_with($nama, '/') || preg_match('#^[A-Za-z]:#', $nama)) {
            throw new RuntimeException("Paket memuat path absolut: {$nama}");
        }

        foreach (explode('/', $nama) as $bagian) {
            if ($bagian === '..') {
                // Inilah Zip Slip: mencoba keluar dari folder tujuan.
                throw new RuntimeException("Paket mencoba keluar dari foldernya: {$nama}");
            }
        }

        return str_ends_with($nama, '/') ? '' : $nama;
    }

    /**
     * Isi berkas harus benar-benar gambar, bukan sekadar berekstensi gambar.
     *
     * Dimensi ikut diperiksa: PNG 10 KB bisa melebar jadi 4 GB saat didekompresi.
     */
    private static function tipeGambar(string $path, string $nama): void
    {
        $info = @getimagesize($path);

        if ($info === false) {
            throw new RuntimeException("Berkas {$nama} bukan gambar yang valid.");
        }

        $lebar = (int) $info[0];
        $tinggi = (int) $info[1];

        if ($lebar < 1 || $tinggi < 1) {
            throw new RuntimeException("Dimensi gambar {$nama} tidak terbaca.");
        }

        if ($lebar > self::MAKS_LEBAR || $tinggi > self::MAKS_TINGGI) {
            throw new RuntimeException(sprintf(
                'Gambar %s berukuran %dx%d px, batasnya %dx%d.',
                $nama, $lebar, $tinggi, self::MAKS_LEBAR, self::MAKS_TINGGI,
            ));
        }
    }

    /**
     * Tulis satu entri ke disk.
     *
     * Sumbernya `getStream()`, bukan `extractTo()`, jadi tidak ada satupun nama
     * dari dalam arsip yang dipakai untuk menyusun path tujuan.
     */
    private static function tulisKe(ZipArchive $arsip, string $entri, ?Filesystem $disk, string $tujuan): void
    {
        $sumber = $arsip->getStream($entri);

        if ($sumber === false) {
            throw new RuntimeException("Entri {$entri} tidak bisa dibaca.");
        }

        if ($disk !== null) {
            $disk->put($tujuan, stream_get_contents($sumber));

            return;
        }

        $keluaran = fopen($tujuan, 'wb');
        stream_copy_to_stream($sumber, $keluaran);
        fclose($keluaran);
    }

    /**
     * @return array<string, string> nama asli => nama tersimpan
     */
    public function petaGambar(): array
    {
        $disk = Storage::disk(ExamQuestion::DISK);
        $path = $this->berkas.self::MANIFEST;

        if (! $disk->exists($path)) {
            return [];
        }

        $isi = json_decode((string) $disk->get($path), true);

        return is_array($isi) ? array_map('strval', $isi) : [];
    }

    /**
     * Nama berkas acak untuk sebuah referensi gambar. Mengembalikan string
     * kosong bila referensinya tidak dikenal — pemanggil wajib memeriksa.
     */
    public function berkasGambar(string $referensi): string
    {
        if (trim($referensi) === '') {
            return '';
        }

        return $this->petaGambar()[basename(trim($referensi))] ?? '';
    }

    public function spreadsheet(): string
    {
        return $this->berkas.self::NAMA_SOAL;
    }

    /**
     * Nama berkas acak yang hanya berisi heksadesimal.
     *
     * PENTING: bukan `Str::random()` — itu menghasilkan huruf besar-kecil dan
     * angka, sedangkan route pratinjau hanya menerima pola `[a-f0-9]{40}`. Kalau
     * polanya dilonggarkan supaya accommodate `Str::random()`, proteksinya ikut
     * melemah. Jadi polanya disatukan di sini dan dipakai bersama oleh route.
     */
    public static function namaAcak(): string
    {
        return bin2hex(random_bytes(20));
    }

    public const POLA_NAMA = '/^[a-f0-9]{40}\.(jpg|jpeg|png|webp|gif)$/';

    public static function namaBerkasSah(string $berkas): bool
    {
        return preg_match(self::POLA_NAMA, $berkas) === 1;
    }

    public function hapus(): void
    {
        Storage::disk(ExamQuestion::DISK)->deleteDirectory(ExamQuestion::IMPORT_DIR.$this->token);
    }
}
