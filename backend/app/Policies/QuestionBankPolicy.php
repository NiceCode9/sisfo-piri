<?php

namespace App\Policies;

use App\Models\Guru;
use App\Models\Pengampu;
use App\Models\QuestionBank;
use App\Models\User;

class QuestionBankPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('cbt.view');
    }

    public function view(User $user, QuestionBank $bank): bool
    {
        if ($user->hasRole(['super-admin', 'admin'])) {
            return true;
        }

        $guru = Guru::where('user_id', $user->id)->first();

        return $guru !== null && $bank->guru_id === $guru->id;
    }

    public function create(User $user): bool
    {
        if (! $user->can('cbt.manage')) {
            return false;
        }

        if ($user->hasRole(['super-admin', 'admin'])) {
            return true;
        }

        $guru = Guru::where('user_id', $user->id)->first();

        return $guru !== null;
    }

    public function update(User $user, QuestionBank $bank): bool
    {
        return $this->view($user, $bank) && $user->can('cbt.manage');
    }

    public function delete(User $user, QuestionBank $bank): bool
    {
        return $this->update($user, $bank);
    }

    /**
     * Cek apakah user mengampu mapel tersebut (lintas rombel).
     */
    public static function isPengampuForMapel(User $user, int $mataPelajaranId): bool
    {
        if ($user->hasRole(['super-admin', 'admin'])) {
            return true;
        }

        $guru = Guru::where('user_id', $user->id)->first();

        if ($guru === null) {
            return false;
        }

        return Pengampu::where('guru_id', $guru->id)
            ->where('mata_pelajaran_id', $mataPelajaranId)
            ->exists();
    }
}
