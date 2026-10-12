<?php

namespace App\Http\Controllers\Admin\Cbt;

use App\Http\Controllers\Controller;
use App\Imports\SoalCbtImport;
use App\Imports\SoalCbtTemplateExport;
use App\Models\Exam;
use App\Models\ExamQuestion;
use App\Models\Guru;
use App\Models\QuestionBank;
use App\Support\Berkas;
use App\Support\PaketSoal;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Import soal CBT dari paket ZIP berisi spreadsheet + folder gambar.
 *
 * Alurnya dua tahap dan itu disengaja:
 *
 * 1. POST paket -> paket dibongkar, Excel dibaca, hasilnya dikembalikan sebagai
 *    draft. **Tidak ada yang menyentuh database.**
 * 2. Guru menyunting draft di layar review, lalu menekan submit. Baru saat itu
 *    soal dibuat.
 *
 * Cara ini penting karena modul ini belum punya endpoint edit soal — kalau
 * langsung menyimpan, satu baris yang salah hanya bisa dihapus, dan menghapus
 * soal ujian ikut menghapus jawaban siswa. Layar review adalah satu-satunya
 * kesempatan memperbaiki.
 */
class SoalImportController extends Controller implements HasMiddleware
{
    use AuthorizesRequests;

    public static function middleware(): array
    {
        return [new Middleware('permission:cbt.manage')];
    }

    /**
     * Unduh template Excel + contoh soal bergambar.
     */
    public function template(): BinaryFileResponse
    {
        return Excel::download(new SoalCbtTemplateExport, 'template-import-soal-cbt.xlsx');
    }

    /**
     * Tahap 1 — bongkar paket dan baca spreadsheet. Tidak menyimpan apa pun.
     */
    public function unggah(Request $request, Exam $exam): RedirectResponse
    {
        $this->authorize('update', $exam);

        $request->validate([
            'paket' => ['required', 'file', 'mimes:zip', 'max:51200'],
        ]);

        try {
            $paket = PaketSoal::dari($request->file('paket'));
        } catch (RuntimeException $e) {
            return back()->withErrors(['paket' => $e->getMessage()]);
        }

        $import = new SoalCbtImport;

        try {
            Excel::import($import, Storage::disk(ExamQuestion::DISK)->path($paket->spreadsheet()));
        } catch (\Throwable $e) {
            $paket->hapus();

            return back()->withErrors(['paket' => 'Spreadsheet tidak bisa dibaca: '.$e->getMessage()]);
        }

        if ($import->drafts === []) {
            $paket->hapus();

            return back()->withErrors([
                'paket' => 'Tidak ada baris yang bisa diproses. Periksa format kolom pada template.',
            ]);
        }

        $drafts = $this->lengkapiGambar($import->drafts, $paket);

        // Draft disimpan di session, bukan di database: import ini stateless.
        // Session yang membuat layar refresh tetap menampilkan draft yang sama
        // tanpa harus mengunggah ulang.
        session()->put('cbt-import-'.$paket->token, [
            'drafts' => $drafts,
            'gagal' => $import->customFailures,
        ]);

        return redirect()->route('admin.cbt.import.review', [
            'exam' => $exam->id,
            'token' => $paket->token,
        ]);
    }

    /**
     * Layar review. Isinya berasal dari session, jadi harus selalu ada; kalau
     * tidak (mis. browser lain atau lewat 24 jam), guru diminta mengunggah ulang.
     */
    public function review(Request $request, Exam $exam, string $token): View|RedirectResponse
    {
        $this->authorize('update', $exam);

        $paket = session()->get('cbt-import-'.$token);

        if (! is_array($paket) || ! isset($paket['drafts'])) {
            return redirect()
                ->route('admin.cbt.exams.show', $exam)
                ->withErrors(['paket' => 'Sesi import sudah berakhir. Silakan unggah paket lagi.']);
        }

        $banks = QuestionBank::where('guru_id', Guru::where('user_id', $request->user()->id)->value('id'))
            ->where('mata_pelajaran_id', $exam->mata_pelajaran_id)
            ->orderBy('nama')
            ->get();

        return view('admin.cbt.import.review', [
            'exam' => $exam,
            'token' => $token,
            'drafts' => $paket['drafts'],
            'gagal' => $paket['gagal'] ?? [],
            'banks' => $banks,
        ]);
    }

    /**
     * Tahap 2 — simpan draft yang sudah disunting guru.
     *
     * Validasi diulang di sini: payload datang dari peramban, jadi tidak boleh
     * dipercaya begitu saja hanya karena lolos layar review.
     */
    public function simpan(Request $request, Exam $exam, string $token): RedirectResponse
    {
        $this->authorize('update', $exam);

        $data = $request->validate([
            'bank_id' => ['required', 'integer', 'exists:question_banks,id'],
            'rows' => ['required', 'array', 'max:'.SoalCbtImport::MAKS_BARIS],
            'rows.*.question_text' => ['required', 'string', 'max:5000'],
            'rows.*.type' => ['required', 'in:single_choice,multiple_choice,essay'],
            'rows.*.score' => ['required', 'integer', 'min:1', 'max:100'],
            'rows.*.order' => ['nullable', 'integer', 'min:0'],
            'rows.*.gambar' => ['nullable', 'string', 'max:255'],
            'rows.*.options' => ['array'],
            'rows.*.options.*.key' => ['required', 'string', 'max:2'],
            'rows.*.options.*.text' => ['required', 'string', 'max:1000'],
            'rows.*.correct_answer' => ['array'],
            'rows.*.correct_answer.*' => ['nullable', 'string', 'max:2'],
        ]);

        $bank = QuestionBank::findOrFail($data['bank_id']);
        $this->authorize('update', $bank);

        if ((int) $bank->mata_pelajaran_id !== (int) $exam->mata_pelajaran_id) {
            return back()->withErrors(['bank_id' => 'Bank soal harus mata pelajaran yang sama dengan ujian.']);
        }

        $paket = new PaketSoal($token, ExamQuestion::IMPORT_DIR.$token.'/');

        if (! Storage::disk(ExamQuestion::DISK)->exists($paket->berkas.PaketSoal::MANIFEST)) {
            return back()->withErrors([
                'rows' => 'Paket sudah kedaluwarsa atau tidak ditemukan. Silakan unggah ulang.',
            ]);
        }

        [$tersimpan, $ditolak] = $this->simpanRows($exam, $bank, $data['rows'], $paket);

        // Paket sudah tidak dibutuhkan setelah submit; gambar yang terpakai
        // sudah dipindah ke folder soal.
        $paket->hapus();

        if ($tersimpan === 0) {
            return back()->withErrors(['rows' => 'Tidak ada soal yang tersimpan.']);
        }

        return redirect()
            ->route('admin.cbt.exams.show', $exam)
            ->with('success', "{$tersimpan} soal diimpor".$this->suffixGagal($ditolak).'.');
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array{0:int, 1:array<int, string>}
     */
    private function simpanRows(Exam $exam, QuestionBank $bank, array $rows, PaketSoal $paket): array
    {
        $berkas = new Berkas;
        $tersimpan = 0;
        $ditolak = [];

        DB::transaction(function () use ($rows, $exam, $bank, $paket, $berkas, &$tersimpan, &$ditolak): void {
            foreach ($rows as $index => $row) {
                $tipe = (string) $row['type'];
                $opsi = $this->bersihkanOpsi($row['options'] ?? []);
                $kunci = array_values(array_filter(array_map(
                    fn ($k) => strtolower(trim((string) $k)),
                    $row['correct_answer'] ?? [],
                ), fn ($k) => $k !== ''));

                $masalah = $this->masalahBaris($tipe, $opsi, $kunci);

                if ($masalah !== null) {
                    $ditolak[] = 'Baris '.($index + 1).": {$masalah}";

                    continue;
                }

                // Gambar dipindahkan dari folder import ke folder soal. Nama
                // barunya diacak, jadi nama dari luar tidak pernah jadi path.
                $sumber = $paket->berkasGambar((string) ($row['gambar'] ?? ''));
                $path = null;

                if ($sumber !== '') {
                    $path = PaketSoal::namaAcak().'.'.pathinfo($sumber, PATHINFO_EXTENSION);
                    Storage::disk(ExamQuestion::DISK)->put(
                        ExamQuestion::IMAGE_DIR.$path,
                        Storage::disk(ExamQuestion::DISK)->get($paket->berkas.$sumber),
                    );
                    $berkas->ganti(ExamQuestion::IMAGE_DIR.$path);
                }

                $isi = [
                    'question_text' => $row['question_text'],
                    'type' => $tipe,
                    'options' => $tipe === 'essay' ? null : $opsi,
                    'correct_answer' => $tipe === 'essay' ? null : $kunci,
                    'score' => (int) $row['score'],
                    'order' => (int) ($row['order'] ?? $index + 1),
                    'question_image' => $path,
                ];

                $soalUjian = ExamQuestion::create(['exam_id' => $exam->id, ...$isi]);

                // Dual-write: salinan di bank memakai PATH GAMBAR YANG SAMA.
                // Aman karena ExamQuestion::hapusGambarJikaTakDipakai()
                // mengecek apakah baris lain masih mereferensikannya.
                ExamQuestion::create([
                    'exam_id' => null,
                    'question_bank_id' => $bank->id,
                    ...$isi,
                    'source_question_id' => $soalUjian->id,
                ]);

                $tersimpan++;
            }
        });

        try {
            $berkas->hapusYangSudahTidakDipakai();
        } catch (\Throwable $e) {
            // Berkas gagal commit -> buang yang baru supaya tidak menggantung.
            $berkas->buangYangBaru();

            throw $e;
        }

        // Soal yang baru disimpan harus langsung terlihat siswa. Cache soal
        // bertahan 6 jam (Api\Cbt\QuestionController), jadi tanpa ini siswa
        // yang sempat membuka soal sebelum import tetap melihat daftar lama
        // meski guru baru saja menambah puluhan soal.
        //
        // Dipanggil setelah transaksi, bukan di dalamnya: kalau forget berjalan
        // lalu transaksi di-rollback, cache akan terisi ulang oleh request siswa
        // berikutnya dengan data yang justru tidak lagi sinkron.
        if ($tersimpan > 0) {
            Cache::forget("exam:{$exam->id}:questions");
        }

        return [$tersimpan, $ditolak];
    }

    /**
     * @param  array<int, mixed>  $opsi
     * @return array<int, array{key:string, text:string}>
     */
    private function bersihkanOpsi(array $opsi): array
    {
        $hasil = [];

        foreach ($opsi as $o) {
            $teks = trim((string) ($o['text'] ?? ''));

            if ($teks === '') {
                continue;
            }

            $hasil[] = [
                'key' => strtolower(trim((string) ($o['key'] ?? ''))),
                'text' => $teks,
            ];
        }

        return $hasil;
    }

    /**
     * @param  array<int, array{key:string, text:string}>  $opsi
     * @param  list<string>  $kunci
     */
    private function masalahBaris(string $tipe, array $opsi, array $kunci): ?string
    {
        if ($tipe === 'essay') {
            return null;
        }

        if ($opsi === []) {
            return 'soal pilihan harus punya minimal satu opsi';
        }

        if ($tipe === 'single_choice' && count($kunci) !== 1) {
            return 'pilihan tunggal butuh tepat satu kunci';
        }

        if ($tipe === 'multiple_choice' && count($kunci) < 2) {
            return 'pilihan jamak butuh minimal dua kunci';
        }

        $hilang = array_diff($kunci, array_column($opsi, 'key'));

        if ($hilang !== []) {
            return 'kunci '.implode(', ', $hilang).' tidak ada di opsi';
        }

        return null;
    }

    /**
     * Pasangkan draft dengan URL pratinjau gambarnya.
     *
     * Referensi yang tidak ditemukan di paket TIDAK menggagalkan baris — soal
     * teksnya tetap berguna, dan guru bisa melihat sendiri di layar review
     * bahwa gambarnya kosong.
     *
     * @param  array<int, array<string, mixed>>  $drafts
     * @return array<int, array<string, mixed>>
     */
    private function lengkapiGambar(array $drafts, PaketSoal $paket): array
    {
        $token = $paket->token;

        foreach ($drafts as $i => $draft) {
            $berkas = $paket->berkasGambar((string) $draft['gambar']);

            $drafts[$i]['gambar_ada'] = $berkas !== '';
            $drafts[$i]['gambar_url'] = $berkas === '' ? null : URL::temporarySignedRoute(
                'admin.cbt.import.gambar',
                now()->addMinutes(30),
                ['token' => $token, 'berkas' => $berkas],
            );
        }

        return $drafts;
    }

    /**
     * @param  list<string>  $ditolak
     */
    private function suffixGagal(array $ditolak): string
    {
        if ($ditolak === []) {
            return '';
        }

        $n = count($ditolak);

        return $n === 1
            ? ' (1 baris ditolak: '.$ditolak[0].')'
            : " ({$n} baris ditolak)";
    }
}
