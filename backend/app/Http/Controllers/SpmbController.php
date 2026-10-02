<?php

namespace App\Http\Controllers;

use App\Exceptions\GelombangTidakTersedia;
use App\Http\Requests\Spmb\StorePendaftaranRequest;
use App\Models\BerkasCalonSiswa;
use App\Models\BiayaPendaftaran;
use App\Models\Brosur;
use App\Models\CalonSiswa;
use App\Models\Ekstrakurikuler;
use App\Models\Galeri;
use App\Models\Gelombang;
use App\Models\JalurPendaftaran;
use App\Models\KuotaPendaftaran;
use App\Models\LogStatusPendaftaran;
use App\Models\Pengumuman;
use App\Models\Rombel;
use App\Models\TahunAjaran;
use App\Models\User;
use App\Support\Penomor;
use App\Support\StatusPendaftaran;
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
        $biayas = $tahunAjaranAktif
            ? BiayaPendaftaran::where('tahun_ajaran_id', $tahunAjaranAktif->id)->orderBy('jenis_biaya')->get()
            : collect();
        $pengumumans = Pengumuman::where('status_aktif', true)->orderByDesc('tanggal_pengumuman')->limit(3)->get();

        // Kartu gelombang menampilkan batch yang BELUM ditutup — termasuk yang
        // masih akan datang, karena "batch berikutnya dibuka tanggal berapa" itu
        // justru informasi yang dicari calon. Batch yang sudah lewat disembunyikan
        // supaya halaman publik tidak menampilkan tanggal basi.
        $gelombangs = Gelombang::semuaAktif($tahunAjaranAktif)
            ->reject(fn (Gelombang $g) => $g->pendaftaranTelahLewat())
            ->values();

        $brosurs = Brosur::aktif()->orderBy('order')->orderByDesc('created_at')->get();
        $galeriFotos = Galeri::aktif()->foto()->whereNotNull('image_path')->orderBy('order')->orderByDesc('created_at')->take(8)->get();
        $prestasis = Galeri::aktif()->prestasi()->orderByDesc('tanggal')->orderBy('order')->take(3)->get();

        // Diekspos lewat admin/ekstrakurikulers, jadi landing page menampilkan
        // kegiatan yang benar-benar ada di sistem. Versi lama menulis sembilan
        // kartu hardcoded yang hampir tidak ada di database, sementara Paskibra,
        // PMR, dan Rohani Islam yang nyata tidak pernah disebut.
        $ekstrakurikulers = Ekstrakurikuler::aktif()->orderBy('nama')->get();

        $status = StatusPendaftaran::tentukan($tahunAjaranAktif);

        return view('spmb.home', [
            'tahunAjaranAktif' => $tahunAjaranAktif,
            'biayas' => $biayas,
            'pengumumans' => $pengumumans,
            'gelombangs' => $gelombangs,
            'brosurs' => $brosurs,
            'ekstrakurikulers' => $ekstrakurikulers,
            'galeriFotos' => $galeriFotos,
            'prestasis' => $prestasis,
            'ringkasanKuota' => $this->ringkasanKuota($tahunAjaranAktif),
            // Badge hero dan form memakai satu keputusan yang sama.
            'statusPendaftaran' => $status,
            'pendaftaranDibuka' => $status->dibuka(),
        ]);
    }

    /**
     * Agregat kuota untuk kartu "Kuota Terbatas" di landing page.
     *
     * Sebelumnya kartu tersebut menampilkan angka hardcoded (180 siswa, 6 kelas,
     * 45% terisi) yang bertentangan dengan data asli. Sekarang:
     *  - `kapasitas` = jumlah kursi yang bisa didaftarkan,
     *  - `persen` memakai `terisi_pendaftaran` — satu-satunya angka yang
     *    benar-benar bergerak di sistem.
     *
     * Jalur tanpa batas (`kuota_pendaftaran` NULL) diabaikan agar tidak
     * ikut dihitung sebagai kapasitas.
     */
    private function ringkasanKuota(?TahunAjaran $tahun): array
    {
        if (! $tahun) {
            return ['kapasitas' => 0, 'terisi' => 0, 'persen' => 0, 'jumlah_kelas' => 0];
        }

        $kuota = KuotaPendaftaran::where('tahun_ajaran_id', $tahun->id)
            ->whereNotNull('kuota_pendaftaran')
            ->get(['kuota_pendaftaran', 'terisi_pendaftaran']);

        $kapasitas = (int) $kuota->sum('kuota_pendaftaran');
        $terisi = (int) $kuota->sum('terisi_pendaftaran');

        // Tabel `kelas` hanya katalog nama kelas (7A, 7B, ...) tanpa tahun
        // ajaran. Kelas yang benar-benar berjalan pada tahun ini dihitung dari
        // rombel, karena `rombels` punya unique (kelas_id, tahun_ajaran_id).
        //
        // Angka ini sengaja TIDAK dibagi dengan kapasitas menjadi "kuota per
        // kelas": kapasitas berlaku untuk angkatan yang baru masuk, sedangkan
        // kelas yang berjalan mencakup kelas atas, jadi pembaginya tidak
        // sebanding.
        $jumlahKelas = Rombel::where('tahun_ajaran_id', $tahun->id)->distinct()->count('kelas_id');

        return [
            'kapasitas' => $kapasitas,
            'terisi' => $terisi,
            'persen' => $kapasitas > 0 ? (int) round($terisi / $kapasitas * 100) : 0,
            'jumlah_kelas' => $jumlahKelas,
        ];
    }

    public function create(): View
    {
        $tahunAjaranAktif = TahunAjaran::aktif()->first();
        $jalurPendaftarans = JalurPendaftaran::where('aktif', true)->orderBy('nama_jalur')->get();

        $kuotaMap = collect();
        if ($tahunAjaranAktif) {
            $kuotaMap = KuotaPendaftaran::where('tahun_ajaran_id', $tahunAjaranAktif->id)->get()->keyBy('jalur_pendaftaran_id');
        }

        $status = StatusPendaftaran::tentukan($tahunAjaranAktif);

        return view('spmb.pendaftaran', [
            'tahunAjaranAktif' => $tahunAjaranAktif,
            'jalurPendaftarans' => $jalurPendaftarans,
            'kuotaMap' => $kuotaMap,
            // Dropdown hanya boleh menawarkan gelombang yang benar-benar bisa
            // dipilih — sudah lewat atau sudah penuh tidak bisa dipilih walau
            // is_aktif masih true.
            'gelombangs' => $status->tersedia,
            // Timeline perlu semua gelombang yang belum ditutup, termasuk yang akan
            // datang: calon perlu tahu batch berikutnya kapan dibuka. Gelombang
            // yang sudah lewat disembunyikan supaya halaman publik tidak
            // menampilkan tanggal basi. Gelombang yang penuh tetap tampil di
            // sini dengan badge "Kuota Penuh" — informatif, selama tidak
            // masuk dropdown.
            'semuaGelombangs' => Gelombang::semuaAktif($tahunAjaranAktif)
                ->reject(fn (Gelombang $g) => $g->pendaftaranTelahLewat())
                ->values(),
            'statusPendaftaran' => $status,
            'pendaftaranDibuka' => $status->dibuka(),
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

                // Gelombang dikunci baris di sini supaya pengecekan kuota
                // gelombang tetap berlaku walau ada dua pendaftar bersamaan
                // pada detik yang sama.
                $gelombang = isset($validated['gelombang_id'])
                    ? Gelombang::kunci((int) $validated['gelombang_id'])
                    : null;

                if ($gelombang && ! $gelombang->bisaMasuk()) {
                    throw new GelombangTidakTersedia($gelombang->kuotaPenuh()
                        ? 'Kuota gelombang '.$gelombang->nama_gelombang.' sudah penuh.'
                        : 'Gelombang '.$gelombang->nama_gelombang.' tidak sedang dibuka.');
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
                    'gelombang_id' => $gelombang?->id,
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
                foreach (BerkasCalonSiswa::UPLOADABLE as $field) {
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
                $gelombang?->increment('terisi');

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
        } catch (GelombangTidakTersedia $e) {
            // Pesan gelombang harus menempel di field gelombang, bukan di
            // jalur pendaftaran yang kebetulan memakai blok catch yang sama.
            return back()->withErrors(['gelombang_id' => $e->getMessage()])->withInput();
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
