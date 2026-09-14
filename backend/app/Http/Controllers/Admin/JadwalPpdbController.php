<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreJadwalPpdbRequest;
use App\Http\Requests\Admin\UpdateJadwalPpdbRequest;
use App\Models\JadwalPpdb;
use App\Models\TahunAjaran;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class JadwalPpdbController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:jadwal-ppdbs.view', only: ['index']),
            new Middleware('permission:jadwal-ppdbs.create', only: ['create', 'store']),
            new Middleware('permission:jadwal-ppdbs.edit', only: ['edit', 'update']),
            new Middleware('permission:jadwal-ppdbs.delete', only: ['destroy']),
        ];
    }

    public function index(): View
    {
        $tahunAktif = TahunAjaran::aktif()->first();
        $tahunMode = request()->query('tahun', 'aktif');

        $jadwals = JadwalPpdb::with('tahunAjaran')
            ->when(request('search'), fn ($q, $s) => $q->where('nama_jadwal', 'like', "%{$s}%"))
            ->when($tahunMode === 'aktif' && $tahunAktif, fn ($q) => $q->where('tahun_ajaran_id', $tahunAktif->id))
            ->when(is_numeric($tahunMode), fn ($q) => $q->where('tahun_ajaran_id', $tahunMode))
            ->orderBy('tanggal_mulai')
            ->paginate(10)
            ->withQueryString();

        return view('admin.jadwal-ppdbs.index', [
            'jadwals' => $jadwals,
            'tahunAjarans' => TahunAjaran::orderByDesc('tanggal_mulai')->get(),
            'tahunAktif' => $tahunAktif,
            'tahunMode' => $tahunMode,
        ]);
    }

    public function create(): View
    {
        return view('admin.jadwal-ppdbs.create', [
            'tahunAjarans' => TahunAjaran::orderByDesc('tanggal_mulai')->get(),
            'tahunAktif' => TahunAjaran::aktif()->first(),
        ]);
    }

    public function store(StoreJadwalPpdbRequest $request): RedirectResponse
    {
        $jadwal = JadwalPpdb::create($request->validated());

        return redirect()->route('admin.jadwal-ppdbs.index')->with('success', "Jadwal {$jadwal->nama_jadwal} berhasil ditambahkan.");
    }

    public function edit(JadwalPpdb $jadwalPpdb): View
    {
        return view('admin.jadwal-ppdbs.edit', [
            'jadwal' => $jadwalPpdb,
            'tahunAjarans' => TahunAjaran::orderByDesc('tanggal_mulai')->get(),
        ]);
    }

    public function update(UpdateJadwalPpdbRequest $request, JadwalPpdb $jadwalPpdb): RedirectResponse
    {
        $jadwalPpdb->update($request->validated());

        return redirect()->route('admin.jadwal-ppdbs.index')->with('success', "Jadwal {$jadwalPpdb->nama_jadwal} diperbarui.");
    }

    public function destroy(JadwalPpdb $jadwalPpdb): RedirectResponse
    {
        $jadwalPpdb->delete();

        return redirect()->route('admin.jadwal-ppdbs.index')->with('success', "Jadwal {$jadwalPpdb->nama_jadwal} dihapus.");
    }
}
