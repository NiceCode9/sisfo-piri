{{--
    Section kontak: empat kartu (Alamat, Telepon & WhatsApp, Email, Jam
    Pelayanan) di kiri, peta dan media sosial di kanan. Semuanya kembali
    seperti versi lama; yang dipoles hanya bentuknya.

    Yang diubah dari versi lama:

    - `bg-white rounded-3xl shadow-lg` menjadi kartu dengan garis 1px.
      Empat kartu yang melayang membuat halaman terasa seperti katalog,
      bukan kontak yang perlu dibaca cepat (R-12).
    - Kartu "Jam Pelayanan" tidak lagi memakai ungu sebagai warna keempat.
      Halaman ini sudah punya tiga warna inti; warna keempat hanya untuk
      membedakan satu kartu dari tiga tetangganya, dan itu tidak cukup alasan
      (R-29).
    - Peta tidak lagi memakai koordinat Bandung sebagai cadangan. Itu lokasi
      yang salah untuk sekolah ini, dan orang tua yang percaya peta itu akan
      datang ke tempat keliru. Kartunya tetap ada; kalau tautannya belum
      diisi, yang tampil adalah alamat teks.

    Isi kartu masih memakai data profil, supaya sekolah bisa memperbarui
    semuanya dari satu tempat.
--}}

@php
    $telpTel = $profileSekolah?->telp_tel;
    $wa = $profileSekolah?->whatsapp;
    $email = $profileSekolah?->email_bersih;
    $alamat = $profileSekolah?->alamat_bersih;

    // Peta tidak lagi dihitung di sini. `spmb.partials.peta-sekolah` yang
    // menentukannya, dan partial yang sama dipakai section Identitas di
    // halaman Tentang.
    $adaKontak = $alamat || $telpTel || $wa || $email;

    // Jam pelayanan belum punya kolom di profil_sekolahs, jadi nilainya masih
    // ditulis di sini. Kalau sekolah punya jam resmi, ini yang perlu diganti.
    $jamPelayanan = [
        'Senin - Jumat' => '07:30 - 15:00',
        'Sabtu' => '08:00 - 12:00',
        'Minggu & Libur' => null,
    ];

    // Entri tanpa URL asli disembunyikan. Kalau tidak, blok sosial media
    // menampilkan tombol yang tidak bisa diklik.
    $socialLinks = array_values(array_filter([
        ['name' => 'Facebook', 'url' => null],
        ['name' => 'Instagram', 'url' => null],
        ['name' => 'YouTube', 'url' => null],
        ['name' => 'X', 'url' => null],
    ], fn ($s) => filled($s['url']) && $s['url'] !== '#'));

    // Kartu diambil dari array supaya menambah satu jenis kontak cukup
    // menambah satu baris, bukan satu blok markup.
    $kontakCards = [
        ['kunci' => 'alamat', 'judul' => 'Alamat Sekolah', 'ikon' => 'M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0zM15 11a3 3 0 11-6 0 3 3 0 016 0z'],
        ['kunci' => 'telepon', 'judul' => 'Telepon & WhatsApp', 'ikon' => 'M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z'],
        ['kunci' => 'email', 'judul' => 'Email', 'ikon' => 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z'],
        ['kunci' => 'jam', 'judul' => 'Jam Pelayanan', 'ikon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'],
    ];

    $sosmed = [
        'Facebook' => 'M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z',
        'Instagram' => 'M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z',
        'YouTube' => 'M23.498 6.186a3.016 3.016 0 00-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 00.502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 002.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 002.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z',
        'X' => 'M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z',
    ];
@endphp

<section id="kontak" class="bg-kertas-100 py-24 sm:py-32">
    <div class="mx-auto max-w-6xl px-5 sm:px-6">

        <div class="max-w-2xl">
            <h2 class="reveal font-display text-3xl font-extrabold leading-tight text-ink-900 sm:text-4xl">
                Hubungi <span class="text-primary-700">Panitia SPMB</span>
            </h2>
            <p class="reveal mt-5 text-base leading-relaxed text-ink-600">
                Ada pertanyaan? Tim kami siap membantu Anda.
            </p>
        </div>

        <div class="mt-12 grid items-start gap-8 lg:grid-cols-2 lg:gap-12">

            {{-- Kartu kontak. --}}
            <div class="space-y-5">
                @foreach ($kontakCards as $card)
                    <article class="reveal rounded-lg border border-kertas-300 bg-kertas-200 p-6">
                        <div class="flex items-start gap-4">
                            <span class="grid h-11 w-11 shrink-0 place-items-center rounded-md bg-primary-50 text-primary-700">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8"
                                     viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $card['ikon'] }}"/>
                                </svg>
                            </span>

                            <div class="min-w-0 flex-1">
                                <h3 class="font-display text-base font-extrabold text-ink-900">{{ $card['judul'] }}</h3>

                                @switch($card['kunci'])
                                    @case('alamat')
                                        @if ($alamat)
                                            <p class="mt-2 text-base leading-relaxed text-ink-700">{!! nl2br(e($alamat)) !!}</p>
                                        @else
                                            <p class="mt-2 text-base leading-relaxed text-ink-500">Alamat belum diisi.</p>
                                        @endif
                                        @break

                                    @case('telepon')
                                        @if ($telpTel || $wa)
                                            <div class="mt-2 space-y-2">
                                                @if ($telpTel)
                                                    <div>
                                                        <p class="text-sm text-ink-500">Telepon Sekolah</p>
                                                        <a href="tel:{{ $telpTel }}"
                                                           class="inline-flex min-h-11 items-center font-semibold text-primary-800 underline decoration-primary-200 underline-offset-4 transition-colors hover:decoration-primary-600">
                                                            {{ $profileSekolah->telp_bersih }}
                                                        </a>
                                                    </div>
                                                @endif
                                                @if ($wa)
                                                    <div>
                                                        <p class="text-sm text-ink-500">WhatsApp Panitia</p>
                                                        <a href="https://wa.me/{{ $wa }}" target="_blank" rel="noopener"
                                                           class="inline-flex min-h-11 items-center font-semibold text-primary-800 underline decoration-primary-200 underline-offset-4 transition-colors hover:decoration-primary-600">
                                                            {{ $profileSekolah->telp_bersih ?? $wa }}
                                                        </a>
                                                    </div>
                                                @endif
                                            </div>
                                        @else
                                            <p class="mt-2 text-base leading-relaxed text-ink-500">Nomor kontak belum diisi.</p>
                                        @endif
                                        @break

                                    @case('email')
                                        @if ($email)
                                            <a href="mailto:{{ $email }}"
                                               class="mt-2 inline-flex min-h-11 items-center break-all font-semibold text-primary-800 underline decoration-primary-200 underline-offset-4 transition-colors hover:decoration-primary-600">
                                                {{ $email }}
                                            </a>
                                        @else
                                            <p class="mt-2 text-base leading-relaxed text-ink-500">Email belum diisi.</p>
                                        @endif
                                        @break

                                    @case('jam')
                                        <dl class="mt-2 space-y-1.5 text-sm">
                                            @foreach ($jamPelayanan as $hari => $jam)
                                                <div class="flex justify-between gap-4">
                                                    <dt class="text-ink-600">{{ $hari }}</dt>
                                                    <dd class="font-semibold text-ink-900">{{ $jam ?? 'Tutup' }}</dd>
                                                </div>
                                            @endforeach
                                        </dl>
                                        @break
                                @endswitch
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>

            {{-- Peta dan media sosial. --}}
            <div class="space-y-6">
                {{-- Peta diambil dari partial yang sama dengan section Identitas di
                     halaman Tentang, supaya kedua halaman tidak pernah
                     menampilkan peta yang berbeda. --}}
                @include('spmb.partials.peta-sekolah', [
                    'tinggi' => 'h-[300px] w-full sm:h-[420px] lg:h-[500px]',
                ])

                @if ($socialLinks)
                    <div class="reveal rounded-lg bg-primary-800 p-6 text-white sm:p-8">
                        <h3 class="font-display text-lg font-extrabold">Ikuti Media Sosial Kami</h3>
                        <p class="mt-1.5 text-sm text-secondary-100/85">
                            Dapatkan update terbaru seputar SPMB dan kegiatan sekolah.
                        </p>
                        <div class="mt-6 grid grid-cols-2 gap-3">
                            @foreach ($socialLinks as $social)
                                <a href="{{ $social['url'] }}" target="_blank" rel="noopener"
                                   aria-label="{{ $social['name'] }} sekolah"
                                   class="inline-flex min-h-11 items-center gap-3 rounded-md bg-primary-700 px-4 font-semibold transition-colors hover:bg-primary-600">
                                    <svg class="h-5 w-5 shrink-0" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path d="{{ $sosmed[$social['name']] }}"/>
                                    </svg>
                                    <span>{{ $social['name'] }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @else
                    <div class="reveal rounded-lg border border-kertas-300 bg-kertas-200 p-6">
                        <h3 class="font-display text-base font-extrabold text-ink-900">Ikuti Media Sosial Kami</h3>
                        <p class="mt-2 text-sm leading-relaxed text-ink-600">
                            Tautan media sosial sekolah belum diisi di panel
                            administrasi. Pengumuman resmi tetap terbit di halaman
                            pengumuman.
                        </p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>
