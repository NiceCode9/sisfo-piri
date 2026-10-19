<?php

namespace App\Http\Controllers\Admin;

use App\Exports\GuruTemplateExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreGuruRequest;
use App\Http\Requests\Admin\UpdateGuruRequest;
use App\Imports\GuruImport;
use App\Models\Guru;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class GuruController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:gurus.view', only: ['index']),
            new Middleware('permission:gurus.create', only: ['create', 'store', 'template', 'import']),
            new Middleware('permission:gurus.edit', only: ['edit', 'update']),
            new Middleware('permission:gurus.delete', only: ['destroy']),
        ];
    }

    public function index(): View
    {
        $gurus = Guru::with('user')
            ->when(request('search'), fn ($q, $s) => $q->where(fn ($qq) => $qq
                ->where('nama', 'like', "%{$s}%")
                ->orWhere('nip', 'like', "%{$s}%")
            ))
            ->orderBy('nama')
            ->paginate(10)
            ->withQueryString();

        return view('admin.gurus.index', compact('gurus'));
    }

    public function create(): View
    {
        return view('admin.gurus.create');
    }

    public function store(StoreGuruRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($validated) {
            $user = User::create([
                'username' => $validated['telp'],
                'name' => $validated['nama'],
                'password' => GuruImport::PASSWORD_BAWAAN,
            ]);
            $user->assignRole('guru');

            Guru::create([
                'user_id' => $user->id,
                'nip' => $validated['nip'] ?? null,
                'nama' => $validated['nama'],
                'jenis_kelamin' => $validated['jenis_kelamin'],
                'telp' => $validated['telp'] ?? null,
                'alamat' => $validated['alamat'] ?? null,
                'is_aktif' => $validated['is_aktif'],
            ]);
        });

        return redirect()->route('admin.gurus.index')->with('success', "Guru {$validated['nama']} berhasil ditambahkan beserta akun login.");
    }

    public function template(): BinaryFileResponse
    {
        return Excel::download(new GuruTemplateExport, 'template-import-guru.xlsx');
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
        ]);

        $import = new GuruImport;
        Excel::import($import, $request->file('file'));

        $gagal = $import->failures();
        $ringkas = "dibuat {$import->imported}, diperbarui {$import->updated}";
        $jumlahGagal = $gagal->count() + count($import->customFailures);

        if ($jumlahGagal > 0) {
            $daftar = $gagal->take(10)
                ->map(fn ($f) => 'Baris '.$f->row().': '.implode(', ', $f->errors()))
                ->merge($import->customFailures)
                ->take(10)
                ->implode(' | ');

            return back()->with('error', "Import selesai: {$ringkas}, {$jumlahGagal} gagal. {$daftar}");
        }

        return redirect()->route('admin.gurus.index')->with('success', "Import selesai: {$ringkas}.");
    }

    public function edit(Guru $guru): View
    {
        return view('admin.gurus.edit', compact('guru'));
    }

    public function update(UpdateGuruRequest $request, Guru $guru): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($validated, $guru) {
            $userData = [
                'username' => $validated['telp'],
                'name' => $validated['nama'],
                'password' => GuruImport::PASSWORD_BAWAAN,
            ];
            $guru->user()->updateOrCreate([], $userData);
            $guru->user->assignRole('guru');

            $guru->update([
                'nip' => $validated['nip'] ?? null,
                'nama' => $validated['nama'],
                'jenis_kelamin' => $validated['jenis_kelamin'],
                'telp' => $validated['telp'] ?? null,
                'alamat' => $validated['alamat'] ?? null,
                'is_aktif' => $validated['is_aktif'],
            ]);
        });

        return redirect()->route('admin.gurus.index')->with('success', "Guru {$guru->nama} diperbarui.");
    }

    public function destroy(Guru $guru): RedirectResponse
    {
        if ($guru->pengampus()->exists() || $guru->waliRombels()->exists()) {
            return back()->with('error', "Guru {$guru->nama} masih memiliki penugasan/wali rombel dan tidak dapat dihapus. Nonaktifkan saja.");
        }

        DB::transaction(function () use ($guru) {
            $user = $guru->user;
            $guru->delete();
            $user?->delete();
        });

        return redirect()->route('admin.gurus.index')->with('success', "Guru {$guru->nama} dihapus.");
    }
}
