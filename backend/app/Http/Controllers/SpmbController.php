<?php

namespace App\Http\Controllers;

use App\Http\Requests\Spmb\StorePendaftaranRequest;
use App\Models\BiayaPendaftaran;
use App\Models\Brosur;
use App\Models\CalonSiswa;
use App\Models\Galeri;
use App\Models\Gelombang;
use App\Models\JadwalPpdb;
use App\Models\JalurPendaftaran;
use App\Models\KuotaPendaftaran;
use App\Models\LogStatusPendaftaran;
use App\Models\Pengumuman;
use App\Models\TahunAjaran;
use App\Models\User;
use App\Support\Penomor;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SpmbController extends Controller
{
    public function home(): View
    {
        $tahunAjaranAktif = TahunAjaran::aktif()->first();
        $jadwalPpdbs = $tahunAjaranAktif
            ? JadwalPpdb::where('tahun_ajaran_id', $tahunAjaranAktif->id)->orderBy('tanggal_mulai')->get()
            : collect();
        $biayas = $tahunAjaranAktif
            ? BiayaPendaftaran::where('tahun_ajaran_id', $tahunAjaranAktif->id)->orderBy('jenis_biaya')->get()
            : collect();
        $pengumumans = Pengumuman::where('status_aktif', true)->orderByDesc('tanggal_pengumuman')->limit(3)->get();
        $gelombangs = $tahunAjaranAktif
            ? Gelombang::where('tahun_ajaran_id', $tahunAjaranAktif->id)->where('is_aktif', true)->orderBy('nomor_urut')->get()
            : Gelombang::where('is_aktif', true)->orderBy('nomor_urut')->get();
        $brosurs = Brosur::aktif()->orderBy('order')->orderByDesc('created_at')->get();
        $galeriFotos = Galeri::aktif()->foto()->whereNotNull('image_path')->orderBy('order')->orderByDesc('created_at')->take(8)->get();
        $prestasis = Galeri::aktif()->prestasi()->orderByDesc('tanggal')->orderBy('order')->take(3)->get();

        return view('spmb.home', [
            'tahunAjaranAktif' => $tahunAjaranAktif,
            'jadwalPpdbs' => $jadwalPpdbs,
            'biayas' => $biayas,
            'pengumumans' => $pengumumans,
            'gelombangs' => $gelombangs,
            'brosurs' => $brosurs,
            'galeriFotos' => $galeriFotos,
            'prestasis' => $prestasis,
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

        $passwordPlain = null;
        $newUser = null;

        try {
            $calon = DB::transaction(function () use ($validated, $tahunAjaranAktif, $request, &$passwordPlain, &$newUser) {
                // Kuota pendaftaran (batas jumlah pendaftar), bukan kuota penerimaan.
                $kuota = KuotaPendaftaran::kunci($tahunAjaranAktif->id, (int) $validated['jalur_pendaftaran_id']);

                if ($kuota?->pendaftaranPenuh()) {
                    throw new \RuntimeException('Kuota pendaftaran untuk jalur ini sudah penuh.');
                }

                $passwordPlain = Str::random(8);

                $newUser = User::create([
                    'username' => $validated['nisn'],
                    'name' => $validated['nama_lengkap'],
                    'email' => $validated['email'],
                    'password' => $passwordPlain,
                ]);
                $newUser->assignRole('siswa');

                $calon = CalonSiswa::create([
                    'jalur_pendaftaran_id' => $validated['jalur_pendaftaran_id'],
                    'tahun_ajaran_id' => $tahunAjaranAktif->id,
                    'user_id' => $newUser->id,
                    'no_pendaftaran' => Penomor::placeholder('PPDB'),
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
                foreach (['ijazah_path', 'kk_path', 'akta_path', 'foto_path', 'skl_path', 'krm_path', 'kip_path'] as $field) {
                    if ($request->hasFile($field)) {
                        $berkasData[$field] = $request->file($field)->store('berkas', 'berkas');
                    }
                }

                $calon->berkasCalonSiswa()->create($berkasData);

                foreach ($request->file('sertifikat', []) as $i => $item) {
                    $calon->sertifikatPrestasis()->create([
                        'nama_sertifikat' => $request->input("sertifikat.{$i}.nama", 'Sertifikat Prestasi'),
                        'file_path' => $item['file']->store('berkas/sertifikat', 'berkas'),
                    ]);
                }

                LogStatusPendaftaran::create([
                    'calon_siswa_id' => $calon->id,
                    'status_sebelumnya' => null,
                    'status_baru' => 'menunggu',
                    'user_id' => $newUser->id,
                    'catatan' => 'Pendaftaran via form publik',
                ]);

                Penomor::calon($calon);

                // Pendaftar sah masuk, jadi kuotanya bertambah. Baris sudah dikunci
                // di atas sehingga langkah ini aman dari pendaftaran bersamaan.
                $kuota?->increment('terisi_pendaftaran');

                return $calon;
            });
        } catch (UniqueConstraintViolationException $e) {
            // Jaring pengaman bila ada kolom unik yang belum tertutup rule. Pesan
            // SQL mentah tidak boleh sampai ke pengunjung publik.
            $field = Str::contains($e->getMessage(), 'email') ? 'email' : 'nisn';

            return back()->withErrors([
                $field => $field === 'email'
                    ? 'Email ini sudah terdaftar. Gunakan email lain.'
                    : 'NISN sudah terdaftar sebagai akun. Gunakan NISN lain atau hubungi admin.',
            ])->withInput();
        } catch (QueryException $e) {
            report($e);

            return back()->withErrors([
                'email' => 'Pendaftaran gagal diproses. Silakan coba beberapa saat lagi.',
            ])->withInput();
        } catch (\RuntimeException $e) {
            return back()->withErrors(['jalur_pendaftaran_id' => $e->getMessage()])->withInput();
        }

        return redirect()->route('spmb.pendaftaran')->with('success', 'Pendaftaran berhasil! No: '.$calon->no_pendaftaran.' | Username: '.$newUser->username.' | Password: '.$passwordPlain.' — Simpan kredensial ini untuk login memantau status.');
    }

    public function pengumumanIndex(): View
    {
        $pengumumans = Pengumuman::where('status_aktif', true)->orderByDesc('tanggal_pengumuman')->paginate(9);

        return view('spmb.pengumuman.index', compact('pengumumans'));
    }

    public function pengumumanShow(Pengumuman $pengumuman): View
    {
        abort_unless($pengumuman->status_aktif, 404);

        return view('spmb.pengumuman.show', compact('pengumuman'));
    }

    public function about()
    {
        return view('spmb.about');
    }
}
