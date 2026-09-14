<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreJalurPendaftaranRequest;
use App\Http\Requests\Admin\UpdateJalurPendaftaranRequest;
use App\Models\JalurPendaftaran;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class JalurPendaftaranController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:jalur-pendaftarans.view', only: ['index']),
            new Middleware('permission:jalur-pendaftarans.create', only: ['create', 'store']),
            new Middleware('permission:jalur-pendaftarans.edit', only: ['edit', 'update']),
            new Middleware('permission:jalur-pendaftarans.delete', only: ['destroy']),
        ];
    }

    public function index(): View
    {
        $jalurs = JalurPendaftaran::withCount(['kuotaPendaftaran', 'calonSiswa'])
            ->when(request('search'), fn ($q, $s) => $q->where('nama_jalur', 'like', "%{$s}%"))
            ->orderBy('nama_jalur')
            ->paginate(10)
            ->withQueryString();

        return view('admin.jalur-pendaftarans.index', compact('jalurs'));
    }

    public function create(): View
    {
        return view('admin.jalur-pendaftarans.create');
    }

    public function store(StoreJalurPendaftaranRequest $request): RedirectResponse
    {
        $jalur = JalurPendaftaran::create($request->validated());

        return redirect()->route('admin.jalur-pendaftarans.index')->with('success', "Jalur {$jalur->nama_jalur} berhasil ditambahkan.");
    }

    public function edit(JalurPendaftaran $jalurPendaftaran): View
    {
        return view('admin.jalur-pendaftarans.edit', ['jalur' => $jalurPendaftaran]);
    }

    public function update(UpdateJalurPendaftaranRequest $request, JalurPendaftaran $jalurPendaftaran): RedirectResponse
    {
        $jalurPendaftaran->update($request->validated());

        return redirect()->route('admin.jalur-pendaftarans.index')->with('success', "Jalur {$jalurPendaftaran->nama_jalur} diperbarui.");
    }

    public function destroy(JalurPendaftaran $jalurPendaftaran): RedirectResponse
    {
        if ($jalurPendaftaran->kuotaPendaftaran()->exists() || $jalurPendaftaran->calonSiswa()->exists()) {
            return back()->with('error', "Jalur {$jalurPendaftaran->nama_jalur} masih memiliki data terkait dan tidak dapat dihapus.");
        }

        $jalurPendaftaran->delete();

        return redirect()->route('admin.jalur-pendaftarans.index')->with('success', "Jalur {$jalurPendaftaran->nama_jalur} dihapus.");
    }
}
