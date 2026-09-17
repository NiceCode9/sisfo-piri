<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuestionBank extends Model
{
    protected $fillable = [
        'mata_pelajaran_id', 'guru_id', 'nama', 'deskripsi', 'is_shared', 'created_by',
    ];

    protected $casts = [
        'is_shared' => 'boolean',
    ];

    public function mataPelajaran(): BelongsTo
    {
        return $this->belongsTo(MataPelajaran::class);
    }

    public function guru(): BelongsTo
    {
        return $this->belongsTo(Guru::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(ExamQuestion::class, 'question_bank_id');
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeForGuru($query, int $guruId)
    {
        return $query->where('guru_id', $guruId);
    }
}
