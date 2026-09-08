<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreKuotaPendaftaranRequest;
use App\Http\Requests\Admin\UpdateKuotaPendaftaranRequest;
use App\Models\JalurPendaftaran;
use App\Models\KuotaPendaftaran;
use App\Models\TahunAjaran;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class KuotaPendaftaranController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:kuota-pendaftarans.view', only: ['index']),
            new Middleware('permission:kuota-pendaftarans.create', only: ['create', 'store']),
            new Middleware('permission:kuota-pendaftarans.edit', only: ['edit', 'update']),
            new Middleware('permission:kuota-pendaftarans.delete', only: ['destroy']),
        ];
    }

    public function index(): View
    {
        $kuotas = KuotaPendaftaran::with(['tahunAjaran', 'jalurPendaftaran'])
            ->when(request('tahun'), fn ($q, $t) => $q->where('tahun_ajaran_id', $t))
            ->orderBy('tahun_ajaran_id')
            ->orderBy('jalur_pendaftaran_id')
            ->paginate(10)
            ->withQueryString();

        return view('admin.kuota-pendaftarans.index', [
            'kuotas' => $kuotas,
            'tahunAjarans' => TahunAjaran::orderByDesc('tanggal_mulai')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.kuota-pendaftarans.create', [
            'tahunAjarans' => TahunAjaran::orderByDesc('tanggal_mulai')->get(),
            'tahunAktif' => TahunAjaran::aktif()->first(),
            'jalurs' => JalurPendaftaran::where('aktif', true)->orderBy('nama_jalur')->get(),
        ]);
    }

    public function store(StoreKuotaPendaftaranRequest $request): RedirectResponse
    {
        $kuota = KuotaPendaftaran::create($request->validated());

        return redirect()->route('admin.kuota-pendaftarans.index')->with('success', "Kuota {$kuota->jalurPendaftaran->nama_jalur} berhasil ditambahkan.");
    }

    public function edit(KuotaPendaftaran $kuotaPendaftaran): View
    {
        return view('admin.kuota-pendaftarans.edit', [
            'kuota' => $kuotaPendaftaran,
            'tahunAjarans' => TahunAjaran::orderByDesc('tanggal_mulai')->get(),
            'jalurs' => JalurPendaftaran::where('aktif', true)->orderBy('nama_jalur')->get(),
        ]);
    }

    public function update(UpdateKuotaPendaftaranRequest $request, KuotaPendaftaran $kuotaPendaftaran): RedirectResponse
    {
        $kuotaPendaftaran->update($request->validated());

        return redirect()->route('admin.kuota-pendaftarans.index')->with('success', "Kuota {$kuotaPendaftaran->jalurPendaftaran->nama_jalur} diperbarui.");
    }

    public function destroy(KuotaPendaftaran $kuotaPendaftaran): RedirectResponse
    {
        if ($kuotaPendaftaran->terisi > 0) {
            return back()->with('error', "Kuota {$kuotaPendaftaran->jalurPendaftaran->nama_jalur} sudah terisi {$kuotaPendaftaran->terisi} dan tidak dapat dihapus.");
        }

        $kuotaPendaftaran->delete();

        return redirect()->route('admin.kuota-pendaftarans.index')->with('success', 'Kuota dihapus.');
    }
}
