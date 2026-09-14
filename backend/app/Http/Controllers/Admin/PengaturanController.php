<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdatePengaturanRequest;
use App\Models\Pengaturan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class PengaturanController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:pengaturans.view', only: ['index']),
            new Middleware('permission:pengaturans.edit', only: ['update', 'reset']),
        ];
    }

    public function index(): View
    {
        $kunciList = [
            'batas_terlambat', 'jam_cek_belum_hadir', 'whatsapp_gateway_url', 'cek_belum_hadir_terakhir',
            'semester_aktif', 'batas_upload_mb', 'maintenance_mode', 'maintenance_pesan',
            'rekap_default_periode', 'notifikasi_ortu_aktif',
        ];

        $pengaturan = Pengaturan::whereIn('kunci', $kunciList)->pluck('nilai', 'kunci');

        return view('admin.pengaturans.index', compact('pengaturan'));
    }

    public function update(UpdatePengaturanRequest $request): RedirectResponse
    {
        foreach ($request->validated() as $kunci => $nilai) {
            Pengaturan::updateOrCreate(['kunci' => $kunci], ['nilai' => $nilai === null ? null : (string) $nilai]);
        }

        return redirect()->route('admin.pengaturans.index')->with('success', 'Pengaturan disimpan.');
    }

    public function reset(): RedirectResponse
    {
        Pengaturan::where('kunci', 'cek_belum_hadir_terakhir')->update(['nilai' => null]);

        return redirect()->route('admin.pengaturans.index')->with('success', 'Re-trigger: cek belum-hadir akan jalan lagi hari ini.');
    }
}
