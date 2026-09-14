<?php

namespace App\Http\Controllers\Ortu;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ortu\UpdateProfilRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ProfilController extends Controller
{
    public function show(): View
    {
        return view('ortu.profil');
    }

    public function update(UpdateProfilRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        if (! empty($validated['password'])) {
            auth()->user()->update(['password' => $validated['password']]);
        }

        return redirect()->route('ortu.profil')->with('success', 'Password diperbarui.');
    }
}
