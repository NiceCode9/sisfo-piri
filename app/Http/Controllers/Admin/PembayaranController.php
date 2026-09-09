<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePembayaranRequest;
use App\Http\Requests\Admin\UpdatePembayaranRequest;
use App\Models\BiayaPendaftaran;
use App\Models\CalonSiswa;
use App\Models\Pembayaran;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class PembayaranController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:pembayarans.view', only: ['index', 'show']),
            new Middleware('permission:pembayarans.create', only: ['create', 'store']),
            new Middleware('permission:pembayarans.edit', only: ['edit', 'update', 'updateStatus']),
            new Middleware('permission:pembayarans.delete', only: ['destroy']),
        ];
    }

    public function index(): View
    {
        $pembayarans = Pembayaran::with(['calonSiswa', 'biayaPendaftaran'])
            ->when(request('search'), fn ($q, $s) => $q->where('kode_pembayaran', 'like', "%{$s}%")
                ->orWhereHas('calonSiswa', fn ($qq) => $qq->where('nama_lengkap', 'like', "%{$s}%")))
            ->when(request('status'), fn ($q, $v) => $q->where('status', $v))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.pembayarans.index', compact('pembayarans'));
    }

    public function create(): View
    {
        return view('admin.pembayarans.create', [
            'calons' => CalonSiswa::with('jalurPendaftaran')->orderByDesc('created_at')->limit(100)->get(),
            'biayas' => BiayaPendaftaran::orderBy('jenis_biaya')->get(),
        ]);
    }

    public function store(StorePembayaranRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $validated['kode_pembayaran'] = $this->generateKode();
        $validated['status'] = $validated['status'] ?? ($request->hasFile('bukti_pembayaran_path') ? 'berhasil' : 'menunggu');
        $validated['jenis_pembayaran'] = $validated['jenis_pembayaran'] ?? 'penuh';

        if ($request->hasFile('bukti_pembayaran_path')) {
            $validated['bukti_pembayaran_path'] = $request->file('bukti_pembayaran_path')->store('bukti', 'public');
            $validated['tanggal_pembayaran'] = $validated['tanggal_pembayaran'] ?? now()->toDateString();
        }

        $pembayaran = Pembayaran::create($validated);

        return redirect()->route('admin.pembayarans.index')->with('success', "Pembayaran {$pembayaran->kode_pembayaran} berhasil dibuat.");
    }

    public function show(Pembayaran $pembayaran): View
    {
        $pembayaran->load(['calonSiswa.jalurPendaftaran', 'biayaPendaftaran', 'detailAngsuran']);

        return view('admin.pembayarans.show', compact('pembayaran'));
    }

    public function edit(Pembayaran $pembayaran): View
    {
        return view('admin.pembayarans.edit', [
            'pembayaran' => $pembayaran,
            'calons' => CalonSiswa::orderByDesc('created_at')->limit(100)->get(),
            'biayas' => BiayaPendaftaran::orderBy('jenis_biaya')->get(),
        ]);
    }

    public function update(UpdatePembayaranRequest $request, Pembayaran $pembayaran): RedirectResponse
    {
        $validated = $request->validated();

        if ($request->hasFile('bukti_pembayaran_path')) {
            if ($pembayaran->bukti_pembayaran_path) {
                Storage::disk('public')->delete($pembayaran->bukti_pembayaran_path);
            }
            $validated['bukti_pembayaran_path'] = $request->file('bukti_pembayaran_path')->store('bukti', 'public');
        }

        $pembayaran->update($validated);

        return redirect()->route('admin.pembayarans.index')->with('success', "Pembayaran {$pembayaran->kode_pembayaran} diperbarui.");
    }

    public function updateStatus(Pembayaran $pembayaran): RedirectResponse
    {
        request()->validate(['status' => ['required', 'in:menunggu,berhasil,gagal']]);
        $pembayaran->update(['status' => request('status')]);

        return back()->with('success', 'Status pembayaran diubah ke '.request('status').'.');
    }

    public function destroy(Pembayaran $pembayaran): RedirectResponse
    {
        if ($pembayaran->bukti_pembayaran_path) {
            Storage::disk('public')->delete($pembayaran->bukti_pembayaran_path);
        }
        $pembayaran->delete();

        return redirect()->route('admin.pembayarans.index')->with('success', 'Pembayaran dihapus.');
    }

    private function generateKode(): string
    {
        $year = date('Y');
        $count = Pembayaran::whereYear('created_at', $year)->count() + 1;

        return sprintf('PAY-%s-%04d', $year, $count);
    }
}
