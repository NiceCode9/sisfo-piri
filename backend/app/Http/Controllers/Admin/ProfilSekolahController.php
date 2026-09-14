<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateProfilSekolahRequest;
use App\Models\ProfilSekolah;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProfilSekolahController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:profil-sekolahs.view', only: ['edit']),
            new Middleware('permission:profil-sekolahs.edit', only: ['update']),
        ];
    }

    /**
     * Form kelola profil tunggal (singleton).
     */
    public function edit(): View
    {
        $profil = ProfilSekolah::firstOrCreate(['nama_sekolah' => 'SMKN Ngaglik']);

        return view('admin.profil-sekolah.edit', compact('profil'));
    }

    /**
     * Perbarui profil tunggal (file lama dihapus bila diganti).
     */
    public function update(UpdateProfilSekolahRequest $request): RedirectResponse
    {
        $profil = ProfilSekolah::firstOrFail();
        $validated = $request->validated();

        foreach (['logo' => 'logo_path', 'foto_gedung' => 'foto_gedung_path', 'foto_kepala' => 'foto_kepala_path'] as $input => $column) {
            if ($request->hasFile($input)) {
                if ($profil->$column) {
                    Storage::disk('public')->delete($profil->$column);
                }
                $validated[$column] = $request->file($input)->store('profil', 'public');
            }
            unset($validated[$input]);
        }

        $validated['misi'] = array_values(array_filter($validated['misi'] ?? []));

        $profil->update($validated);

        return redirect()->route('admin.profil-sekolah.edit')->with('success', 'Profil sekolah berhasil diperbarui.');
    }
}
