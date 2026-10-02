<?php

namespace App\Policies;

use App\Models\Materi;
use App\Models\Rombel;
use App\Models\User;

/**
 * Isolasi rombel ditegakkan di sini, bukan lewat pemisahan permission.
 *
 * Permission `materis.*` sengaja tetap diberikan penuh kepada role `guru`
 * agar guru bisa mengelola materinya sendiri tanpa kehilangan akses; yang
 * membatasi adalah rombel mana yang boleh ia sentuh. Memecah permission
 * per-guru hanya menambah daftar permission tanpa menambah keamanan, karena
 * yang tetap perlu dicek tetap "apakah rombel ini miliknya".
 */
class MateriPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('materis.view');
    }

    public function view(User $user, Materi $materi): bool
    {
        if ($user->hasRole(Rombel::ROLE_UNIVERSAL)) {
            return true;
        }

        return Rombel::terjangkauOleh($user, $materi->rombel_id);
    }

    public function create(User $user): bool
    {
        return $user->can('materis.create');
    }

    public function update(User $user, Materi $materi): bool
    {
        return $this->view($user, $materi) && $user->can('materis.edit');
    }

    public function delete(User $user, Materi $materi): bool
    {
        return $this->view($user, $materi) && $user->can('materis.delete');
    }
}
