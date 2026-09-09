<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Models\CalonSiswa;
use App\Models\Pembayaran;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PembayaranController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'pembayaran_id' => ['required', 'integer', 'exists:pembayarans,id'],
            'bukti_pembayaran_path' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);

        $pembayaran = Pembayaran::findOrFail($request->input('pembayaran_id'));

        // Pastikan pembayaran milik calon siswa yang login
        $user = auth()->user();
        $calonIds = $user->calonSiswa()->pluck('id')->toArray();
        // also via direct CalonSiswa where user_id
        $calonIds = CalonSiswa::where('user_id', $user->id)->pluck('id')->toArray();
        if (! in_array($pembayaran->calon_siswa_id, $calonIds)) {
            abort(403);
        }

        if ($pembayaran->bukti_pembayaran_path) {
            Storage::disk('public')->delete($pembayaran->bukti_pembayaran_path);
        }

        $path = $request->file('bukti_pembayaran_path')->store('bukti', 'public');

        $pembayaran->update([
            'bukti_pembayaran_path' => $path,
            'tanggal_pembayaran' => now()->toDateString(),
            'status' => 'menunggu',
        ]);

        return back()->with('success', 'Bukti pembayaran berhasil diupload, menunggu verifikasi admin.');
    }
}
