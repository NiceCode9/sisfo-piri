<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * Membaca baris Excel paket soal menjadi draft untuk layar review.
 *
 * Baris TIDAK disimpan ke database di sini. Resultan import ini hanya mengisi
 * layar review; penyimpanan baru terjadi setelah guru menekan submit. Itu
 * penting karena modul ini belum punya endpoint edit soal — layar review adalah
 * satu-satunya kesempatan guru memperbaiki sebelum data masuk.
 *
 * Kegagalan yang tidak bisa ditangani aturan statis — kunci yang tidak menunjuk
 * opsi, tipe yang tidak dikenal — dikumpulkan di {@see $customFailures} lengkap
 * dengan nomor baris, supaya guru tahu persis baris mana yang bermasalah.
 *
 * @implements ToCollection<int, array<string, mixed>>
 */
class SoalCbtImport implements SkipsOnFailure, ToCollection, WithHeadingRow
{
    use SkipsFailures;

    public const MAKS_BARIS = 200;

    /** Nilai kolom `tipe` yang dipahami, termasuk beberapa sinonim. */
    public const TIPE = [
        'single' => 'single_choice',
        'single_choice' => 'single_choice',
        'tunggal' => 'single_choice',
        'multiple' => 'multiple_choice',
        'multiple_choice' => 'multiple_choice',
        'jamak' => 'multiple_choice',
        'essay' => 'essay',
        'uraian' => 'essay',
    ];

    /** @var array<int, array<string, mixed>> draft siap review */
    public array $drafts = [];

    /**
     * Baris yang tidak bisa diproses, beserta alasannya.
     *
     * @var array<int, array{baris:int, alasan:string}>
     */
    public array $customFailures = [];

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     */
    public function collection(Collection $rows): void
    {
        foreach ($rows as $index => $row) {
            // +2: baris heading Excel adalah baris pertama.
            $nomorBaris = $index + 2;

            if ($nomorBaris > self::MAKS_BARIS) {
                $this->gagal($nomorBaris, 'Melebihi batas '.self::MAKS_BARIS.' soal per import.');

                continue;
            }

            $draft = $this->draftBaris($row->toArray(), $nomorBaris);

            if ($draft !== null) {
                $this->drafts[] = $draft;
            }
        }
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>|null
     */
    private function draftBaris(array $row, int $nomorBaris): ?array
    {
        $teks = trim((string) ($row['teks'] ?? ''));

        if ($teks === '') {
            $this->gagal($nomorBaris, 'Kolom teks soal kosong.');

            return null;
        }

        $tipeMasuk = strtolower(trim((string) ($row['tipe'] ?? '')));
        $tipe = self::TIPE[$tipeMasuk] ?? null;

        if ($tipe === null) {
            $this->gagal($nomorBaris, sprintf(
                'Tipe soal "%s" tidak dikenal. Gunakan: single, multiple, atau essay.',
                $row['tipe'] ?? '(kosong)',
            ));

            return null;
        }

        $opsi = $this->opsiDariKolom($row);
        $kunci = $this->kunciDariKolom($row);

        if ($tipe !== 'essay' && $opsi === []) {
            $this->gagal($nomorBaris, 'Soal pilihan harus punya minimal satu opsi yang terisi.');

            return null;
        }

        if ($tipe === 'single_choice' && count($kunci) !== 1) {
            $this->gagal($nomorBaris, 'Soal pilihan tunggal butuh tepat satu kunci, ditemukan '.$kunciTerbaca($kunci).'.');

            return null;
        }

        if ($tipe === 'multiple_choice' && count($kunci) < 2) {
            $this->gagal($nomorBaris, 'Soal pilihan jamak butuh minimal dua kunci, ditemukan '.$kunciTerbaca($kunci).'.');

            return null;
        }

        if ($tipe !== 'essay' && $kunci !== []) {
            $hilang = array_diff($kunci, array_column($opsi, 'key'));

            if ($hilang !== []) {
                $this->gagal($nomorBaris, 'Kunci '.implode(', ', $hilang).' tidak ada di daftar opsi.');

                return null;
            }
        }

        $skor = (int) ($row['skor'] ?? 10);

        if ($skor < 1 || $skor > 100) {
            $this->gagal($nomorBaris, "Skor harus antara 1 sampai 100, ditemukan {$skor}.");

            return null;
        }

        return [
            'baris' => $nomorBaris,
            'question_text' => $teks,
            'type' => $tipe,
            'options' => $opsi,
            'correct_answer' => $kunci,
            'score' => $skor,
            'order' => (int) ($row['urutan'] ?? 0),
            'gambar' => trim((string) ($row['gambar'] ?? '')),
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<int, array{key:string, text:string}>
     */
    private function opsiDariKolom(array $row): array
    {
        $opsi = [];

        foreach (['a', 'b', 'c', 'd', 'e'] as $huruf) {
            $teks = trim((string) ($row['opsi_'.$huruf] ?? ''));

            if ($teks !== '') {
                $opsi[] = ['key' => $huruf, 'text' => $teks];
            }
        }

        return $opsi;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return list<string>
     */
    private function kunciDariKolom(array $row): array
    {
        $mentah = strtolower(trim((string) ($row['kunci'] ?? '')));

        if ($mentah === '') {
            return [];
        }

        $pecah = preg_split('/[;,|]/', $mentah) ?: [];

        return array_values(array_unique(array_filter(array_map(
            fn ($k) => strtolower(trim($k)),
            $pecah
        ), fn ($k) => $k !== '')));
    }

    private function gagal(int $baris, string $alasan): void
    {
        $this->customFailures[] = ['baris' => $baris, 'alasan' => $alasan];
    }
}

/**
 * @param  list<string>  $kunci
 */
function kunciTerbaca(array $kunci): string
{
    return $kunci === [] ? 'tidak ada' : implode(', ', $kunci);
}
