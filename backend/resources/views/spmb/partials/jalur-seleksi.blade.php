{{--
    Jalur pendaftaran.

    Section ini diletakkan di panel gelap, bukan di atas kertas. Alasannya isi:
    section lain di halaman ini terang, dan jalur pendaftaran adalah bagian yang
    paling butuh dipisah secara visual karena orang tua berhenti membacanya di
    sini untuk mengambil keputusan. Panel gelap memberi jeda, dan panel gelap
    juga membuat isinya terbaca sebagai satu blok utuh.

    Jalur ditampilkan sebagai kartu yang terbuka semua, bukan sebagai tab.

    Tab terlihat lebih ringkas, dan versi lama section ini memang memakai tab
    (Reguler dan Prestasi/Beasiswa). Tapi pekerjaan yang sedang dilakukan orang
    tua di sini bukan memilih satu jalur, melainkan membandingkan dua jalur:
    kuota jalur mana yang lebih longgar, dokumen mana yang lebih banyak, apakah
    ada jalur yang tidak perlu tes. Tab menutup dua dari tiga perbandingan itu
    di balik klik, dan angka yang tertutup tidak bisa dibandingkan tanpa dibuka
    satu per satu. Dengan semua kartu terbuka, kuota Reguler dan kuota Prestasi
    terbandingkan dalam satu pandangan.

    Menghemat tinggi halaman tidak sebanding dengan harga yang dibayar di sini,
    jadi kartu terbuka. Panel gelap di belakang keduanya tetap membuat halaman
    terasa punya jeda.

    Rincian syarat per jalur masih hardcode karena tabel
    `jalur_pendaftarans` tidak punya kolom untuk itu, dan dicocokkan lewat
    `nama_jalur`. Kalau sekolah mengganti nama jalurnya lewat panel
    administrasi, kartunya tetap tampil, hanya daftar rinciannya yang kosong.
--}}

@php
    $jalurList = $jalurPendaftarans ?? collect();

    // Rincian syarat per jalur, dikembalikan dari versi lama. Saat itu section
    // ini memakai tab "Reguler" dan "Prestasi/Beasiswa" yang ditulis mati,
    // lengkap dengan syarat rata-rata rapor, batas usia, daftar berkas, dan
    // persentase beasiswa.
    //
    // Semuanya masih hardcode karena tabel jalur_pendaftarans tidak punya
    // kolom untuk itu. Yang berubah cuma cara menampilkannya: jalur tetap
    // diambil dari database supaya sekolah bisa memakai nama sendiri, lalu
    // rinciannya dicocokkan lewat nama_jalur.
    $rincianJalur = [
        'Jalur Reguler' => [
            'persyaratan' => [
                'Siswa kelas 6 SD/MI atau sederajat',
                'Usia maksimal 15 tahun pada awal tahun pelajaran',
                'Mengikuti tes seleksi akademik dan wawancara',
            ],
            'dokumen' => [
                'Fotocopy Akta Kelahiran (2 lembar)',
                'Fotocopy Kartu Keluarga (2 lembar)',
                'Rapor semester 1-5 SD/MI (upload PDF)',
                'Pas foto berwarna 3x4 (3 lembar)',
                'Surat Keterangan Sehat dari dokter',
            ],
            'catatan' => 'Semua dokumen harus asli dan masih berlaku. Pastikan data yang diisi sesuai dengan dokumen resmi.',
        ],
        'Jalur Prestasi' => [
            'persyaratan' => [
                'Rata-rata rapor semester 1-5 minimal <strong>85</strong> (prestasi akademik)',
                'Memiliki prestasi juara 1-3 tingkat Kabupaten/Kota atau lebih tinggi',
                'Prestasi dicapai maksimal 2 tahun terakhir',
                'Bebas tes tulis, hanya wawancara dan verifikasi dokumen',
            ],
            'dokumen' => [
                'Semua dokumen persyaratan jalur reguler',
                'Fotocopy piagam/sertifikat prestasi (dilegalisir)',
                'Surat rekomendasi dari kepala sekolah asal',
                'Portofolio prestasi (jika ada)',
            ],
            'kategori' => [
                'Akademik' => ['Olimpiade Sains/Matematika', 'Olimpiade Bahasa', 'Lomba Karya Ilmiah'],
                'Non-Akademik' => ['Olahraga (minimal Kabupaten)', 'Seni & Budaya', 'Teknologi & Robotik'],
            ],
            'catatan' => 'Siswa yang lolos jalur prestasi berpeluang mendapatkan <strong>beasiswa 25%-100%</strong> biaya pendidikan sesuai tingkat prestasi yang dicapai.',
        ],
    ];

    $centangJalur = 'M5 13l4 4L19 7';
@endphp

<section id="jalur" class="on-dark bg-primary-900 py-24 sm:py-32">
    <div class="mx-auto max-w-6xl px-5 sm:px-6">

        <div class="max-w-2xl">
            <p class="motif-rule mb-4 w-32 justify-self-start text-sm font-semibold uppercase tracking-[0.14em] text-secondary-300">
                @include('spmb.partials.motif-star', ['ukuran' => 'h-4 w-4', 'kelas' => 'shrink-0'])
                <span class="shrink-0">Pilih jalur</span>
            </p>
            <h2 class="reveal font-display text-3xl font-extrabold leading-tight text-white sm:text-4xl">
                Jalur pendaftaran
            </h2>
            <p class="reveal mt-5 text-base leading-relaxed text-primary-200">
                Daftar di bawah diambil dari data sekolah. Kuotanya ikut terisi seiring
                pendaftaran berlangsung, jadi angkanya bisa berubah kapan saja.
            </p>
        </div>

        @forelse ($jalurList as $jalur)
            @php
                $kuota = ($kuotaMap ?? collect())->get($jalur->id);
                $kapasitas = $kuota?->kuota;
                $terisi = $kuota?->terisi ?? 0;
                $tanpaBatas = $kapasitas === null;
                $persen = ($kapasitas && $kapasitas > 0) ? (int) round(min($terisi / $kapasitas, 1) * 100) : null;

                // Rincian dicocokkan lewat nama jalur. Kalau sekolah mengganti
                // nama jalurnya lewat panel administrasi, tabnya tetap tampil,
                // hanya tanpa daftar rincian yang tidak cocok.
                $rincian = $rincianJalur[$jalur->nama_jalur] ?? null;
            @endphp

            {{-- Kartu jalur. Isinya terang di atas panel gelap: kontrasnya
                 jauh di atas batas, dan bentuk kartu jadi batas yang jelas
                 antara isi satu jalur dan isi jalur berikutnya tanpa perlu
                 garis pemisah. --}}
            <article class="reveal mt-8 rounded-lg bg-kertas-50 p-6 text-ink-800 sm:p-8">
                <div class="grid gap-8 md:grid-cols-12 md:gap-10">
                    <div class="md:col-span-7">
                        <h3 class="font-display text-xl font-extrabold text-ink-900">{{ $jalur->nama_jalur }}</h3>

                        @if ($jalur->deskripsi)
                            <p class="mt-3 text-base leading-relaxed text-ink-600">{!! nl2br(e($jalur->deskripsi)) !!}</p>
                        @else
                            <p class="mt-3 text-base leading-relaxed text-ink-500">
                                Sekolah belum menulis keterangan jalur ini.
                            </p>
                        @endif

                        @if ($jalur->wajib_sertifikat)
                            <p class="mt-4 inline-flex items-start gap-2 rounded-md bg-secondary-50 px-3 py-2 text-sm text-secondary-700 ring-1 ring-secondary-200">
                                <svg class="mt-0.5 h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2"
                                     viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M12 9v4m0 4h.01M10.3 3.9L1.8 18a2 2 0 001.7 3h17a2 2 0 001.7-3L13.7 3.9a2 2 0 00-3.4 0z"/>
                                </svg>
                                <span>Jalur ini mewajibkan sertifikat prestasi. Kolomnya ada di langkah 4 saat mengunggah berkas.</span>
                            </p>
                        @endif
                    </div>

                    {{-- Kuota jalur ini. Angka di sini juga ikut menghitung
                         naik lewat `data-hitung`, karena inilah angka yang
                         paling sering ditanyakan orang tua sebelum memilih. --}}
                    <div class="md:col-span-5">
                        <div class="rounded-md border border-kertas-300 bg-kertas-100 p-5">
                            <p class="text-sm font-semibold text-ink-600">Kuota jalur ini</p>

                            @if ($tanpaBatas)
                                <p class="mt-2 font-display text-lg font-extrabold text-ink-900">Tidak dibatasi</p>
                                <p class="mt-1 text-sm text-ink-600">
                                    Sekolah tidak menetapkan batas jumlah peserta untuk jalur ini.
                                </p>
                            @elseif ($kapasitas)
                                {{-- `tabular` di elemen pembungkus, bukan di tiap angka: lihat catatan
                                         yang sama di kartu Kuota Pendaftaran. --}}
                                <p class="mt-2 font-display text-lg font-extrabold text-ink-900 tabular">
                                    {{ $terisi }} dari {{ $kapasitas }} kursi terisi
                                </p>
                                <div class="mt-3 h-2 w-full overflow-hidden rounded-full bg-kertas-300">
                                    <div class="h-2 rounded-full bg-primary-600" style="width: {{ max($persen ?? 0, $terisi > 0 ? 2 : 0) }}%"></div>
                                </div>
                                @if ($kuota?->keterangan)
                                    <p class="mt-3 text-sm text-ink-600">{{ $kuota->keterangan }}</p>
                                @endif
                            @else
                                <p class="mt-2 font-display text-lg font-extrabold text-ink-900">Belum ditetapkan</p>
                                <p class="mt-1 text-sm text-ink-600">
                                    Sekolah belum mengatur kuota jalur ini untuk tahun ajaran berjalan.
                                </p>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Rincian syarat dan dokumen. Semuanya tetap terbuka, jadi
                     jalur bisa dibaca lengkap tanpa satu klik pun. --}}
                @if ($rincian)
                    <div class="mt-8 space-y-8 border-t border-kertas-200 pt-8">

                        <div class="grid gap-8 md:grid-cols-2">
                            @foreach (['persyaratan' => 'Persyaratan', 'dokumen' => 'Dokumen yang diperlukan'] as $kunci => $judul)
                                @if (! empty($rincian[$kunci]))
                                    <div>
                                        <h4 class="flex items-center gap-3 font-display text-base font-extrabold text-ink-900">
                                            <span aria-hidden="true"
                                                  class="grid h-7 w-7 shrink-0 place-items-center rounded-md bg-secondary-600 font-display text-xs font-extrabold text-white">{{ $loop->iteration }}</span>
                                            {{ $judul }}
                                        </h4>
                                        <ul class="mt-4 space-y-2.5">
                                            @foreach ($rincian[$kunci] as $butir)
                                                {{-- `min-w-0` di `<li>` dan `<span>`: tanpa itu butir
                                                     panjang seperti "Rata-rata rapor semester 1-5
                                                     minimal 85" menolak turun dan flex
                                                     memotong sisanya jadi baris yang meluber. --}}
                                                <li class="flex min-w-0 gap-2.5 text-sm leading-relaxed text-ink-700">
                                                    <svg class="mt-0.5 h-4 w-4 shrink-0 text-primary-600" fill="none"
                                                         stroke="currentColor" stroke-width="3" viewBox="0 0 24 24" aria-hidden="true">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $centangJalur }}"/>
                                                    </svg>
                                                    <span class="min-w-0">{!! $butir !!}</span>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif
                            @endforeach
                        </div>

                        @if (! empty($rincian['kategori']))
                            <div>
                                <h4 class="flex items-center gap-3 font-display text-base font-extrabold text-ink-900">
                                    <span aria-hidden="true"
                                          class="grid h-7 w-7 shrink-0 place-items-center rounded-md bg-secondary-600 font-display text-xs font-extrabold text-white">3</span>
                                    Kategori prestasi yang diterima
                                </h4>
                                <div class="mt-4 grid gap-4 md:grid-cols-2">
                                    @foreach ($rincian['kategori'] as $judul => $butir)
                                        <div class="rounded-md border border-kertas-300 bg-kertas-100 p-5">
                                            <h5 class="font-display text-sm font-extrabold text-ink-900">{{ $judul }}</h5>
                                            <ul class="mt-2.5 space-y-1.5">
                                                @foreach ($butir as $item)
                                                    <li class="flex min-w-0 gap-2 text-sm leading-relaxed text-ink-700">
                                                        <span aria-hidden="true" class="mt-1.5 h-1 w-1 shrink-0 rounded-full bg-primary-600"></span>
                                                        <span class="min-w-0">{{ $item }}</span>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        @if (! empty($rincian['catatan']))
                            <p class="flex items-start gap-3 rounded-md border border-primary-200 bg-primary-50 p-5 text-sm leading-relaxed text-ink-700">
                                <svg class="mt-0.5 h-5 w-5 shrink-0 text-primary-700" fill="none" stroke="currentColor"
                                     stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M8.228 9c.549-1.165 2.03-2 3.772-2s3.223.835 3.772 2m-7.544 0a8.25 8.25 0 113.772 0M12 12.75h.008v.008H12v-.008zm.375 3h-.75v4.5h3v-3.75m-3-3h3v2.25H9.375z"/>
                                </svg>
                                <span>{!! $rincian['catatan'] !!}</span>
                            </p>
                        @endif
                    </div>
                @endif
            </article>
        @empty
            {{-- Empty state (R-27): belum ada jalur aktif. Menyebutkan sebabnya
                 dan jalan keluarnya, bukan cuma menampilkan halaman kosong.

                 Panelnya tetap gelap, bukan kartu putih, supaya "tidak ada
                 jalur" terbaca sebagai keadaan halaman, bukan kelihatan seperti
                 yang biasanya berisi isi. --}}
            <div class="reveal mt-8 rounded-lg bg-primary-800 p-8">
                <h3 class="font-display text-lg font-extrabold text-white">Belum ada jalur pendaftaran yang aktif</h3>
                <p class="mt-2 max-w-lg text-base leading-relaxed text-primary-200">
                    Sekolah belum membuka jalur pendaftaran untuk tahun ajaran ini,
                    atau seluruhnya sudah ditutup. Padahal begitu ada yang dibuka,
                    halaman ini langsung menampilkannya.
                </p>
                <a href="{{ route('spmb.pengumuman.index') }}" class="btn btn-quiet-light mt-6">
                    Lihat Pengumuman
                </a>
            </div>
        @endforelse
    </div>
</section>
