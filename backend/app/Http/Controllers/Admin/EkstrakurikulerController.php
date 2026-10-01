<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreEkstrakurikulerRequest;
use App\Http\Requests\Admin\UpdateEkstrakurikulerRequest;
use App\Models\Ekstrakurikuler;
use App\Models\Guru;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class EkstrakurikulerController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:ekstrakurikulers.view', only: ['index']),
            new Middleware('permission:ekstrakurikulers.create', only: ['create', 'store']),
            new Middleware('permission:ekstrakurikulers.edit', only: ['edit', 'update']),
            new Middleware('permission:ekstrakurikulers.delete', only: ['destroy']),
        ];
    }

    public function index(): View
    {
        $ekstrakurikulers = Ekstrakurikuler::query()
            ->when(request('search'), fn ($q, $s) => $q->where(
                fn ($qq) => $qq->where('kode', 'like', "%{$s}%")
                    ->orWhere('nama', 'like', "%{$s}%")
                    ->orWhere('pembina', 'like', "%{$s}%")
            ))
            ->orderBy('kode')
            ->paginate(10)
            ->withQueryString();

        return view('admin.ekstrakurikulers.index', compact('ekstrakurikulers'));
    }

    public function create(): View
    {
        $gurus = Guru::aktif()->orderBy('nama')->get(['id', 'nama']);

        return view('admin.ekstrakurikulers.create', compact('gurus'));
    }

    public function store(StoreEkstrakurikulerRequest $request): RedirectResponse
    {
        $ekstrakurikuler = Ekstrakurikuler::create($request->safe()->except(['pembina_pilih', 'pembina_manual']));

        return redirect()
            ->route('admin.ekstrakurikulers.index')
            ->with('success', "Ekstrakurikuler {$ekstrakurikuler->nama} berhasil ditambahkan.");
    }

    public function edit(Ekstrakurikuler $ekstrakurikuler): View
    {
        $gurus = Guru::aktif()->orderBy('nama')->get(['id', 'nama']);

        return view('admin.ekstrakurikulers.edit', compact('ekstrakurikuler', 'gurus'));
    }

    public function update(UpdateEkstrakurikulerRequest $request, Ekstrakurikuler $ekstrakurikuler): RedirectResponse
    {
        $ekstrakurikuler->update($request->safe()->except(['pembina_pilih', 'pembina_manual']));

        return redirect()
            ->route('admin.ekstrakurikulers.index')
            ->with('success', "Ekstrakurikuler {$ekstrakurikuler->nama} diperbarui.");
    }

    public function destroy(Ekstrakurikuler $ekstrakurikuler): RedirectResponse
    {
        $ekstrakurikuler->delete();

        return redirect()
            ->route('admin.ekstrakurikulers.index')
            ->with('success', "Ekstrakurikuler {$ekstrakurikuler->nama} dihapus.");
    }
}
