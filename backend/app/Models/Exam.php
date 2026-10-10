<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Exam extends Model
{
    use HasFactory;

    protected $fillable = [
        'rombel_id', 'mata_pelajaran_id', 'name', 'description', 'duration_minutes', 'available_from', 'available_until',
        'max_violation_count', 'shuffle_questions', 'shuffle_options', 'status', 'created_by',
        'is_remedial', 'remedial_of_id',
    ];

    protected $casts = [
        'available_from' => 'datetime',
        'available_until' => 'datetime',
        'shuffle_questions' => 'boolean',
        'shuffle_options' => 'boolean',
        'is_remedial' => 'boolean',
    ];

    public function rombel(): BelongsTo
    {
        return $this->belongsTo(Rombel::class);
    }

    public function mataPelajaran(): BelongsTo
    {
        return $this->belongsTo(MataPelajaran::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(ExamQuestion::class);
    }

    public function tokens(): HasMany
    {
        return $this->hasMany(ExamToken::class);
    }

    public function participants(): HasMany
    {
        return $this->hasMany(ExamParticipant::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(ExamSession::class);
    }

    /**
     * Ujian asal yang nilainya digantikan oleh remedial ini di rapor.
     * Reminder: satu ujian boleh punya banyak anak (remedial 1, remedial 2),
     * yang kedua-duanya menunjuk ujian asal yang sama.
     */
    public function remedialOf(): BelongsTo
    {
        return $this->belongsTo(self::class, 'remedial_of_id');
    }

    public function remidals(): HasMany
    {
        return $this->hasMany(self::class, 'remedial_of_id');
    }

    /**
     * Kunci kelompok untuk rekap nilai.
     *
     * Ujian biasa menjadi kelompoknya sendiri; semua remedial yang menunjuk
     * ujian yang sama berbagi satu kelompok. Inilah yang membuat UH-1 dan
     * UH-2 (keduanya bukan remedial) tidak pernah ikut digabung.
     */
    public function nilaiKelompok(): int
    {
        return $this->remedial_of_id ?? $this->id;
    }
}
