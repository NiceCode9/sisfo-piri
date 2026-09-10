<?php

namespace App\Http\Controllers\Admin;

use App\Exports\PembayaranLainnyaExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePembayaranLainnyaRequest;
use App\Http\Requests\Admin\UpdatePembayaranLainnyaRequest;
use App\Models\CalonSiswa;
use App\Models\PembayaranLainnya;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

class PembayaranLainnyaController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:pembayaran-lainnyas.view', only: ['index', 'show', 'exportExcel', 'exportPdf']),
            new Middleware('permission:pembayaran-lainnyas.create', only: ['create', 'store']),
            new Middleware('permission:pembayaran-lainnyas.edit', only: ['edit', 'update', 'updateStatus']),
            new Middleware('permission:pembayaran-lainnyas.delete', only: ['destroy']),
        ];
    }

    public function index(): View
    {
        $pembayarans = $this->filteredQuery()->latest()->paginate(10)->withQueryString();

        return view('admin.pembayaran-lainnyas.index', ['pembayarans' => $pembayarans]);
    }

    /**
     * Query daftar mengikuti filter aktif (dipakai index + export).
     */
    protected function filteredQuery(): Builder
    {
        return PembayaranLainnya::with('calonSiswa')
            ->when(request('search'), fn ($q, $s) => $q->where('kode_pembayaran', 'like', "%{$s}%")
                ->orWhere('nama_biaya', 'like', "%{$s}%")
                ->orWhereHas('calonSiswa', fn ($qq) => $qq->where('nama_lengkap', 'like', "%{$s}%")))
            ->when(request('status'), fn ($q, $v) => $q->where('status', $v))
            ->when(request('tanggal_mulai'), fn ($q, $v) => $q->whereDate('tanggal_pembayaran', '>=', $v))
            ->when(request('tanggal_sampai'), fn ($q, $v) => $q->whereDate('tanggal_pembayaran', '<=', $v));
    }

    public function exportExcel(): BinaryFileResponse
    {
        $pembayarans = $this->filteredQuery()->latest()->get();

        return Excel::download(
            new PembayaranLainnyaExport($pembayarans),
            'pembayaran-lainnya-'.now()->format('Ymd-His').'.xlsx'
        );
    }

    public function exportPdf(): Response
    {
        $pembayarans = $this->filteredQuery()->latest()->get();

        return Pdf::loadView('admin.pembayaran-lainnyas.pdf', [
            'pembayarans' => $pembayarans,
            'total' => $pembayarans->sum('jumlah'),
            'filters' => request()->only(['search', 'status', 'tanggal_mulai', 'tanggal_sampai']),
        ])->download('pembayaran-lainnya-'.now()->format('Ymd-His').'.pdf');
    }

    public function create(): View
    {
        return view('admin.pembayaran-lainnyas.create', [
            'calons' => CalonSiswa::with('jalurPendaftaran')->orderByDesc('created_at')->limit(100)->get(),
        ]);
    }

    public function store(StorePembayaranLainnyaRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $validated['kode_pembayaran'] = $this->generateKode();
        // Input admin selalu langsung berhasil (bukti wajib untuk transfer).
        $validated['status'] = 'berhasil';

        if ($request->hasFile('bukti_pembayaran_path')) {
            $validated['bukti_pembayaran_path'] = $request->file('bukti_pembayaran_path')->store('bukti', 'public');
            $validated['tanggal_pembayaran'] = $validated['tanggal_pembayaran'] ?? now()->toDateString();
        }

        $pembayaran = PembayaranLainnya::create(
            collect($validated)->except('redirect_to')->toArray()
        );

        return $this->redirectAfterStore($request, "Pembayaran lainnya {$pembayaran->kode_pembayaran} berhasil dibuat.");
    }

    public function show(PembayaranLainnya $pembayaranLainnya): View
    {
        $pembayaranLainnya->load('calonSiswa.jalurPendaftaran');

        return view('admin.pembayaran-lainnyas.show', ['pembayaran' => $pembayaranLainnya]);
    }

    public function edit(PembayaranLainnya $pembayaranLainnya): View
    {
        return view('admin.pembayaran-lainnyas.edit', [
            'pembayaran' => $pembayaranLainnya,
            'calons' => CalonSiswa::with('jalurPendaftaran')->orderByDesc('created_at')->limit(100)->get(),
        ]);
    }

    public function update(UpdatePembayaranLainnyaRequest $request, PembayaranLainnya $pembayaranLainnya): RedirectResponse
    {
        $validated = $request->validated();

        if ($request->hasFile('bukti_pembayaran_path')) {
            if ($pembayaranLainnya->bukti_pembayaran_path) {
                Storage::disk('public')->delete($pembayaranLainnya->bukti_pembayaran_path);
            }
            $validated['bukti_pembayaran_path'] = $request->file('bukti_pembayaran_path')->store('bukti', 'public');
            // Upload bukti baru oleh admin berarti terverifikasi.
            $validated['status'] = 'berhasil';
        }

        $pembayaranLainnya->update($validated);

        return redirect()->route('admin.pembayaran-lainnyas.index')->with('success', "Pembayaran lainnya {$pembayaranLainnya->kode_pembayaran} diperbarui.");
    }

    public function updateStatus(PembayaranLainnya $pembayaranLainnya): RedirectResponse
    {
        request()->validate(['status' => ['required', 'in:menunggu,berhasil,gagal']]);
        $pembayaranLainnya->update(['status' => request('status')]);

        return back()->with('success', 'Status pembayaran diubah ke '.request('status').'.');
    }

    public function destroy(PembayaranLainnya $pembayaranLainnya): RedirectResponse
    {
        if ($pembayaranLainnya->bukti_pembayaran_path) {
            Storage::disk('public')->delete($pembayaranLainnya->bukti_pembayaran_path);
        }
        $pembayaranLainnya->delete();

        return redirect()->route('admin.pembayaran-lainnyas.index')->with('success', 'Pembayaran lainnya dihapus.');
    }

    /**
     * Kembali ke show calon bila simpan via modal show,
     * selain itu ke index pembayaran lainnya.
     */
    protected function redirectAfterStore(Request $request, string $message): RedirectResponse
    {
        $redirectTo = $request->input('redirect_to');

        if ($redirectTo && Str::startsWith($redirectTo, url('/admin/calon-siswas/'))) {
            return redirect($redirectTo)->with('success', $message);
        }

        return redirect()->route('admin.pembayaran-lainnyas.index')->with('success', $message);
    }

    private function generateKode(): string
    {
        $year = date('Y');
        $count = PembayaranLainnya::whereYear('created_at', $year)->count() + 1;

        return sprintf('LNN-%s-%04d', $year, $count);
    }
}
