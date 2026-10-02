<?php

namespace App\Policies;

use App\Models\Rombel;
use App\Models\Tugas;
use App\Models\User;

class TugasPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('tugas.view');
    }

    public function view(User $user, Tugas $tugas): bool
    {
        if ($user->hasRole(Rombel::ROLE_UNIVERSAL)) {
            return true;
        }

        return Rombel::terjangkauOleh($user, $tugas->rombel_id);
    }

    public function create(User $user): bool
    {
        return $user->can('tugas.create');
    }

    public function update(User $user, Tugas $tugas): bool
    {
        return $this->view($user, $tugas) && $user->can('tugas.edit');
    }

    public function delete(User $user, Tugas $tugas): bool
    {
        return $this->view($user, $tugas) && $user->can('tugas.delete');
    }

    /**
     * Menilai terpisah dari `update` supaya permission `tugas.nilai` tidak
     * otomatis ikut berlaku saat guru boleh menyunting tugas.
     */
    public function nilai(User $user, Tugas $tugas): bool
    {
        return $this->view($user, $tugas) && $user->can('tugas.nilai');
    }
}
