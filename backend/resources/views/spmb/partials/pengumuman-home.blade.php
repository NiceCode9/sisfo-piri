@php
    $daftarPengumuman = ($pengumumans ?? collect())->values();
    $terbaru = $daftarPengumuman->first();
    $sisanya = $daftarPengumuman->slice(1);
@endphp

{{--
    Bentuknya diubah dari tiga kartu sama besar jadi satu yang diberi penekanan
    dik Alongside daftar ringkas. Alasannya isi: pengumuman terbaru biasanya
    satu hal penting (hasil seleksi, jadwal daftar ulang), sedangkan yang
    sebelumnya cuma arsip. Kalau ketiganya diberi bobot visual yang sama,
    orang tua harus membacanya satu per satu untuk menemukan yang penting.

    Ikon `<i class="far fa-calendar-alt">` dihapus. FontAwesome tidak dimuat
    di layout SPMB, jadi ikon itu tidak pernah tampil dan cuma menyisakan
    elemen kosong. Glyph digambar inline sebagai SVG kalau memang perlu.
--}}

<section id="pengumuman" class="bg-kertas-100 py-24 sm:py-32">
    <div class="mx-auto max-w-6xl px-5 sm:px-6">

        <div class="flex flex-wrap items-end justify-between gap-6">
            <div class="max-w-2xl">
                <h2 class="reveal font-display text-3xl font-extrabold leading-tight text-ink-900 sm:text-4xl">
                    Pengumuman <span class="text-primary-700">Terbaru</span>
                </h2>
                <p class="reveal mt-5 text-base leading-relaxed text-ink-600">
                    Informasi resmi seputar PPDB. Hanya pengumuman yang masih
                    aktif dari sekolah yang muncul di sini.
                </p>
            </div>

            @if ($daftarPengumuman->isNotEmpty())
                <a href="{{ route('spmb.pengumuman.index') }}" class="btn btn-quiet">
                    Lihat Semua Pengumuman
                </a>
            @endif
        </div>

        @forelse ($daftarPengumuman as $p)
            @php
                $adalahTerbaru = $p->is($terbaru);

                // Kolom tanggalnya disimpan sebagai string, bukan instance
                // Carbon, jadi harus di-parse dulu sebelum dipakai untuk format
                // dan atribut datetime.
                $tanggal = $p->tanggal_pengumuman
                    ? \Carbon\Carbon::parse($p->tanggal_pengumuman)
                    : null;
            @endphp

            @if ($adalahTerbaru)
                {{-- Pengumuman terbaru diberi latar gelap supaya jelas mana yang jadi
                     sorotan. Sisanya tetap ringan, jadi ada tepat satu titik
                     fokus di section ini. --}}
                <article class="reveal mt-10 rounded-lg bg-primary-800 p-6 text-white sm:p-8">
                    <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-sm">
                        <time datetime="{{ $tanggal?->toDateString() }}" class="font-semibold text-secondary-300">
                            {{ $tanggal?->translatedFormat('j F Y') }}
                        </time>
                        @if ($p->tahunAjaran?->nama_tahun_ajaran)
                            <span class="text-secondary-100/70">Tahun ajaran {{ $p->tahunAjaran->nama_tahun_ajaran }}</span>
                        @endif
                    </div>

                    <h3 class="mt-3 font-display text-2xl font-extrabold leading-tight">{{ $p->judul }}</h3>
                    <p class="mt-3 max-w-2xl text-base leading-relaxed text-secondary-100/90">
                        {{ \Illuminate\Support\Str::limit(strip_tags($p->isi), 220) }}
                    </p>
                    <a href="{{ route('spmb.pengumuman.show', $p) }}" class="btn mt-6 bg-white text-primary-900 hover:bg-kertas-200">
                        Baca Pengumuman Lengkap
                    </a>
                </article>
            @else
                {{-- Sisanya jadi daftar satu baris, bukan kartu. --}}
                <article class="reveal mt-6 flex flex-wrap items-baseline justify-between gap-x-6 gap-y-2 border-t border-kertas-300 py-5">
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-sm">
                            <time datetime="{{ $tanggal?->toDateString() }}" class="font-semibold text-secondary-700">
                                {{ $tanggal?->translatedFormat('j M Y') }}
                            </time>
                            @if ($p->tahunAjaran?->nama_tahun_ajaran)
                                <span class="text-ink-500">{{ $p->tahunAjaran->nama_tahun_ajaran }}</span>
                            @endif
                        </div>
                        <h3 class="mt-1.5 font-display text-lg font-extrabold text-ink-900">{{ $p->judul }}</h3>
                        <p class="mt-1.5 max-w-2xl text-sm leading-relaxed text-ink-600">
                            {{ \Illuminate\Support\Str::limit(strip_tags($p->isi), 140) }}
                        </p>
                    </div>
                    <a href="{{ route('spmb.pengumuman.show', $p) }}"
                       class="inline-flex min-h-11 shrink-0 items-center font-semibold text-primary-800 underline decoration-primary-200 underline-offset-4 transition-colors hover:decoration-primary-600">
                        Baca
                    </a>
                </article>
            @endif
        @empty
            {{-- Empty state (R-27). --}}
            <div class="reveal mt-10 max-w-xl rounded-md border border-kertas-300 bg-kertas-200 p-8">
                <h3 class="font-display text-lg font-extrabold text-ink-900">Belum ada pengumuman</h3>
                <p class="mt-2 text-base leading-relaxed text-ink-600">
                    Sekolah belum memasang pengumuman apa pun. Informasi resmi
                    soal daftar ulang, jadwal tes, dan hasil seleksi akan muncul
                    di halaman ini begitu diterbitkan.
                </p>
            </div>
        @endforelse
    </div>
</section>
