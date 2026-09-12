<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Http\Requests\Siswa\UpdateProfilRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProfilController extends Controller
{
    public function show(): View
    {
        $user = auth()->user();
        $siswa = $user->siswa()->with(['kelas', 'tahunAjaran'])->first();

        return view('siswa.profil', ['siswa' => $siswa]);
    }

    public function update(UpdateProfilRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $user = auth()->user();
        $siswa = $user->siswa;

        if (! empty($validated['password'])) {
            $user->update(['password' => $validated['password']]);
        }

        if ($siswa) {
            $data = collect($validated)->only([
                'nama_ayah', 'pekerjaan_ayah', 'nama_ibu', 'pekerjaan_ibu', 'no_hp_orang_tua',
            ])->toArray();

            if ($request->hasFile('foto')) {
                if ($siswa->foto_path) {
                    Storage::disk('public')->delete($siswa->foto_path);
                }
                $data['foto_path'] = $request->file('foto')->store('foto-siswa', 'public');
            }

            $siswa->update($data);
        }

        return redirect()->route('siswa.profil')->with('success', 'Profil diperbarui.');
    }
}
