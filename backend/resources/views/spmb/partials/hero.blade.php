{{-- Statistik & badge di bawah memakai data nyata: tahun ajaran aktif,
     jumlah siswa/guru aktif, dan jendela pendaftaran dari database. --}}
@php
    $tahunAjaran = $tahunAjaranAktif?->nama_tahun_ajaran;
@endphp

<section id="beranda" class="relative min-h-screen flex items-center bg-gradient-primary overflow-hidden">

    {{-- Dekorasi blob + animasi `float` dihapus pada phase editorial: pola
     "blob gradient" itu penanda template 2020. Kedalaman sekarang datang dari
     garis rambut dan jarak, bukan bentuk organik yang melayang. --}}

    <div class="container mx-auto px-4 py-20 relative z-10">
        <div class="grid lg:grid-cols-2 gap-12 items-center">

            {{-- Left Content --}}
            <div class="text-white space-y-6 animate-fade-in-up">
                {{-- Status pendaftaran dihitung sekali di App\Support\StatusPendaftaran dan
     dipakai bersama oleh badge ini, peringatan di halaman pendaftaran, serta
     gate di store(). Satu sumber, jadi ketiganya tidak bisa berbeda pendapat. --}}
                @if ($statusPendaftaran?->tampilkanBadge())
                    <div @class([
                        'inline-block px-4 py-2 rounded-full text-sm font-semibold shadow-sm',
                        'bg-secondary-500' => $statusPendaftaran->state === \App\Support\StatusPendaftaran::BUKA,
                        'bg-amber-400 text-amber-950' => $statusPendaftaran->state === \App\Support\StatusPendaftaran::BELUM,
                        'bg-white/25' => in_array($statusPendaftaran->state, [
                            \App\Support\StatusPendaftaran::PENUH,
                            \App\Support\StatusPendaftaran::TUTUP,
                        ], true),
                    ])>
                        {{-- Emoji 🎉 dihapus: di halaman penerimaan murid ia terbaca amatir,
             bukan seperti di halaman produk. --}}
                        {{ $statusPendaftaran->teksBadge() }}
                    </div>
                @endif

<h1 class="font-display text-display-lg text-white">
                    Wujudkan Impianmu<br/>
                    <span class="text-secondary-300">Bersama Kami!</span>
                </h1>

                <p class="text-lg md:text-xl text-white/85 leading-relaxed max-w-xl">
                    Bergabunglah dalam Penerimaan Murid Baru
                    @if ($tahunAjaran)
                        Tahun Ajaran <span class="font-medium text-white">{{ $tahunAjaran }}</span>
                    @endif.
                    Raih prestasi, kembangkan bakatmu, dan ciptakan masa depan cerah!
                </p>

                {{-- CTA Buttons. Sudut sengaja kecil dan tanpa bayangan:
                     tombol yang "melompat" (hover:scale-105 +) adalah
                     tanda template, bukan tanda yang meyakinkan. --}}
                <div class="flex flex-col sm:flex-row gap-4 pt-4">
                    <a href="#alur" class="inline-flex items-center justify-center px-8 py-4 text-base font-medium text-ink bg-white rounded-soft hover:bg-paper-alt transition-colors">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        Mulai Pendaftaran
                    </a>
                    <a href="{{ route('login') }}" class="inline-flex items-center justify-center px-8 py-4 text-base font-medium text-white border border-white/60 rounded-soft hover:bg-white hover:text-ink transition-colors">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        Login Calon Murid
                    </a>
                </div>

                {{-- Stats --}}
                {{-- Angka diambil dari database lewat view composer spmb.*.
                     Kosong berarti belum ada data, jadi tampilkan tanda pisah
                     dan bukan angka karangan. --}}
                {{-- Angka memakai serif agar terbaca sebagai data, bukan badge. --}}
                <div class="grid grid-cols-3 gap-4 pt-8">
                    <div>
                        <div class="font-display text-display-md text-secondary-300">{{ $jumlahSiswaAktif ?: '—' }}</div>
                        <div class="text-sm text-white/70">Siswa Aktif</div>
                    </div>
                    <div>
                        <div class="font-display text-display-md text-secondary-300">{{ $jumlahGuruAktif ?: '—' }}</div>
                        <div class="text-sm text-white/70">Guru Aktif</div>
                    </div>
                    <div>
                        <div class="font-display text-display-md text-secondary-300">{{ $lamaBerdiri ?: '—' }}</div>
                        <div class="text-sm text-white/70">Tahun Berdiri</div>
                    </div>
                </div>
            </div>

            {{-- Right Photo --}}
            <div class="relative animate-fade-in-up" style="animation-delay: 0.3s;">
                {{-- Foto asli sekolah, bukan foto stok. Sumber aslinya 4032x2268
                     (5,4 MB) hanya diturunkan ukuran dan dibuang EXIF-nya; versi
                     web ada di public/images/sekolah. --}}
                <div class="relative z-10">
                    {{-- `width`/`height` wajib ada supaya browser bisa
                         menyediakan ruang gambar sebelum file selesai dimuat.
                         Tanpa itu halaman bergeser (CLS) tepat di bagian yang
                         paling pertama dilihat calon. --}}
                    <img src="/images/sekolah/gedung-960w.jpg"
                         srcset="/images/sekolah/gedung-480w.jpg 480w,
                                 /images/sekolah/gedung-960w.jpg 960w,
                                 /images/sekolah/gedung-1600w.jpg 1600w"
                         sizes="(min-width: 1024px) 620px, 100vw"
                         width="1600" height="900"
                         alt="Gedung dan lapangan {{ $namaSekolah ?? 'sekolah' }}"
                         class="w-full h-auto object-cover"
                         loading="eager" fetchpriority="high" decoding="async">
                </div>

                {{-- Floating Card 1: akreditasi.
                     Dirender hanya kalau nilainya benar-benar diisi. Versi lama
                     selalu menulis "Sekolah Terakreditasi" walau kolomnya kosong,
                     sementara sub-teksnya sendiri mengakui "Status belum
                     dicantumkan" - jadi halaman mengarang klaim yang
                     bersamaan menyangkal dirinya sendiri. --}}
                @if ($profileSekolah?->akreditasi_bersih)
                    <div class="absolute -bottom-6 -left-6 z-20 bg-paper px-5 py-4 hairline hidden md:block">
                        <div class="flex items-center space-x-3">
                            <div class="w-9 h-9 bg-accent-600 rounded-soft flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div>
                                <div class="text-sm font-medium text-ink">Terakreditasi {{ $profileSekolah->akreditasi_bersih }}</div>
                                <div class="text-xs text-ink-muted">Standar Nasional</div>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Floating Card 2.
                     Versi lama menulis "Kurikulum Merdeka / Update & Inovatif"
                     sebagai teks mati: tidak ada kolom, tidak ada sumber, dan
                     tidak bisa dibantah kalau ternyata tidak berlaku. Diganti
                     tahun ajaran aktif, yang benar-benar berasal dari database
                     dan selalu tersedia di halaman PPDB. --}}
                @if ($tahunAjaran)
                <div class="absolute -top-6 -right-6 z-20 bg-paper px-5 py-4 hairline hidden md:block">
                    <div class="flex items-center space-x-3">
                        <div class="w-9 h-9 bg-secondary-500 rounded-soft flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                            </svg>
                        </div>
                        <div>
                            <div class="text-sm font-medium text-ink">Tahun Ajaran {{ $tahunAjaran }}</div>
                            <div class="text-xs text-ink-muted">Penerimaan Murid Baru</div>
                        </div>
                    </div>
                </div>
                @endif
            </div>

        </div>
    </div>

    {{-- Scroll Indicator --}}
    <div class="absolute bottom-8 left-1/2 transform -translate-x-1/2 animate-bounce">
        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
        </svg>
    </div>

</section>
