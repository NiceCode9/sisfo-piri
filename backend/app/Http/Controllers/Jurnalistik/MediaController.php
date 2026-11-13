<?php

namespace App\Http\Controllers\Jurnalistik;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class MediaController extends Controller
{
    public function index(): View
    {
        $files = collect(Storage::disk('public')->files('media'))
            ->map(fn ($path) => [
                'path' => $path,
                'url' => Storage::disk('public')->url($path),
                'name' => basename($path),
            ]);

        return view('jurnalistik.media.index', compact('files'));
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $request->validate([
            'file' => ['required', 'image', 'max:4096'],
        ]);

        $path = $request->file('file')->store('media', 'public');
        $url = Storage::disk('public')->url($path);

        if ($request->expectsJson()) {
            return response()->json(['location' => $url, 'path' => $path]);
        }

        return redirect()->route('jurnalistik.media.index')->with('success', 'Media diunggah.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $path = (string) $request->input('path');
        if (str_starts_with($path, 'media/') && ! str_contains($path, '..') && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }

        return redirect()->route('jurnalistik.media.index')->with('success', 'Media dihapus.');
    }
}
