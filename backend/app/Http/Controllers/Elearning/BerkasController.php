<?php

namespace App\Http\Controllers\Elearning;

use App\Http\Controllers\Controller;
use App\Models\Materi;
use App\Models\PengumpulanTugas;
use App\Models\Rombel;
use App\Models\Siswa;
use App\Models\Tugas;
use App\Models\User;
use App\Models\WaliMurid;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Penyaji berkas E-Learning: lampiran materi dan berkas tugas siswa.
 *
 * Kedua jenis berkas ini dulu ditulis ke disk `public`, sehingga URL
 * `/storage/tugas/...` bisa diambil siapa pun tanpa login — termasuk
 * berkas jawaban siswa yang belum dinilai dan materi kelas yang belum
 * diumumkan. Sekarang keduanya hanya hidup di disk privat dan dibaca
 * lewat route di sini.
 *
 * Aturan pembacaan berkas tugas sengaja lebih ketat daripada materi:
 * berkas tugas milik satu siswa hanya boleh dibaca siswa itu sendiri,
 * waliMurid-nya, dan guru yang mengampu rombelnya. Teman sekelas dan
 * waliMurid lain tidak termasuk, meskipun mereka bisa melihat halaman
 * rekap milik masing-masing.
 */
class BerkasController extends Controller
{
    public function materi(Materi $materi): StreamedResponse
    {
        abort_if(! $materi->file_path, 404);

        $user = request()->user();

        abort_unless($this->bolehLihatMateri($user, $materi), 403);

        return $this->alirkan($materi->file_path, Materi::DISK);
    }

    public function pengumpulan(PengumpulanTugas $pengumpulan): StreamedResponse
    {
        abort_if(! $pengumpulan->file_path, 404);

        $user = request()->user();

        abort_unless($this->bolehLihatPengumpulan($user, $pengumpulan), 403);

        return $this->alirkan($pengumpulan->file_path, PengumpulanTugas::DISK);
    }

    private function bolehLihatMateri(?User $user, Materi $materi): bool
    {
        abort_if($user === null, 401);

        if ($user->hasRole(Rombel::ROLE_UNIVERSAL)) {
            return $user->can('materis.view');
        }

        // Materi non-aktif hanya boleh dilihat pengelola, bukan siswa/wali:
        // status `is_aktif` adalah cara guru mengumandahkan materi, jadi
        // halaman detail harus menegakkan hal yang sama agar tidak bisa
        // dibuka dengan menebak ID.
        if (! $materi->is_aktif && ! Rombel::terjangkauOleh($user, $materi->rombel_id)) {
            return false;
        }

        if (Rombel::terjangkauOleh($user, $materi->rombel_id)) {
            return true;
        }

        $rombel = Rombel::find($materi->rombel_id);

        if (! $rombel) {
            return false;
        }

        $anakIds = WaliMurid::where('user_id', $user->id)->pluck('siswa_id');
        $anakIds = $anakIds->merge(
            Siswa::where('user_id', $user->id)->pluck('id')
        );

        return $anakIds->intersect($rombel->anggotaIdsAktif())->isNotEmpty();
    }

    private function bolehLihatPengumpulan(?User $user, PengumpulanTugas $pengumpulan): bool
    {
        abort_if($user === null, 401);

        if ($user->hasRole(Rombel::ROLE_UNIVERSAL)) {
            return $user->can('tugas.view');
        }

        $tugas = $pengumpulan->tugas;

        if (! $tugas) {
            return false;
        }

        if (Rombel::terjangkauOleh($user, $tugas->rombel_id)) {
            return true;
        }

        if (Siswa::where('user_id', $user->id)->where('id', $pengumpulan->siswa_id)->exists()) {
            return true;
        }

        return WaliMurid::where('user_id', $user->id)
            ->where('siswa_id', $pengumpulan->siswa_id)
            ->exists();
    }

    private function alirkan(string $path, string $disk): StreamedResponse
    {
        abort_unless(Storage::disk($disk)->exists($path), 404);

        return Storage::disk($disk)->response($path);
    }
}
