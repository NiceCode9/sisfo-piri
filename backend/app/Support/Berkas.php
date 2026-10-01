<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

/**
 * Mencatat berkas yang perlu dihapus dari disk dan membersihkannya pada waktu
 * yang tepat.
 *
 * Masalah yang dipecahkan: `Storage::delete()` yang dijalankan sebelum commit
 * database membuat baris menunjuk file yang sudah hilang (link 404 di panel
 * verifikasi) bila transaksiьюgagal atau di-rollback. Sebaliknya, file baru
 * yang tersimpan lalu gagal di-commit akan menggantung sebagai yatim.
 *
 * Pola pakai:
 *
 *     $berkas = new Berkas;
 *     try {
 *         DB::transaction(function () use ($berkas) {
 *             $pathBaru = $file->store(...);
 *             $berkas->ganti($pathBaru, $row->path_lama);
 *             $row->update(['path_lama' => $pathBaru]);
 *         });
 *     } catch (\Throwable $e) {
 *         $berkas->buangYangBaru();   // file baru tidak pernah dirujuk
 *         throw $e;
 *     }
 *     $berkas->hapusYangSudahTidakDipakai();
 */
class Berkas
{
    /** @var list<string> File lama yang sudah tidak dirujuk baris mana pun. */
    private array $terpakai = [];

    /** @var list<string> File baru yang disimpan selama transaksi berjalan. */
    private array $baru = [];

    /**
     * Catat penggantian satu berkas: simpan path baru (dan path lama bila ada).
     *
     * @param  string|null  $pathLama  Path sebelum penggantian, jika ada.
     */
    public function ganti(?string $pathBaru, ?string $pathLama = null): void
    {
        if ($pathBaru) {
            $this->baru[] = $pathBaru;
        }

        if ($pathLama && $pathLama !== $pathBaru) {
            $this->terpakai[] = $pathLama;
        }
    }

    /**
     * Catat path yang harus dihapus karena barisnya dihapus (mis._destroy()).
     */
    public function hapus(?string $path): void
    {
        if ($path) {
            $this->terpakai[] = $path;
        }
    }

    /**
     * Dipanggil setelah transaksi berhasil commit: file lama baru aman dihapus
     * karena tidak ada baris yang menunjuknya.
     */
    public function hapusYangSudahTidakDipakai(): void
    {
        $this->hapusPath($this->terpakai);
        $this->terpakai = [];
    }

    /**
     * Dipanggil saat transaksi gagal: file baru tidak pernah dirujuk baris
     * mana pun, jadi harus dibuang agar tidak menggantung di disk.
     */
    public function buangYangBaru(): void
    {
        $this->hapusPath($this->baru);
        $this->baru = [];
    }

    /**
     * @param  list<string>  $paths
     */
    private function hapusPath(array $paths): void
    {
        if ($paths === []) {
            return;
        }

        Storage::disk('berkas')->delete(array_values(array_unique($paths)));
    }
}
