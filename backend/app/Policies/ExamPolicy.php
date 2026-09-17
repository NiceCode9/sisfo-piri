<?php

namespace App\Policies;

use App\Models\Exam;
use App\Models\Guru;
use App\Models\Pengampu;
use App\Models\User;

class ExamPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('cbt.view');
    }

    public function view(User $user, Exam $exam): bool
    {
        if ($user->hasRole(['super-admin', 'admin'])) {
            return true;
        }

        $guru = Guru::where('user_id', $user->id)->first();

        if ($guru === null || $exam->mata_pelajaran_id === null) {
            return false;
        }

        return Pengampu::where('guru_id', $guru->id)
            ->where('mata_pelajaran_id', $exam->mata_pelajaran_id)
            ->where('rombel_id', $exam->rombel_id)
            ->exists();
    }

    public function create(User $user): bool
    {
        return $user->can('cbt.manage');
    }

    public function update(User $user, Exam $exam): bool
    {
        if (! $user->can('cbt.manage')) {
            return false;
        }

        if ($user->hasRole(['super-admin', 'admin'])) {
            return true;
        }

        return $this->view($user, $exam);
    }

    public function delete(User $user, Exam $exam): bool
    {
        return $this->update($user, $exam);
    }
}
