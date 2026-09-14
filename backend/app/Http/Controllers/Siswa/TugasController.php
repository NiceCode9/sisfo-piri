<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Models\PengumpulanTugas;
use App\Models\Rombel;
use App\Models\Tugas;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class TugasController extends Controller
{
    public function index(): View
    {
        $siswa = auth()->user()->siswa;
        $rombels = $siswa ? Rombel::where('kelas_id', $siswa->kelas_id)->where('tahun_ajaran_id', $siswa->tahun_ajaran_id)->pluck('id') : collect();

        $tugas = Tugas::with(['mataPelajaran', 'rombel.kelas'])
            ->whereIn('rombel_id', $rombels)
            ->where('is_aktif', true)
            ->latest()
            ->paginate(10);

        $dikumpul = PengumpulanTugas::where('siswa_id', $siswa?->id)->pluck('tugas_id')->all();

        return view('siswa.tugas.index', ['tugas' => $tugas, 'dikumpul' => $dikumpul]);
    }

    public function show(Tugas $tugas): View
    {
        $siswa = auth()->user()->siswa;
        abort_unless($siswa && $tugas->rombel_id === Rombel::where('kelas_id', $siswa->kelas_id)->where('tahun_ajaran_id', $siswa->tahun_ajaran_id)->first()?->id, 403);

        $pengumpulan = PengumpulanTugas::where('tugas_id', $tugas->id)->where('siswa_id', $siswa->id)->first();
        $tugas->load(['mataPelajaran', 'rombel.kelas']);

        return view('siswa.tugas.show', compact('tugas', 'pengumpulan'));
    }

    public function kumpul(Request $request, Tugas $tugas): RedirectResponse
    {
        $siswa = auth()->user()->siswa;
        abort_unless($siswa && $tugas->rombel_id === Rombel::where('kelas_id', $siswa->kelas_id)->where('tahun_ajaran_id', $siswa->tahun_ajaran_id)->first()?->id, 403);

        $request->validate([
            'file' => ['nullable', 'file', 'max:10240', 'mimes:pdf,doc,docx,ppt,pptx,xls,xlsx,mp4,mov,avi,mp3,jpg,jpeg,png'],
            'jawaban_text' => ['nullable', 'string', 'max:5000'],
        ]);

        if (! $request->hasFile('file') && ! $request->filled('jawaban_text')) {
            return back()->withErrors(['file' => 'File atau jawaban wajib diisi.'])->withInput();
        }

        $data = [
            'tugas_id' => $tugas->id,
            'siswa_id' => $siswa->id,
            'jawaban_text' => $request->input('jawaban_text'),
            'is_terlambat' => $tugas->deadline ? now()->greaterThan($tugas->deadline) : false,
        ];

        $existing = PengumpulanTugas::where('tugas_id', $tugas->id)->where('siswa_id', $siswa->id)->first();

        if ($request->hasFile('file')) {
            if ($existing?->file_path) {
                Storage::disk('public')->delete($existing->file_path);
            }
            $data['file_path'] = $request->file('file')->store('tugas', 'public');
        }

        PengumpulanTugas::updateOrCreate(
            ['tugas_id' => $tugas->id, 'siswa_id' => $siswa->id],
            $data
        );

        return redirect()->route('siswa.tugas.show', $tugas)->with('success', 'Tugas dikumpulkan.');
    }
}
