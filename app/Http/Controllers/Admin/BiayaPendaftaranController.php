<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreBiayaPendaftaranRequest;
use App\Http\Requests\Admin\UpdateBiayaPendaftaranRequest;
use App\Models\BiayaPendaftaran;
use App\Models\TahunAjaran;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class BiayaPendaftaranController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:biaya-pendaftarans.view', only: ['index']),
            new Middleware('permission:biaya-pendaftarans.create', only: ['create', 'store']),
            new Middleware('permission:biaya-pendaftarans.edit', only: ['edit', 'update']),
            new Middleware('permission:biaya-pendaftarans.delete', only: ['destroy']),
        ];
    }

    public function index(): View
    {
        $biayas = BiayaPendaftaran::with('tahunAjaran')
            ->when(request('search'), fn ($q, $s) => $q->where('jenis_biaya', 'like', "%{$s}%"))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.biaya-pendaftarans.index', compact('biayas'));
    }

    public function create(): View
    {
        return view('admin.biaya-pendaftarans.create', [
            'tahunAjarans' => TahunAjaran::orderByDesc('tanggal_mulai')->get(),
            'tahunAktif' => TahunAjaran::aktif()->first(),
        ]);
    }

    public function store(StoreBiayaPendaftaranRequest $request): RedirectResponse
    {
        $biaya = BiayaPendaftaran::create($request->validated());

        return redirect()->route('admin.biaya-pendaftarans.index')->with('success', "Biaya {$biaya->jenis_biaya} berhasil ditambahkan.");
    }

    public function edit(BiayaPendaftaran $biayaPendaftaran): View
    {
        return view('admin.biaya-pendaftarans.edit', [
            'biaya' => $biayaPendaftaran,
            'tahunAjarans' => TahunAjaran::orderByDesc('tanggal_mulai')->get(),
        ]);
    }

    public function update(UpdateBiayaPendaftaranRequest $request, BiayaPendaftaran $biayaPendaftaran): RedirectResponse
    {
        $biayaPendaftaran->update($request->validated());

        return redirect()->route('admin.biaya-pendaftarans.index')->with('success', "Biaya {$biayaPendaftaran->jenis_biaya} diperbarui.");
    }

    public function destroy(BiayaPendaftaran $biayaPendaftaran): RedirectResponse
    {
        $biayaPendaftaran->delete();

        return redirect()->route('admin.biaya-pendaftarans.index')->with('success', "Biaya {$biayaPendaftaran->jenis_biaya} dihapus.");
    }
}
