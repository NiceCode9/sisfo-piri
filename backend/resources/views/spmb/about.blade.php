@extends('layouts.spmb')

@section('title', 'Tentang Sekolah - '.($namaSekolah ?? 'SPMB'))

@php
    // $profileSekolah dishare otomatis via View composer (spmb.*). Tidak ada
    // objek cadangan berisi data karangan: nama sekolah, alamat, telepon,
    // email, visi/misi, dan sambutan diambil apa adanya dari database. Nilai
    // yang masih placeholder "GANTI: ..." disaring oleh accessor `*Bersih`,
    // dan bagian yang kosong tidak ditampilkan sama sekali.

    $tahunBerjalan = $lamaBerdiri ?? null;

    // Daftar nilai dan fasilitas di bawah masih contoh desain, belum ada
    // tabel sumbernya. Selama begitu, isinya tetap ditulis di view.
    $misiList = $profileSekolah?->misi_bersih ?? [];

    $nilaiList = [
        ['icon' => 'heart', 'title' => 'Integritas', 'desc' => 'Jujur dan bertanggung jawab dalam setiap tindakan'],
        ['icon' => 'sparkles', 'title' => 'Kreativitas', 'desc' => 'Berani bereksplorasi dan berinovasi'],
        ['icon' => 'users', 'title' => 'Kolaborasi', 'desc' => 'Bekerja sama membangun lingkungan yang positif'],
        ['icon' => 'academic-cap', 'title' => 'Prestasi', 'desc' => 'Berusaha mencapai hasil terbaik secara konsisten'],
        ['icon' => 'globe', 'title' => 'Wawasan Global', 'desc' => 'Terbuka terhadap perkembangan dunia'],
        ['icon' => 'shield-check', 'title' => 'Karakter', 'desc' => 'Berakhlak mulia dan menghargai perbedaan'],
    ];

    // Fasilitas dirender sebagai kartu ikon, bukan foto. Versi lama memakai
    // `via.placeholder.com`, layanan pihak ketiga yang sudah tidak bisa
    // diandalkan: appearnya kotak abu-abu bertuliskan teks, bukan gambar
    // fasilitas sekolah (R-36).
    $fasilitasList = [
        ['icon' => 'beaker', 'title' => 'Laboratorium IPA', 'desc' => 'Ruang praktikum sains lengkap dengan alat modern'],
        ['icon' => 'book', 'title' => 'Perpustakaan', 'desc' => 'Koleksi ribuan buku dan area baca yang nyaman'],
        ['icon' => 'bolt', 'title' => 'Lapangan Olahraga', 'desc' => 'Lapangan multifungsi untuk berbagai cabang olahraga'],
        ['icon' => 'desktop', 'title' => 'Ruang Komputer', 'desc' => 'Lab komputer dengan akses internet untuk pembelajaran digital'],
        ['icon' => 'sparkles', 'title' => 'Ruang Kesenian', 'desc' => 'Fasilitas untuk mengembangkan bakat seni dan musik siswa'],
        ['icon' => 'mosque', 'title' => 'Masjid Sekolah', 'desc' => 'Tempat ibadah yang nyaman untuk kegiatan keagamaan'],
    ];

    $kepalaSekolah = (object) [
        'nama' => $profileSekolah?->nama_kepala_bersih,
        'jabatan' => 'Kepala Sekolah',
        'foto' => ! empty($profileSekolah?->foto_kepala_path)
            ? Storage::disk('public')->url($profileSekolah->foto_kepala_path)
            : null,
        'sambutan' => $profileSekolah?->sambutan_bersih,
    ];

    $fotoGedung = ! empty($profileSekolah?->foto_gedung_path)
        ? Storage::disk('public')->url($profileSekolah->foto_gedung_path)
        : '/images/sekolah/gedung-960w.jpg';

    $visiSekolah = $profileSekolah?->visi_bersih;

    /*
     * Identitas sekolah, dibaca apa adanya dari `profil_sekolahs`.
     *
     * Dua kolom yang paling sering ditanyakan orang tua dan sama sekali
     * belum tampil di halaman mana pun: `npsn` dan `website`. Keduanya ada
     * di `$fillable` sejak awal, tapi tidak pernah dirender di view mana pun,
     * jadi admin yang sudah mengisinya tetap tidak melihatnya di situs.
     *
     * Baris yang nilainya kosong atau masih placeholder TIDAK dirender. Daftar
     * identitas yang setengah kosong lebih buruk daripada tidak ada: yang
     * hilang itu menandakan datanya sengaja belum diisi, bukan lupa. Kalau
     * ternyata tidak ada satu baris pun yang punya nilai, blok ini tidak
     * dirender sama sekali (lihat `@if ($identitas)`).
     */
    $identitas = array_values(array_filter([
        $profileSekolah?->npsn_bersih ? [
            'label' => 'NPSN',
            'nilai' => $profileSekolah->npsn_bersih,
            'tabular' => true,
        ] : null,
        $profileSekolah?->akreditasi_bersih ? [
            'label' => 'Akreditasi',
            'nilai' => $profileSekolah->akreditasi_bersih,
        ] : null,
        $profileSekolah?->tahun_berdiri ? [
            'label' => 'Tahun Berdiri',
            'nilai' => $profileSekolah->tahun_berdiri,
            'tabular' => true,
        ] : null,
        $profileSekolah?->alamat_bersih ? [
            'label' => 'Alamat',
            // Alamat boleh panjang dan bisa berisi baris baru, jadi ia
            // di-return sebagai HTML yang sudah di-escape per baris.
            'nilai' => nl2br(e($profileSekolah->alamat_bersih)),
            'html' => true,
        ] : null,
        $profileSekolah?->telp_bersih ? [
            'label' => 'Telepon',
            'nilai' => $profileSekolah->telp_bersih,
            'tel' => $profileSekolah->telp_tel,
            'tabular' => true,
        ] : null,
        $profileSekolah?->whatsapp ? [
            'label' => 'WhatsApp',
            'nilai' => 'wa.me/'.$profileSekolah->whatsapp,
            'url' => 'https://wa.me/'.$profileSekolah->whatsapp,
            'tabular' => true,
        ] : null,
        $profileSekolah?->email_bersih ? [
            'label' => 'Email',
            'nilai' => $profileSekolah->email_bersih,
            'url' => 'mailto:'.$profileSekolah->email_bersih,
        ] : null,
        $profileSekolah?->website_bersih ? [
            'label' => 'Website',
            'nilai' => $profileSekolah->website_bersih,
            'url' => Str::startsWith($profileSekolah->website_bersih, ['http://', 'https://'])
                ? $profileSekolah->website_bersih
                // Admin sering mengetik `sekolah.sch.id` tanpa protokol, dan
                // `href="sekolah.sch.id"` akan dibaca browser sebagai path
                // relatif, bukan alamat web.
                : 'https://'.$profileSekolah->website_bersih,
            'tabular' => true,
        ] : null,
    ]));

    // Ikon inline, satu jalur `<path>` per ikon. Tidak ada paket ikon di
    // proyek ini, dan memuat 12 berkas SVG hanya untuk gambar 20px lebih
    // lambat daripada menuliskannya langsung.
    $icons = [
        'heart' => ['M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z'],
        'sparkles' => ['M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-6.714 2.143L12 21l-2.286-6.857L3 12l6.714-2.143L12 3z'],
        'users' => ['M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zM7 7a3 3 0 11-6 0 3 3 0 016 0z'],
        'academic-cap' => ['M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14zm-4 6v-7.5l4-2.222'],
        'globe' => ['M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
        'shield-check' => ['M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z'],
        'check' => ['M5 13l4 4L19 7'],
        'flag' => ['M3 3v18M3 4h13l-2 4 2 4H3'],
        'target' => ['M12 8a4 4 0 100 8 4 4 0 000-8zm0-6a10 10 0 100 20 10 10 0 000-20zm0 3a7 7 0 100 14 7 7 0 000-14z'],
        'beaker' => ['M9 3v6.692L4.5 18a2 2 0 001.7 3h11.6a2 2 0 001.7-3L15 9.692V3M8 3h8M7 14h10'],
        'book' => ['M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253'],
        'bolt' => ['M13 10V3L4 14h7v7l9-11h-7z'],
        'desktop' => ['M3 5a2 2 0 012-2h14a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V5zM8 21h8m-4-4v4'],
        'mosque' => ['M12 2c1.5 2 2.5 3.5 2.5 5a2.5 2.5 0 01-5 0C9.5 5.5 10.5 4 12 2zM4 22v-7a8 8 0 0116 0v7M4 22h16M3 22h18M12 12v3m0 0l-2 2m2-2l2 2'],
    ];

    $renderIcon = fn ($key) => $icons[$key] ?? $icons['check'];
@endphp

@section('content')
    <main id="konten-utama">
        @include('spmb.partials.navbar', ['transparent' => true])

        {{-- ============ HERO ============ --}}
        {{-- ============ HERO ============ --}}
        {{-- Blob dekoratif dan `animate-float` yang dulu memenuhi halaman ini
             dihapus: keduanya bergerak tanpa ujung dan tidak menambah informasi
             apa pun (R-19). Yang mengisi ruang kosong sekarang adalah deretan
             angka yang bisa diperiksa orang tua.

             `on-dark` wajib ada di sini. Tanpa itu, cincin fokus memakai
             coral gelap yang hanya 2.78:1 di atas teal-900, dan hasilnya tidak
             terlihat sama sekali. --}}
        <section class="on-dark bg-primary-900 pt-28 pb-20 text-white sm:pt-36 sm:pb-28">
            <div class="mx-auto max-w-6xl px-5 sm:px-6">

                <p class="inline-flex items-center gap-2 rounded-md bg-white/10 px-3 py-1.5 text-sm font-semibold text-secondary-300">
                    Tentang Kami
                </p>

                {{-- `h1` sengaja tanpa `.reveal`. Di atas lipatan, judul utama
                     tidak boleh bergantung pada satu frame animasi untuk muncul:
                     kalau `pasangReveal()` tidak jalan, halaman ini terbuka tanpa
                     judul sama sekali. --}}
                <h1 class="mt-6 max-w-3xl text-balance font-display text-4xl font-extrabold leading-[1.1] tracking-tight sm:text-5xl">
                    Mengenal lebih dekat
                    <span class="text-secondary-300">{{ $namaSekolah }}</span>
                </h1>

                {{-- Kalimat pembuka hanya ditulis kalau tahun berdiri benar-benar ada.
                     `?? 'berdiri'` di versi lama menghasilkan "Sejak berdiri,
                     kami berkomitmen" yang tidak gramatikal, sekaligus memberi
                     kesan ada sejarahnya padahal tidak ada datanya. --}}
                @if ($profileSekolah?->tahun_berdiri)
                    <p class="reveal mt-6 max-w-2xl text-base leading-relaxed text-primary-200 sm:text-lg">
                        Sejak {{ $profileSekolah->tahun_berdiri }}, kami berkomitmen
                        membentuk generasi cerdas, berkarakter, dan berprestasi untuk masa depan
                        Indonesia yang gemilang.
                    </p>
                @endif

                {{-- Baris hilang kalau angkanya nol, bukan diisi tanda pisah.
                     `filled(0)` bernilai true di Laravel, jadi penyaringan
                     memakai perbandingan langsung.

                     Label "Tahun Berjalan" dulu dipakai untuk `$tahunBerjalan`,
                     yang isinya `now()->year - tahun_berdiri`. Itu JUMLAH
                     tahun, bukan tahun. Angka 40 dengan label "Tahun Berjalan"
                     dibaca orang tua sebagai "tahun 40". Sekarang labelnya
                     menyebut apa yang benar-benar diukur.

                     `border-primary-500`, bukan `primary-800`: di atas
                     primary-900, primary-800 cuma 1.22:1 dan primary-600
                     2.25:1, keduanya di bawah batas 3:1 untuk garis yang
                     harus terlihat. primary-500 mencapai 3.63:1. --}}
                @php
                    $statistik = array_values(array_filter([
                        $jumlahSiswaAktif > 0 ? ['nilai' => $jumlahSiswaAktif, 'label' => 'Siswa Aktif', 'tabular' => true] : null,
                        $jumlahGuruAktif > 0 ? ['nilai' => $jumlahGuruAktif, 'label' => 'Tenaga Pendidik', 'tabular' => true] : null,
                        $tahunBerjalan ? ['nilai' => $tahunBerjalan, 'label' => 'Tahun Mengajar', 'tabular' => true] : null,
                        $profileSekolah?->akreditasi_bersih ? ['nilai' => $profileSekolah->akreditasi_bersih, 'label' => 'Akreditasi'] : null,
                    ]));
                @endphp

                @if ($statistik)
                    <dl class="mt-12 grid grid-cols-2 gap-x-6 gap-y-8 border-t border-primary-500 pt-8 sm:grid-cols-4">
                        @foreach ($statistik as $stat)
                            <div>
                                <dd class="font-display text-3xl font-extrabold text-secondary-300 tabular sm:text-4xl">{{ $stat['nilai'] }}</dd>
                                <dt class="mt-1 text-sm text-primary-200">{{ $stat['label'] }}</dt>
                            </div>
                        @endforeach
                    </dl>
                @endif
            </div>
        </section>

        {{-- ============ SEJARAH ============ --}}
        <section class="bg-kertas-100 py-20 sm:py-24">
            <div class="mx-auto max-w-6xl px-5 sm:px-6">
                <div class="grid items-center gap-12 lg:grid-cols-2 lg:gap-16">

                    <div class="relative">
                        <div class="bingkai inline-block max-w-lg border border-kertas-300">
                            <img src="{{ $fotoGedung }}" alt="Gedung {{ $namaSekolah }}"
                                 width="1600" height="900"
                                 class="block w-full rounded-lg object-cover"
                                 loading="lazy" decoding="async">
                        </div>

                        @if ($profileSekolah?->tahun_berdiri)
                            <div class="mt-6 inline-flex items-baseline gap-3 rounded-md border border-kertas-300 bg-kertas-200 px-5 py-3">
                                <span class="font-display text-2xl font-extrabold text-primary-700">{{ $profileSekolah->tahun_berdiri }}</span>
                                <span class="text-sm text-ink-600">Tahun Berdiri</span>
                            </div>
                        @endif
                    </div>

                    <div>
                        <h2 class="reveal font-display text-3xl font-extrabold leading-tight text-ink-900 sm:text-4xl">
                            Perjalanan membentuk generasi unggul
                        </h2>

                        <p class="reveal mt-6 text-base leading-relaxed text-ink-600">
                            {{ $namaSekolah }} didirikan pada tahun {{ $profileSekolah?->tahun_berdiri ?? '-' }}
                            dengan visi menghadirkan pendidikan yang berkualitas dan terjangkau bagi masyarakat sekitar.
                        </p>

                        @if ($tahunBerjalan)
                            <p class="reveal mt-4 text-base leading-relaxed text-ink-600">
                                Selama {{ $tahunBerjalan }} tahun perjalanan, kami terus berkembang, dari
                                fasilitas, kurikulum, hingga kualitas tenaga pendidik, untuk memastikan setiap
                                siswa mendapatkan pengalaman belajar terbaik dan siap melangkah ke jenjang
                                pendidikan berikutnya.
                            </p>
                        @endif

                        {{-- Klaim "Standar Nasional" dihapus. Kolom `akreditasi` cuma menyimpan
                             A/B/C; tidak ada kolom yang menyatakan standar mana,
                             dan tidak ada sumber yang bisa menopang klaim itu.
                             Yang tampil hanya hurufnya (R-36). --}}
                        @if ($profileSekolah?->akreditasi_bersih)
                            <p class="reveal mt-6 inline-flex items-center gap-2 text-sm font-semibold text-ink-700">
                                <svg class="h-5 w-5 shrink-0 text-primary-600" fill="none" stroke="currentColor"
                                     stroke-width="3" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $renderIcon('check')[0] }}"/>
                                </svg>
                                Terakreditasi {{ $profileSekolah->akreditasi_bersih }}
                            </p>
                        @endif
                    </div>
                </div>
            </div>
        </section>

        {{-- ============ IDENTITAS SEKOLAH ============ --}}
        {{-- Ditaruh tepat setelah Sejarah, bukan di bawah. Orang tua yang
             datang ke halaman "Tentang" hampir selalu mencari satu dari dua
             hal: apakah ini sekolah yang benar, dan bagaimana cara
             endometukannya. Jawaban kedua seharusnya tidak menuntut menggulir
             ke bawah footer.

             Baris yang kosong tidak dirender, jadi tabel ini hanya berisi
             data yang benar-benar ada. Kalau tidak ada satu pun, blok ini
             hilang total dan tidak meninggalkan tempat kosong. --}}
        @if ($identitas)
            <section id="identitas" class="bg-kertas-200 py-20 sm:py-24">
                <div class="mx-auto max-w-6xl px-5 sm:px-6">

                    <div class="max-w-2xl">
                        <p class="motif-rule mb-4 w-32 justify-self-start text-sm font-semibold uppercase tracking-[0.14em] text-primary-700">
                            @include('spmb.partials.motif-star', ['ukuran' => 'h-4 w-4', 'kelas' => 'shrink-0'])
                            <span class="shrink-0">Data resmi</span>
                        </p>
                        <h2 class="reveal font-display text-3xl font-extrabold leading-tight text-ink-900 sm:text-4xl">
                            Identitas sekolah
                        </h2>
                        <p class="reveal mt-5 text-base leading-relaxed text-ink-600">
                            Data berikut diisi dari panel administrasi sekolah. Baris
                            yang belum diisi sengaja tidak ditampilkan, bukan
                            diganti dengan contoh.
                        </p>
                    </div>

                    {{-- `sm:grid-cols-2` dengan label di kiri dan nilai di kanan,
                         bukan grid kartu. Kartu membuat delapan baris data
                         admin terbaca sebagai delapan hal yang sama pentingnya,
                         padahal yang paling dicari orang tua cuma satu: alamat
                         dan telepon. --}}
                    <dl class="mt-10 overflow-hidden rounded-lg border border-kertas-300 bg-kertas-100">
                        @foreach ($identitas as $baris)
                            <div class="grid gap-1 border-b border-kertas-200 px-5 py-4 last:border-b-0 sm:grid-cols-3 sm:items-baseline sm:gap-6 sm:px-6">
                                <dt class="text-sm font-semibold text-ink-600">{{ $baris['label'] }}</dt>
                                <dd class="min-w-0 text-base text-ink-900 sm:col-span-2{{ ($baris['tabular'] ?? false) ? ' tabular' : '' }}">
                                    {{-- `tel` diperiksa sebelum `url`: nomor telepon di bawahnya
                                         tetap teks biasa supaya tidak terpotong
                                         underline panjang. --}}
                                    @if ($baris['tel'] ?? false)
                                        <a href="tel:{{ $baris['tel'] }}"
                                           class="inline-flex min-h-11 items-center text-primary-700 underline decoration-primary-200 underline-offset-4 transition-colors hover:decoration-primary-600">
                                            {{ $baris['nilai'] }}
                                        </a>
                                    @elseif ($baris['url'] ?? false)
                                        <a href="{{ $baris['url'] }}"
                                           class="inline-flex min-h-11 items-center break-all text-primary-700 underline decoration-primary-200 underline-offset-4 transition-colors hover:decoration-primary-600">
                                            {{ $baris['nilai'] }}
                                        </a>
                                    @elseif ($baris['html'] ?? false)
                                        {{-- Alamat sudah di-escape per baris di dalam
                                             `$identitas`, dengan `e()` lalu `nl2br()`.
                                             Di-cast dua kali di sini akan
                                             menghasilkan `&amp;lt;` yang tampil
                                             sebagai teks mentah. --}}
                                        {!! $baris['nilai'] !!}
                                    @else
                                        <span class="break-words">{{ $baris['nilai'] }}</span>
                                    @endif
                                </dd>
                            </div>
                        @endforeach
                    </dl>

                </div>
            </section>
        @endif

        {{-- ============ PETA LOKASI ============ --}}
        {{-- Section sendiri, bukan digabung ke tabel Identitas.

             Versi pertama menaruh peta di dalam `@if ($identitas)`, jadi peta
             ikut hilang begitu tabel itu kosong. Padahal keduanya dua hal yang
             berbeda: peta selalu punya sumber (peta cadangan kalau admin belum
             mengisi URL), sedangkan baris identitas tidak punya satu pun nilai
             sampai admin mengisinya. Sekolah yang sudah memasang peta tapi belum
             mengisi alamat atau telepon akan kehilangan petanya sendiri.

             Lebarnya penuh, bukan seperempat kolom: peta yang sempit tidak bisa
             dibaca jalan. --}}
        <section id="lokasi" class="bg-kertas-100 py-20 sm:py-24">
            {{-- `kertas-100` di sini, bukan `kertas-200`. Section Identitas di
                 atasnya sudah `kertas-200`, dan dua latar kertas yang sama
                 bersebelahan terbaca seperti satu blok besar, jadi jeda yang
                 seharusnya ada justru hilang. --}}
            <div class="mx-auto max-w-6xl px-5 sm:px-6">

                <div class="max-w-2xl">
                    <p class="motif-rule mb-4 w-32 justify-self-start text-sm font-semibold uppercase tracking-[0.14em] text-primary-700">
                        @include('spmb.partials.motif-star', ['ukuran' => 'h-4 w-4', 'kelas' => 'shrink-0'])
                        <span class="shrink-0">Di sini</span>
                    </p>
                    <h2 class="reveal font-display text-3xl font-extrabold leading-tight text-ink-900 sm:text-4xl">
                        Lokasi sekolah
                    </h2>
                </div>

                <div class="mt-10">
                    @include('spmb.partials.peta-sekolah')
                </div>
            </div>
        </section>

        {{-- ============ VISI & MISI ============ --}}
        {{-- Ritme halaman ini: gelap, 100, 200, 100, 200, 100, 200, gelap.
             Dipisah-pisah begini karena dua section bersebelahan dengan warna
             yang sama terbaca seperti satu blok besar, dan jeda di
             antaranya yang seharusnya ada justru hilang. Identitas dan
             Visi-Misi pernah sama-sama `kertas-200` setelah blok Identitas
             disisipkan. --}}
        @if ($visiSekolah || $misiList)
            <section id="visi-misi" class="bg-kertas-200 py-20 sm:py-24">
                <div class="mx-auto max-w-6xl px-5 sm:px-6">
                    <h2 class="reveal font-display text-3xl font-extrabold leading-tight text-ink-900 sm:text-4xl">
                        Visi &amp; Misi
                    </h2>

                    <div class="mt-10 grid gap-8 lg:grid-cols-5 lg:gap-10">
                        {{-- Visi jadi panel hijau tua karena itu satu kalimat
                             yang paling ingin dibaca orang tua; misi jadi daftar
                             bernomor ringan di sebelahnya. --}}
                        @if ($visiSekolah)
                            <div class="lg:col-span-2">
                                <div class="flex h-full flex-col rounded-lg bg-primary-800 p-8 text-white">
                                    <h3 class="font-display text-sm font-extrabold uppercase tracking-wider text-secondary-300">
                                        Visi
                                    </h3>
                                    <p class="mt-4 font-display text-2xl font-semibold leading-snug">
                                        {{ $visiSekolah }}
                                    </p>
                                </div>
                            </div>
                        @endif

                        @if ($misiList)
                            <div class="{{ $visiSekolah ? 'lg:col-span-3' : 'lg:col-span-5' }}">
                                <h3 class="font-display text-sm font-extrabold uppercase tracking-wider text-ink-500">
                                    Misi
                                </h3>
                                <ol class="mt-5 space-y-4">
                                    @foreach ($misiList as $index => $misi)
                                        <li class="flex gap-4 border-b border-kertas-300 pb-4 last:border-b-0">
                                            <span aria-hidden="true"
                                                  class="grid h-8 w-8 shrink-0 place-items-center rounded-md border border-primary-200 bg-primary-50 font-display text-sm font-extrabold text-primary-800">
                                                {{ $index + 1 }}
                                            </span>
                                            <p class="min-w-0 flex-1 text-base leading-relaxed text-ink-700">{{ $misi }}</p>
                                        </li>
                                    @endforeach
                                </ol>
                            </div>
                        @endif
                    </div>
                </div>
            </section>
        @endif

        {{-- ============ NILAI-NILAI ============ --}}
        <section class="bg-kertas-100 py-20 sm:py-24">
            <div class="mx-auto max-w-6xl px-5 sm:px-6">

                <div class="max-w-2xl">
                    <h2 class="reveal font-display text-3xl font-extrabold leading-tight text-ink-900 sm:text-4xl">
                        Nilai-nilai sekolah
                    </h2>
                    <p class="reveal mt-5 text-base leading-relaxed text-ink-600">
                        Enam nilai yang membentuk karakter setiap siswa kami.
                    </p>
                </div>

                {{-- Grid tiga kolom, kartu rata. Versi lama memberi tiap kartu
                     gradien sendiri sehingga enam nilai ini terbaca sebagai
                     enam hal yang sama besar, padahal tidak ada yang perlu
                     dibedakan (R-05). --}}
                <ul class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($nilaiList as $nilai)
                        <li class="reveal rounded-lg border border-kertas-300 bg-kertas-200 p-6">
                            <span class="grid h-10 w-10 place-items-center rounded-md bg-primary-800 text-secondary-300">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8"
                                     viewBox="0 0 24 24" aria-hidden="true">
                                    @foreach ($renderIcon($nilai['icon']) as $d)
                                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $d }}"/>
                                    @endforeach
                                </svg>
                            </span>
                            <h3 class="mt-5 font-display text-lg font-extrabold text-ink-900">{{ $nilai['title'] }}</h3>
                            <p class="mt-2 text-sm leading-relaxed text-ink-600">{{ $nilai['desc'] }}</p>
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>

        {{-- ============ SAMBUTAN KEPALA SEKOLAH ============ --}}
        @if ($kepalaSekolah->sambutan)
            <section class="bg-kertas-200 py-20 sm:py-24">
                <div class="mx-auto max-w-5xl px-5 sm:px-6">
                    <div class="grid gap-8 sm:grid-cols-12 sm:gap-10">
                        @if ($kepalaSekolah->foto)
                            <div class="sm:col-span-4">
                                <img src="{{ $kepalaSekolah->foto }}"
                                     alt="{{ $kepalaSekolah->nama ?: 'Kepala sekolah' }}"
                                     width="400" height="500"
                                     class="aspect-[4/5] w-full rounded-md object-cover"
                                     loading="lazy" decoding="async">
                            </div>
                        @endif

                        <blockquote class="{{ $kepalaSekolah->foto ? 'sm:col-span-8' : 'sm:col-span-12' }}">
                            <h2 class="font-display text-sm font-extrabold uppercase tracking-wider text-ink-500">
                                Sambutan Kepala Sekolah
                            </h2>
                            <p class="mt-5 text-lg leading-relaxed text-ink-800">{{ $kepalaSekolah->sambutan }}</p>
                            @if ($kepalaSekolah->nama)
                                <footer class="mt-6 border-t border-kertas-300 pt-4">
                                    <span class="font-display text-base font-extrabold text-ink-900">{{ $kepalaSekolah->nama }}</span>
                                    <span class="mt-0.5 block text-sm text-ink-500">{{ $kepalaSekolah->jabatan }} {{ $namaSekolah }}</span>
                                </footer>
                            @endif
                        </blockquote>
                    </div>
                </div>
            </section>
        @endif

        {{-- ============ FASILITAS ============ --}}
        <section class="bg-kertas-200 py-20 sm:py-24">
            <div class="mx-auto max-w-6xl px-5 sm:px-6">

                <div class="max-w-2xl">
                    <h2 class="reveal font-display text-3xl font-extrabold leading-tight text-ink-900 sm:text-4xl">
                        Fasilitas penunjang belajar
                    </h2>
                    <p class="reveal mt-5 text-base leading-relaxed text-ink-600">
                        Lingkungan belajar yang lengkap dan nyaman untuk mendukung
                        tumbuh kembang siswa.
                    </p>
                </div>

                <ul class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($fasilitasList as $fasilitas)
                        <li class="reveal rounded-lg border border-kertas-300 bg-kertas-200 p-6">
                            <span class="grid h-10 w-10 place-items-center rounded-md bg-secondary-50 text-secondary-700">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8"
                                     viewBox="0 0 24 24" aria-hidden="true">
                                    @foreach ($renderIcon($fasilitas['icon']) as $d)
                                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $d }}"/>
                                    @endforeach
                                </svg>
                            </span>
                            <h3 class="mt-5 font-display text-lg font-extrabold text-ink-900">{{ $fasilitas['title'] }}</h3>
                            <p class="mt-2 text-sm leading-relaxed text-ink-600">{{ $fasilitas['desc'] }}</p>
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>

        {{-- ============ CTA ============ --}}
        <section class="on-dark bg-primary-900 py-20 sm:py-24">
            <div class="mx-auto max-w-6xl px-5 sm:px-6">
                <div class="bingkai inline-block max-w-lg border border-primary-800">
                    <img src="{{ $fotoGedung }}"
                         alt="Gedung {{ $namaSekolah }}"
                         width="1600" height="900"
                         class="block w-full rounded-lg object-cover"
                         loading="lazy" decoding="async">
                </div>

                <div class="mt-10 max-w-xl">
                    <h2 class="font-display text-3xl font-extrabold leading-tight text-white">
                        Siap bergabung bersama kami?
                    </h2>
                    <p class="mt-5 text-base leading-relaxed text-secondary-100/90">
                        Wujudkan langkah pertama menuju masa depan cerah
                        putra-putri Anda.
                    </p>
                    <a href="{{ route('spmb.pendaftaran') }}" class="btn btn-accent mt-7">
                        Mulai Pendaftaran
                    </a>
                </div>
            </div>
        </section>
    </main>
@endsection
