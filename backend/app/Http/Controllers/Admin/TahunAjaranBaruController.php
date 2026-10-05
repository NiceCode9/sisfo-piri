<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Rombel\SalinRombelAction;
use App\Actions\Siswa\ProsesKenaikanAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProsesKenaikanWizardRequest;
use App\Models\Kelas;
use App\Models\Pengampu;
use App\Models\Rombel;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

/**
 * Wizard Tahun Ajaran Baru.
 *
 * Menyatukan dua langkah yang sebelumnya harus dijalankan manual dan bisa
 * salah urutan: "Salin Tahun" menyalin rombel + wali + penugasan tapi TIDAK
 * anggota siswa, sedangkan "Kenaikan Kelas" menulis riwayat siswa tapi TIDAK
 * membuat rombel. Kalau kenaikan jalan lebih dulu, siswa menunjuk `kelas_id`
 * yang tidak punya rombel — dan `Rombel::anggotaIds()` untuk kelas itu kosong,
 * sehingga siswa itu lenyap dari rekap absensi, nilai tugas, dan nilai ujian
 * tanpa ada yang memberitahu.
 *
 * Wizard ini menjalankan salin lebih dulu, baru kenaikan, lalu memverifikasi
 * setiap kelas tujuan benar-benar punya rombel sebelum memindahkan siapa pun.
 *
 * Menu "Kenaikan Kelas" lama sudah dilebur ke sini. Alasannya bukan sekadar
 * satu menu: halaman lama itu punya override per siswa tapi TIDAK menyalin
 * rombel dan tidak pernah memanggil `kelasTujuanTanpaRombel()`, sehingga
 * tombol "Atur Kenaikan Detail" yang menuju ke sana justru membuka jalan
 * memindahkan siswa ke kelas tanpa rombel. Sekarang override per siswa ada di
 * halaman ini dan submit ke endpoint yang sama, jadi urutan salah tidak lagi
 * bisa terjadi.
 */
class TahunAjaranBaruController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:tahun-ajaran-baru.view', only: ['index']),
            new Middleware('permission:tahun-ajaran-baru.execute', only: ['proses']),
        ];
    }

    public function index(Request $request, ProsesKenaikanAction $kenaikan): View
    {
        $tahunAjarans = TahunAjaran::orderByDesc('tanggal_mulai')->get();

        if ($tahunAjarans->count() < 2) {
            return view('admin.tahun-ajaran-baru.index', [
                'tahunAjarans' => $tahunAjarans,
                'tahunAktif' => null,
                'tahunAsalId' => null,
                'tahunTujuanId' => null,
                'grup' => collect(),
                'kelasList' => collect(),
                'tingkatAkhir' => 0,
                'petaOtomatis' => [],
                'pemetaan' => [],
                'override' => [],
                'jumlahOverride' => 0,
                'kelasTujuan' => collect(),
                'ringkasanSalin' => ['rombel' => 0, 'penugasan' => 0, 'sudah ada' => 0],
                'peringatan' => ['Butuh minimal dua tahun ajaran. Tambahkan tahun ajaran baru terlebih dahulu.'],
            ]);
        }

        // Default: tahun asal adalah tahun yang sedang aktif, tujuan adalah
        // tahun lain yang paling baru. Memakai tahun aktif — bukan tahun kedua
        // terbaru — supaya siswa yang tampil memang yang sedang absen hari ini.
        $tahunAktif = TahunAjaran::aktif()->first();
        $tahunAsalId = $request->integer('tahun_asal_id') ?: $tahunAktif?->id;
        $tahunTujuanId = $request->integer('tahun_tujuan_id')
            ?: $tahunAjarans->firstWhere('id', '!=', $tahunAsalId)?->id;

        if ($tahunAsalId === $tahunTujuanId) {
            $tahunTujuanId = $tahunAjarans->firstWhere('id', '!=', $tahunAsalId)?->id;
        }

        $siswas = Siswa::with(['kelas', 'user', 'calonSiswa'])
            ->where('is_aktif', true)
            ->where('tahun_ajaran_id', $tahunAsalId)
            ->orderBy('kelas_id')
            ->orderBy('nis')
            ->get();

        $grup = $siswas->groupBy('kelas_id')->map(fn ($anggota) => [
            'kelas' => $anggota->first()->kelas,
            'siswas' => $anggota,
        ]);

        $peta = $kenaikan->petaOtomatis();

        // Pemetaan bisa dioverride lewat query string supaya pratinjau bisa
        // dicoba tanpa commit dulu. Defaultnya pasangan tingkat+1 huruf sama.
        $pemetaan = $request->input('pemetaan', []);
        foreach ($grup->keys() as $kelasId) {
            $pemetaan[$kelasId] ??= $peta[$kelasId] ?? '';
        }

        // Override per siswa ditampilkan di dalam accordion, jadi tidak lewat
        // query string. Yang dihitung di sini hanya jumlahnya, untuk
        // memberitahu operator bahwa ada pengecualian yang wajib ditinjau.
        $override = $request->input('override', []);
        $jumlahOverride = count(array_filter(
            $override,
            fn ($aksi) => $aksi !== null && $aksi !== '' && $aksi !== 'ikuti'
        ));

        $rombelTujuan = Rombel::where('tahun_ajaran_id', $tahunTujuanId)->pluck('kelas_id')->all();

        $kelasTujuan = collect($pemetaan)
            ->filter(fn ($t) => $t !== '' && $t !== 'LULUS')
            ->map(fn ($t) => (int) $t)
            ->unique()
            ->map(fn ($id) => [
                'kelas' => Kelas::find($id)?->nama_kelas ?? '?',
                'adaRombel' => in_array($id, $rombelTujuan, true),
            ])
            ->values();

        return view('admin.tahun-ajaran-baru.index', [
            'tahunAjarans' => $tahunAjarans,
            'tahunAktif' => $tahunAktif,
            'tahunAsalId' => $tahunAsalId,
            'tahunTujuanId' => $tahunTujuanId,
            'grup' => $grup,
            'kelasList' => Kelas::orderBy('tingkat')->orderBy('nama_kelas')->get(),
            'tingkatAkhir' => (int) Kelas::pluck('tingkat')->map(fn ($t) => (int) $t)->max(),
            'petaOtomatis' => $peta,
            'pemetaan' => $pemetaan,
            'override' => $override,
            'jumlahOverride' => $jumlahOverride,
            'kelasTujuan' => $kelasTujuan,
            'pratinjau' => $this->hitungPratinjau($grup, $pemetaan),
            'ringkasanSalin' => $this->ringkasanSalin($tahunAsalId, $tahunTujuanId),
            'peringatan' => [],
        ]);
    }

    /**
     * Pratinjau berdasarkan pemetaan kelas saja — override per siswa sengaja
     * tidak dihitung, karena kolom itu baru mengubah hasil setelah dipilih.
     * UI menandai tabel ini supaya angkanya tidak dibaca sebagai hasil final.
     *
     * @param  \Illuminate\Support\Collection<int, array{kelas: Kelas, siswas: Collection}>  $grup
     * @param  array<int|string, string>  $pemetaan
     * @return array<string, array<string, int>>
     */
    protected function hitungPratinjau($grup, array $pemetaan): array
    {
        $rekap = [];

        foreach ($grup as $kelasId => $data) {
            $nama = $data['kelas']->nama_kelas ?? '?';
            $rekap[$nama] ??= ['naik' => 0, 'tinggal' => 0, 'lulus' => 0, 'dilewati' => 0];

            $tujuan = $pemetaan[$kelasId] ?? '';

            if ($tujuan === 'LULUS') {
                $aksi = 'lulus';
            } elseif ($tujuan !== '') {
                $aksi = 'naik';
            } else {
                $aksi = 'dilewati';
            }

            $rekap[$nama][$aksi] += $data['siswas']->count();
        }

        return $rekap;
    }

    /**
     * @return array{rombel:int, penugasan:int, sudah_ada:int}
     */
    protected function ringkasanSalin(int $tahunAsalId, int $tahunTujuanId): array
    {
        $rombelTujuan = Rombel::where('tahun_ajaran_id', $tahunTujuanId)->pluck('id');

        return [
            'rombel' => Rombel::where('tahun_ajaran_id', $tahunAsalId)->count(),
            'penugasan' => Pengampu::whereIn(
                'rombel_id',
                Rombel::where('tahun_ajaran_id', $tahunAsalId)->pluck('id')
            )->count(),
            'sudah ada' => $rombelTujuan->count(),
        ];
    }

    public function proses(
        ProsesKenaikanWizardRequest $request,
        SalinRombelAction $salin,
        ProsesKenaikanAction $kenaikan
    ): RedirectResponse {
        $validated = $request->validated();

        $tahunAsal = (int) $validated['tahun_asal_id'];
        $tahunTujuan = (int) $validated['tahun_tujuan_id'];
        $pemetaan = array_map(
            fn ($v) => $v === null ? '' : (string) $v,
            $validated['pemetaan']
        );
        $override = $validated['override'] ?? [];

        // Langkah 1 — bentuk rombel tahun tujuan. Idempoten, jadi aman
        // dijalankan berkali-kali saat penyiapan.
        $hasilSalin = $salin->jalankan($tahunAsal, $tahunTujuan);

        // Langkah 2 — pastikan setiap kelas tujuan punya rombel SEBELUM ada
        // siswa yang dipindahkan ke sana.
        //
        // Salin hanya menyalin rombel yang sudah ada di tahun asal, jadi kelas
        // yang BARU (mis. 8A saat sekolah naik dari kelas 7) tidak punya
        // sumber untuk disalin. Kalau dibiarkan, wizard akan selalu mentok di
        // sini dan kenaikan tidak bisa dijalankan sama sekali. Rombel kosong
        // dibuat otomatis; wali dan penugasannya masih perlu diisi lewat menu
        // Rombel, dan itu dilaporkan di pesan hasil.
        $kelasTanpaRombel = $kenaikan->kelasTujuanTanpaRombel($pemetaan, $tahunTujuan);
        $rombelDibuat = [];

        foreach ($kelasTanpaRombel as $kelasId) {
            $ada = Rombel::firstOrCreate(
                ['kelas_id' => $kelasId, 'tahun_ajaran_id' => $tahunTujuan],
                ['wali_guru_id' => null]
            );

            if ($ada->wasRecentlyCreated) {
                $rombelDibuat[] = Kelas::find($kelasId)?->nama_kelas ?? "#{$kelasId}";
            }
        }

        // Jaring pengaman. `ProsesKenaikanWizardRequest` sudah menolak id kelas yang
        // tidak dikenal, dan setiap id yang lolos baru saja dibuatkan rombelnya,
        // jadi daftar ini praktis tidak mungkin berisi apa pun. Kalau tetap
        // ada, jangan dipindahkan — lebih baik tidak naik daripada menunjuk
        // kelas tanpa rombel.
        $sisa = $kenaikan->kelasTujuanTanpaRombel($pemetaan, $tahunTujuan);

        if ($sisa !== []) {
            $nama = collect($sisa)->map(fn ($id) => Kelas::find($id)?->nama_kelas ?? "#{$id}")->implode(', ');

            return redirect()
                ->route('admin.tahun-ajaran-baru.index', [
                    'tahun_asal_id' => $tahunAsal,
                    'tahun_tujuan_id' => $tahunTujuan,
                ])
                ->with('error', "Rombel tahun tujuan untuk kelas {$nama} gagal dibuat. "
                    .'Kenaikan kelas tidak dijalankan agar tidak ada siswa yang menunjuk kelas tanpa rombel.');
        }

        // Langkah 3 — pindahkan siswa.
        $hasilKenaikan = $kenaikan->jalankan($tahunAsal, $tahunTujuan, $pemetaan, $override);

        $bagian = "Salin: {$hasilSalin['rombelDisalin']} rombel + {$hasilSalin['tugasDisalin']} penugasan baru";
        if ($hasilSalin['rombelDilewati'] || $hasilSalin['tugasDilewati']) {
            $bagian .= " ({$hasilSalin['rombelDilewati']} rombel + {$hasilSalin['tugasDilewati']} penugasan sudah ada)";
        }
        if ($rombelDibuat !== []) {
            $bagian .= '. Rombel tujuan dibuat otomatis: '.implode(', ', $rombelDibuat)
                .' — wali dan penugasannya masih kosong, isi lewat menu Rombel';
        }

        $bagian .= sprintf(
            '. Kenaikan: %d naik, %d tinggal, %d lulus, %d dilewati.',
            $hasilKenaikan['naik'],
            $hasilKenaikan['tinggal'],
            $hasilKenaikan['lulus'],
            $hasilKenaikan['dilewati']
        );

        return redirect()
            ->route('admin.tahun-ajaran-baru.index', [
                'tahun_asal_id' => $tahunAsal,
                'tahun_tujuan_id' => $tahunTujuan,
            ])
            ->with('success', $bagian);
    }
}
