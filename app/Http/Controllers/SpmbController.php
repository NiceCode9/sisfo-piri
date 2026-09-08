<?php

namespace App\Http\Controllers;

use App\Http\Requests\Spmb\StorePendaftaranRequest;
use App\Models\CalonSiswa;
use App\Models\JadwalPpdb;
use App\Models\JalurPendaftaran;
use App\Models\KuotaPendaftaran;
use App\Models\LogStatusPendaftaran;
use App\Models\TahunAjaran;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SpmbController extends Controller
{
    public function home(): View
    {
        $tahunAjaranAktif = TahunAjaran::aktif()->first();
        $jadwalPpdbs = $tahunAjaranAktif
            ? JadwalPpdb::where('tahun_ajaran_id', $tahunAjaranAktif->id)->orderBy('tanggal_mulai')->get()
            : collect();

        return view('spmb.home', [
            'tahunAjaranAktif' => $tahunAjaranAktif,
            'jadwalPpdbs' => $jadwalPpdbs,
        ]);
    }

    public function create(): View
    {
        $tahunAjaranAktif = TahunAjaran::aktif()->first();
        $jalurPendaftarans = JalurPendaftaran::where('aktif', true)->orderBy('nama_jalur')->get();
        $jadwalPpdbs = $tahunAjaranAktif
            ? JadwalPpdb::where('tahun_ajaran_id', $tahunAjaranAktif->id)->orderBy('tanggal_mulai')->get()
            : collect();

        $kuotaMap = collect();
        if ($tahunAjaranAktif) {
            $kuotaMap = KuotaPendaftaran::where('tahun_ajaran_id', $tahunAjaranAktif->id)->get()->keyBy('jalur_pendaftaran_id');
        }

        return view('spmb.pendaftaran', [
            'tahunAjaranAktif' => $tahunAjaranAktif,
            'jalurPendaftarans' => $jalurPendaftarans,
            'jadwalPpdbs' => $jadwalPpdbs,
            'kuotaMap' => $kuotaMap,
        ]);
    }

    public function store(StorePendaftaranRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $tahunAjaranAktif = TahunAjaran::aktif()->first();

        if (! $tahunAjaranAktif) {
            return back()->withErrors(['jalur_pendaftaran_id' => 'Pendaftaran belum dibuka (tahun ajaran aktif belum diatur).'])->withInput();
        }

        try {
            $calon = DB::transaction(function () use ($validated, $tahunAjaranAktif, $request) {
                $kuota = KuotaPendaftaran::where('tahun_ajaran_id', $tahunAjaranAktif->id)
                    ->where('jalur_pendaftaran_id', $validated['jalur_pendaftaran_id'])
                    ->lockForUpdate()
                    ->first();

                if ($kuota && $kuota->terisi >= $kuota->kuota) {
                    throw new \RuntimeException('Kuota untuk jalur ini sudah penuh.');
                }

                $noPendaftaran = sprintf('PPDB-%s-%04d', date('Y'), CalonSiswa::whereYear('created_at', date('Y'))->count() + 1);

                $calon = CalonSiswa::create([
                    'jalur_pendaftaran_id' => $validated['jalur_pendaftaran_id'],
                    'tahun_ajaran_id' => $tahunAjaranAktif->id,
                    'no_pendaftaran' => $noPendaftaran,
                    'nik' => $validated['nik'],
                    'nisn' => $validated['nisn'],
                    'nama_lengkap' => $validated['nama_lengkap'],
                    'jenis_kelamin' => $validated['jenis_kelamin'],
                    'tempat_lahir' => $validated['tempat_lahir'],
                    'tanggal_lahir' => $validated['tanggal_lahir'],
                    'agama' => $validated['agama'],
                    'alamat' => $validated['alamat'],
                    'no_hp' => $validated['no_hp'],
                    'email' => $validated['email'],
                    'asal_sekolah' => $validated['asal_sekolah'],
                    'nama_ayah' => $validated['nama_ayah'],
                    'pekerjaan_ayah' => $validated['pekerjaan_ayah'],
                    'nama_ibu' => $validated['nama_ibu'],
                    'pekerjaan_ibu' => $validated['pekerjaan_ibu'],
                    'no_hp_orang_tua' => $validated['no_hp_orang_tua'],
                    'status_pendaftaran' => 'menunggu',
                ]);

                $berkasData = [];
                foreach (['ijazah_path', 'kk_path', 'akta_path', 'foto_path', 'skl_path'] as $field) {
                    if ($request->hasFile($field)) {
                        $berkasData[$field] = $request->file($field)->store('berkas', 'public');
                    }
                }

                $calon->berkasCalonSiswa()->create($berkasData);

                LogStatusPendaftaran::create([
                    'calon_siswa_id' => $calon->id,
                    'status_sebelumnya' => null,
                    'status_baru' => 'menunggu',
                    'user_id' => null,
                    'catatan' => 'Pendaftaran via form publik',
                ]);

                // terisi only increments when status becomes diterima, not on menunggu — so no increment here

                return $calon;
            });
        } catch (\RuntimeException $e) {
            return back()->withErrors(['jalur_pendaftaran_id' => $e->getMessage()])->withInput();
        }

        return redirect()->route('spmb.pendaftaran')->with('success', 'Pendaftaran berhasil! Nomor pendaftaran Anda: '.$calon->no_pendaftaran);
    }
}
