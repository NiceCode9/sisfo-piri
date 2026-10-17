{{-- Statistik di bawah memakai data nyata: tahun ajaran aktif, jumlah
     siswa dan guru aktif, serta jendela pendaftaran dari database. Kalau
     kosong, tampilkan tanda pisah dan bukan angka karangan (R-17). --}}
@php
    $tahunAjaran = $tahunAjaranAktif?->nama_tahun_ajaran;
    $status = $statusPendaftaran;
@endphp

<section id="beranda" class="relative bg-primary-900 text-white">

    {{-- Ornamen: satu bintang delapan besar, redup, di pojok kanan.
         Bentuk yang sama dengan motif kecil di bawah, hanya diperbesar.
         Motif ini menggantikan blob dekoratif karena bintang delapan milik
         sekolah islami ini, sementara blob hanya tren. --}}
    <svg aria-hidden="true"
         class="pointer-events-none absolute -top-10 right-[-3rem] hidden h-[26rem] w-[26rem] text-primary-700/30 lg:block"
         fill="none" stroke="currentColor" stroke-width="0.6" viewBox="0 0 24 24">
        <rect x="4.5" y="4.5" width="15" height="15" />
        <rect x="4.5" y="4.5" width="15" height="15" transform="rotate(45 12 12)" />
        <rect x="2.5" y="2.5" width="19" height="19" transform="rotate(22.5 12 12)" />
    </svg>

    <div class="mx-auto max-w-6xl px-5 pt-28 pb-16 sm:px-6 sm:pt-36 sm:pb-24">
        <div class="motif-rule mb-8 max-w-[10rem]">
            <svg class="motif-star" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                <rect x="4.5" y="4.5" width="15" height="15" />
                <rect x="4.5" y="4.5" width="15" height="15" transform="rotate(45 12 12)" />
            </svg>
        </div>

        {{-- Status pendaftaran dihitung sekali di App\Support\StatusPendaftaran
             lalu dipakai bersama oleh badge di sini, peringatan di halaman
             pendaftaran, dan gate di store(). Badge ini bukan hiasan: ia
             menandai keadaan nyata, jadi ia boleh ada (R-09). --}}
        @if ($status?->tampilkanBadge())
            @php
                $label = $status->teksBadge();
                $style = match ($status->state) {
                    \App\Support\StatusPendaftaran::BUKA => 'bg-secondary-400 text-primary-900',
                    \App\Support\StatusPendaftaran::BELUM => 'bg-white/15 text-white',
                    default => 'bg-white/10 text-white/90',
                };
                $dot = $status->state === \App\Support\StatusPendaftaran::BUKA ? 'bg-primary-800' : null;
            @endphp
            <p class="inline-flex items-center gap-2 rounded-md px-3 py-1.5 text-sm font-semibold {{ $style }}">
                @if ($dot)<span aria-hidden="true" class="h-2 w-2 rounded-full {{ $dot }}"></span>@endif
                {{ $label }}
            </p>
        @endif

        <h1 class="mt-6 max-w-2xl text-balance font-display text-4xl font-extrabold leading-[1.1] tracking-tight sm:text-5xl lg:text-6xl">
            Wujudkan Impianmu<br class="hidden sm:block" />
            <span class="text-secondary-300">Bersama Kami!</span>
        </h1>

        <p class="mt-6 max-w-xl text-base leading-relaxed text-secondary-100/90 sm:text-lg">
            Bergabunglah dalam Penerimaan Murid Baru
            @if ($tahunAjaran)
                Tahun Ajaran <span class="font-semibold">{{ $tahunAjaran }}</span>
            @endif.
            Raih prestasi, kembangkan bakatmu, dan ciptakan masa depan cerah!
        </p>

        <p class="mt-4 max-w-xl text-base leading-relaxed text-secondary-100/75">
            Tidak perlu berkas apa pun untuk mulai. Isi formulirnya sekarang,
            unggah berkasnya nanti.
        </p>

        <div class="mt-9 flex flex-col gap-3 sm:flex-row">
            <a href="#alur" class="btn btn-accent w-full sm:w-auto">
                Mulai Pendaftaran
            </a>
            <a href="{{ route('login') }}"
               class="btn w-full border border-white/25 text-white hover:border-white hover:bg-white/10 sm:w-auto">
                Login Calon Murid
            </a>
        </div>

        {{-- Angka diambil dari database lewat view composer spmb.*. Kalau
             jumlahnya nol, barisnya disembunyikan utuh. `filled(0)` bernilai
             true di Laravel, jadi menyaring dengan `filled` akan tetap
             menampilkan "0 siswa aktif", padahal sekolah yang baru berdiri
             belum punya data, bukan sekolah dengan nol siswa (R-17). --}}
        @php
            $stats = array_values(array_filter([
                $jumlahSiswaAktif > 0 ? ['nilai' => $jumlahSiswaAktif, 'label' => 'Siswa aktif'] : null,
                $jumlahGuruAktif > 0 ? ['nilai' => $jumlahGuruAktif, 'label' => 'Guru aktif'] : null,
                $lamaBerdiri ? ['nilai' => $lamaBerdiri, 'label' => 'Tahun berdiri'] : null,
            ]));
        @endphp

        @if ($stats)
            <dl class="mt-14 grid max-w-lg grid-cols-2 gap-6 border-t border-primary-800 pt-8 sm:grid-cols-3">
                @foreach ($stats as $stat)
                    <div>
                        <dt class="text-sm text-secondary-100/80">{{ $stat['label'] }}</dt>
                        <dd class="mt-1 font-display text-3xl font-extrabold text-secondary-300 sm:text-4xl">
                            {{ $stat['nilai'] }}
                        </dd>
                    </div>
                @endforeach
            </dl>
        @endif
    </div>

    {{-- Foto sekolah dan fakta yang menyertainya. Dulu foto ini dikelilingi
         dua kartu yang melayang tanpa henti. Diganti baris fakta di bawah
         foto, karena gerak tanpa pemicu hanya jadi kebisingan (R-19), dan
         baris fakta tetap terbaca di layar kecil. --}}
    <div class="relative mx-auto max-w-6xl px-5 pb-16 sm:px-6 sm:pb-24">
        <div class="bingkai inline-block max-w-xl border border-primary-800">
            {{-- `width`/`height` wajib ada supaya browser menyediakan ruang
                 gambar sebelum file selesai dimuat. Tanpa itu halaman
                 bergeser tepat di bagian yang paling pertama dilihat. --}}
            <img src="/images/sekolah/gedung-960w.jpg"
                 srcset="/images/sekolah/gedung-480w.jpg 480w,
                         /images/sekolah/gedung-960w.jpg 960w,
                         /images/sekolah/gedung-1600w.jpg 1600w"
                 sizes="(min-width: 1024px) 600px, 100vw"
                 width="1600" height="900"
                 alt="Gedung dan lapangan {{ $namaSekolah ?? 'sekolah' }}"
                 class="block w-full rounded-lg object-cover"
                 loading="eager" fetchpriority="high" decoding="async">
        </div>

        <dl class="mt-10 grid max-w-xl gap-4 sm:grid-cols-2">
            {{-- Akreditasi hanya dirender kalau kolomnya benar-benar diisi.
                 Versi lama selalu menulis "Sekolah Terakreditasi" walau kolomnya
                 kosong, sementara sub-teksnya sendiri mengakui "Status belum
                 dicantumkan", jadi halaman mengarang klaim yang justru
                 menyangkal dirinya sendiri. --}}
            @if ($profileSekolah?->akreditasi_bersih)
                <div class="rounded-md border border-primary-800 bg-primary-900/60 p-4">
                    <dt class="text-xs font-semibold uppercase tracking-wider text-secondary-300">Status Akreditasi</dt>
                    <dd class="mt-1 font-display text-lg font-extrabold text-white">
                        Terakreditasi {{ $profileSekolah->akreditasi_bersih }}
                    </dd>
                </div>
            @endif

            @if ($tahunAjaran)
                <div class="rounded-md border border-primary-800 bg-primary-900/60 p-4">
                    <dt class="text-xs font-semibold uppercase tracking-wider text-secondary-300">Tahun ajaran</dt>
                    <dd class="mt-1 font-display text-lg font-extrabold text-white">{{ $tahunAjaran }}</dd>
                </div>
            @endif
        </dl>
    </div>
</section>