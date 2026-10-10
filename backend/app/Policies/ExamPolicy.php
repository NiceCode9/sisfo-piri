<?php

namespace App\Policies;

use App\Models\Exam;
use App\Models\Guru;
use App\Models\Pengampu;
use App\Models\Rombel;
use App\Models\User;

/**
 * Isolasi rombel ditegakkan lewat {@see Rombel::terjangkauOleh()}, bukan lewat
 * daftar rombel yang ditulis ulang per controller — sama seperti MateriPolicy
 * dan TugasPolicy. Guru tanpa penugasan menghasilkan NOL, bukan semua.
 *
 * Beda dengan materi: sebuah ujian terikat rombel **dan** mata pelajaran,
 * jadi guru yang hanya wali kelas (bukan pengampu mapel) tetap tidak boleh
 * menyentuh ujian mapel orang lain di kelas itu.
 */
class ExamPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('cbt.view');
    }

    public function view(User $user, Exam $exam): bool
    {
        if ($user->hasRole(Rombel::ROLE_UNIVERSAL)) {
            return true;
        }

        if ($exam->rombel_id === null || $exam->mata_pelajaran_id === null) {
            return false;
        }

        if (! Rombel::terjangkauOleh($user, $exam->rombel_id)) {
            return false;
        }

        $guru = Guru::where('user_id', $user->id)->first();

        return $guru !== null && Pengampu::where('guru_id', $guru->id)
            ->where('mata_pelajaran_id', $exam->mata_pelajaran_id)
            ->exists();
    }

    public function create(User $user): bool
    {
        return $user->can('cbt.manage');
    }

    public function update(User $user, Exam $exam): bool
    {
        return $this->view($user, $exam) && $user->can('cbt.manage');
    }

    public function delete(User $user, Exam $exam): bool
    {
        return $this->update($user, $exam);
    }
}
