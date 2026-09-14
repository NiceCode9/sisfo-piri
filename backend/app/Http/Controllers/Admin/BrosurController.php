<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreBrosurRequest;
use App\Http\Requests\Admin\UpdateBrosurRequest;
use App\Models\Brosur;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class BrosurController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:brosurs.view', only: ['index']),
            new Middleware('permission:brosurs.create', only: ['create', 'store']),
            new Middleware('permission:brosurs.edit', only: ['edit', 'update']),
            new Middleware('permission:brosurs.delete', only: ['destroy']),
        ];
    }

    public function index(): View
    {
        $brosurs = Brosur::when(request('search'), fn ($q, $s) => $q->where('title', 'like', "%{$s}%"))
            ->orderBy('order')
            ->orderByDesc('created_at')
            ->paginate(10)
            ->withQueryString();

        return view('admin.brosurs.index', compact('brosurs'));
    }

    public function create(): View
    {
        return view('admin.brosurs.create');
    }

    public function store(StoreBrosurRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        if ($request->hasFile('image')) {
            $validated['image_path'] = $request->file('image')->store('brosur', 'public');
        }
        unset($validated['image']);

        $brosur = Brosur::create($validated);

        return redirect()->route('admin.brosurs.index')->with('success', "Brosur {$brosur->title} berhasil ditambahkan.");
    }

    public function edit(Brosur $brosur): View
    {
        return view('admin.brosurs.edit', compact('brosur'));
    }

    public function update(UpdateBrosurRequest $request, Brosur $brosur): RedirectResponse
    {
        $validated = $request->validated();

        if ($request->hasFile('image')) {
            if ($brosur->image_path) {
                Storage::disk('public')->delete($brosur->image_path);
            }
            $validated['image_path'] = $request->file('image')->store('brosur', 'public');
        }
        unset($validated['image']);

        $brosur->update($validated);

        return redirect()->route('admin.brosurs.index')->with('success', "Brosur {$brosur->title} diperbarui.");
    }

    public function destroy(Brosur $brosur): RedirectResponse
    {
        if ($brosur->image_path) {
            Storage::disk('public')->delete($brosur->image_path);
        }
        $brosur->delete();

        return redirect()->route('admin.brosurs.index')->with('success', "Brosur {$brosur->title} dihapus.");
    }
}
