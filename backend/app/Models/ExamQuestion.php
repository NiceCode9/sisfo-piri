<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ExamQuestion extends Model
{
    use HasFactory;

    /**
     * Gambar soal TIDAK boleh hidup di disk `public`: URL `/storage/...`
     * bisa diambil tanpa login, dan isinya justru bank soal ujian yang
     * selama ini dilindungi dari kebocoran. Disk yang sama dengan Materi dan
     * PengumpulanTugas; disajikan lewat route yang memeriksa hak akses.
     */
    public const DISK = 'berkas';

    public const IMAGE_DIR = 'cbt/questions';

    /**
     * Folder sementara untuk paket import yang sedang direview. Berisi
     * `manifest.json`, berkas `.xlsx`, dan gambar yang sudah diacak ulang
     * namanya. Disapu setelah 24 jam — lihat `cbt:expire-sessions`.
     */
    public const IMPORT_DIR = 'cbt/import/';

    protected $fillable = [
        'exam_id', 'question_bank_id', 'source_question_id', 'question_text', 'question_image', 'type', 'options', 'correct_answer', 'score', 'order',
    ];

    protected $casts = [
        'options' => 'array',
        'correct_answer' => 'array',
    ];

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function bank(): BelongsTo
    {
        return $this->belongsTo(QuestionBank::class, 'question_bank_id');
    }

    public function sourceQuestion(): BelongsTo
    {
        return $this->belongsTo(self::class, 'source_question_id');
    }

    public function imageExists(): bool
    {
        return $this->question_image !== null
            && Storage::disk(self::DISK)->exists($this->question_image);
    }

    /**
     * Hapus berkas gambar, tapi hanya bila tidak ada baris soal lain yang masih
     * menunjuk path yang sama.
     *
     * `ExamController::importFromBank()` menyalin `question_image` apa adanya,
     * jadi baris di bank dan salinannya di ujian menunjuk SATU file. Tanpa
     * pengecekan ini, menghapus soal di bank akan ikut menghapus gambar yang
     * masih dipakai soal ujian yang sedang berjalan.
     */
    public function hapusGambarJikaTakDipakai(): void
    {
        if ($this->question_image === null) {
            return;
        }

        $dipakaiLain = static::query()
            ->where('question_image', $this->question_image)
            ->whereKeyNot($this->getKey())
            ->exists();

        if ($dipakaiLain) {
            return;
        }

        Storage::disk(self::DISK)->delete($this->question_image);
    }
}
