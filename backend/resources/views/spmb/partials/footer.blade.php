@php
    // $profileSekolah dishare otomatis via View composer (spmb.*).
    // Pakai accessor `*Bersih` supaya placeholder "GANTI: ..." dan data dummy
    // lama tidak ikut tampil di footer.
    $schoolName = $namaSekolah ?? 'Sekolah';
    $schoolLogo = ! empty($profileSekolah?->logo_path)
        ? Storage::disk('public')->url($profileSekolah->logo_path)
        : null;
    $schoolInitial = mb_substr($schoolName, 0, 1);
    $schoolAlamat = $profileSekolah?->alamat_bersih;
    $schoolTelp = $profileSekolah?->telp_bersih;
    $schoolTelpTel = $profileSekolah?->telp_tel;
    $schoolWhatsapp = $profileSekolah?->whatsapp;
    $schoolEmail = $profileSekolah?->email_bersih;
    $currentYear = date('Y');

    // Versi lama menaruh tautan jangkar di footer, padahal footer ikut
    // dirender di halaman Tentang, Pendaftaran, dan Pengumuman. Di sana
    // #biaya atau #alur tidak ada, jadi setiap tautannya mati. Karena itu
    // semua tujuan section ditulis sebagai URL penuh ke Beranda.
    //
    // Dua tautan punya syarat tambahan: section-nya disembunyikan kalau
    // tabelnya kosong, jadi tautannya ikut disembunyikan. Menampilkan
    // "Unduh Berkas" yang menuju ke nowhere lebih buruk daripada tidak
    // menampilkannya. Syaratnya datang dari view composer.
    $footerSections = array_values(array_filter([
        ['label' => 'Tentang SPMB', 'url' => route('spmb.home') . '#tentang'],
        ['label' => 'Jalur Pendaftaran', 'url' => route('spmb.home') . '#jalur'],
        ['label' => 'Alur Pendaftaran', 'url' => route('spmb.home') . '#alur'],
        ['label' => 'Jadwal Gelombang', 'url' => route('spmb.home') . '#gelombang'],
        ['label' => 'Biaya Pendidikan', 'url' => route('spmb.home') . '#biaya'],
        ['label' => 'Kontak', 'url' => route('spmb.home') . '#kontak'],
        ($adaBerkasUnduhan ?? false) ? ['label' => 'Unduh Berkas', 'url' => route('spmb.home') . '#unduhan'] : null,
        ($adaGaleri ?? false) ? ['label' => 'Galeri Sekolah', 'url' => route('spmb.home') . '#galeri'] : null,
    ]));

    $footerPages = [
        ['label' => 'Profil Sekolah', 'url' => route('spmb.about')],
        ['label' => 'Visi & Misi', 'url' => route('spmb.about') . '#visi-misi'],
        ['label' => 'Lokasi Sekolah', 'url' => route('spmb.about') . '#lokasi'],
        ['label' => 'Hasil Seleksi', 'url' => route('spmb.home') . '#gelombang'],
        ['label' => 'Semua Pengumuman', 'url' => route('spmb.pengumuman.index')],
        ['label' => 'Formulir Pendaftaran', 'url' => route('spmb.pendaftaran')],
    ];

    $hasContact = $schoolAlamat || $schoolTelpTel || $schoolWhatsapp || $schoolEmail;
@endphp

<footer class="on-dark bg-primary-900">
    {{-- Bintang delapan dari partial yang sama dengan hero Beranda, Keunggulan,
         Jalur, Galeri, dan Identitas Sekolah. Versi lama di sini masih
         menggambar sendiri sebagai dua `<rect>` yang diputar 45 derajat, jadi
         bentuknya beda dari bagian halaman lain padahal sama-sama mengklaim
         sebagai motif yang sama. --}}
    <div class="mx-auto max-w-6xl px-5 pt-14 sm:px-6">
        <div class="motif-rule mb-10 max-w-xs text-primary-300">
            @include('spmb.partials.motif-star', ['ukuran' => 'h-5 w-5'])
        </div>

        <div class="grid gap-12 pb-14 md:grid-cols-2 lg:grid-cols-12 lg:gap-10">

            {{-- Identitas + deskripsi sekolah. --}}
            <div class="lg:col-span-5">
                <div class="flex items-center gap-3">
                    @if ($schoolLogo)
                        {{-- `width`/`height` hanya ditulis sekali. Baris ini sempat
                             punya `width="480" height="360"` DAN `width="48"
                             height="48"` sekaligus: HTML mengabaikan atribut
                             pertama yang diulang, jadi tampilan kebetulan benar,
                             tapi rasio intrinsik yang dipakai browser untuk
                             reserving space adalah 480x360 (4:3), bukan 1:1
                             milik slot 48px. --}}
                        <img width="48" height="48" src="{{ $schoolLogo }}" alt="Logo {{ $schoolName }}"
                             class="h-12 w-12 shrink-0 object-contain"
                             loading="lazy" decoding="async">
                    @else
                        <span aria-hidden="true"
                              class="grid h-12 w-12 shrink-0 place-items-center rounded-md bg-primary-700 font-display text-xl font-extrabold text-white">
                            {{ $schoolInitial }}
                        </span>
                    @endif
                    <div>
                        <p class="font-display text-lg font-extrabold leading-tight text-white">{{ $schoolName }}</p>
                        <p class="mt-0.5 text-sm text-primary-200">Penerimaan Murid Baru</p>
                    </div>
                </div>

                <p class="mt-6 max-w-sm text-sm leading-relaxed text-primary-200">
                    Membentuk generasi cerdas, berkarakter, dan berprestasi
                    untuk masa depan Indonesia yang gemilang.
                </p>
            </div>

            {{-- Menu Cepat: semua section yang benar-benar ada di Beranda.
                 Tautan yang tidak ada ditukar, bukan dibiarkan mati. --}}
            <nav class="lg:col-span-3" aria-label="Menu cepat">
                <h2 class="font-display text-sm font-extrabold uppercase tracking-wider text-secondary-300">
                    Menu Cepat
                </h2>
                <ul class="mt-4 space-y-1">
                    <li>
                        <a href="{{ route('spmb.home') }}#beranda"
                           class="inline-flex min-h-11 items-center text-sm text-primary-200 transition-colors hover:text-white">
                            Beranda
                        </a>
                    </li>
                    @foreach ($footerSections as $link)
                        <li>
                            <a href="{{ $link['url'] }}"
                               class="inline-flex min-h-11 items-center text-sm text-primary-200 transition-colors hover:text-white">
                                {{ $link['label'] }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </nav>

            {{-- Informasi: tautan antar halaman, bukan jangkar seksi. --}}
            <nav class="lg:col-span-2" aria-label="Informasi">
                <h2 class="font-display text-sm font-extrabold uppercase tracking-wider text-secondary-300">
                    Informasi
                </h2>
                <ul class="mt-4 space-y-1">
                    @foreach ($footerPages as $link)
                        <li>
                            <a href="{{ $link['url'] }}"
                               class="inline-flex min-h-11 items-center text-sm text-primary-200 transition-colors hover:text-white">
                                {{ $link['label'] }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </nav>

            {{-- Kontak. --}}
            <div class="lg:col-span-2">
                <h2 class="font-display text-sm font-extrabold uppercase tracking-wider text-secondary-300">
                    Kontak Kami
                </h2>

                @if ($hasContact)
                    <ul class="mt-4 space-y-3">
                        @if ($schoolAlamat)
                            <li class="flex gap-2.5 text-sm leading-relaxed text-primary-200">
                                <svg class="mt-0.5 h-4 w-4 shrink-0 text-secondary-300" fill="none" stroke="currentColor"
                                     stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0zM15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                <span>{!! nl2br(e($schoolAlamat)) !!}</span>
                            </li>
                        @endif

                        @if ($schoolTelp || $schoolWhatsapp)
                            <li class="flex gap-2.5 text-sm leading-relaxed text-primary-200">
                                <svg class="mt-0.5 h-4 w-4 shrink-0 text-secondary-300" fill="none" stroke="currentColor"
                                     stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                </svg>
                                <span>
                                    @if ($schoolTelp)<span class="block">{{ $schoolTelp }}</span>@endif
                                    @if ($schoolWhatsapp)
                                        <a href="https://wa.me/{{ $schoolWhatsapp }}" target="_blank" rel="noopener"
                                           class="inline-flex min-h-11 items-center underline decoration-primary-300 underline-offset-4 transition-colors hover:decoration-secondary-300">
                                            WhatsApp
                                        </a>
                                    @endif
                                </span>
                            </li>
                        @endif

                        @if ($schoolEmail)
                            <li class="flex gap-2.5 text-sm leading-relaxed text-primary-200">
                                <svg class="mt-0.5 h-4 w-4 shrink-0 text-secondary-300" fill="none" stroke="currentColor"
                                     stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                                <a href="mailto:{{ $schoolEmail }}"
                                   class="inline-flex min-h-11 items-center break-all underline decoration-primary-300 underline-offset-4 transition-colors hover:decoration-secondary-300">
                                    {{ $schoolEmail }}
                                </a>
                            </li>
                        @endif
                    </ul>
                @else
                    <p class="mt-4 text-sm leading-relaxed text-primary-200">
                        Data kontak sekolah belum dilengkapi. Halaman ini diurus
                        oleh pihak sekolah melalui panel administrasi.
                    </p>
                @endif
            </div>
        </div>
    </div>

    <div class="border-t border-primary-500">
        <div class="mx-auto flex max-w-6xl flex-col gap-2 px-5 py-6 text-sm text-primary-200 sm:px-6 md:flex-row md:items-center md:justify-between">
            <p>&copy; {{ $currentYear }} {{ $schoolName }}. Hak cipta dilindungi.</p>
            <p>Penerimaan Murid Baru Tahun Ajaran {{ $tahunAjaranAktif?->nama_tahun_ajaran ?? 'berjalan' }}</p>
        </div>
    </div>
</footer>
