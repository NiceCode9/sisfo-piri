<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateAkunRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProfilController extends Controller
{
    public function show(): View
    {
        return view('guru.profil', ['guru' => auth()->user()->guru]);
    }

    public function update(UpdateAkunRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $user = auth()->user();

        if ($request->hasFile('foto')) {
            if ($user->foto_path) {
                Storage::disk('public')->delete($user->foto_path);
            }
            $validated['foto_path'] = $request->file('foto')->store('profil', 'public');
        }

        $data = [];
        if (array_key_exists('name', $validated) && $validated['name'] !== null && $validated['name'] !== '') {
            $data['name'] = $validated['name'];
        }
        if (array_key_exists('email', $validated)) {
            $data['email'] = $validated['email'] ?: null;
        }
        if (! empty($validated['password'])) {
            $data['password'] = $validated['password'];
        }
        if (isset($validated['foto_path'])) {
            $data['foto_path'] = $validated['foto_path'];
        }
        if ($data !== []) {
            $user->update($data);
        }

        if (isset($data['name']) && $user->guru) {
            $user->guru->update(['nama' => $data['name']]);
        }

        return redirect()->route('guru.profil')->with('success', 'Profil akun diperbarui.');
    }
}
