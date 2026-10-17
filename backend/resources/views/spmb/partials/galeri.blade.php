@php
    // Data dari database (dikelola via halaman admin Galeri).
    $fotos = ($galeriFotos ?? collect())->values();
    $daftarPrestasi = ($prestasis ?? collect())->values();
@endphp

{{--
    Dua perubahan besar dari versi lama.

    Pertama, grid desktop dan carousel mobile dihapus jadi satu grid yang
    berubah jumlah kolomnya sendiri. Versi lama menyalin isi galeri dua kali,
    dan caption di desktop disembunyikan sampai hover sedangkan di mobile
    selalu terlihat, jadi orang tua yang memakai laptop tidak pernah membaca
    judul fotonya sama sekali. Caption sekarang selalu tampil di bawah foto.

    Kedua, setiap foto bisa diklik dan dibuka lewat dialog yang sama dengan
    berkas unduhan. Versi lamanya tidak punya interaksi apa pun di desktop:
    kotak foto hanya menerima hover.

    Pola "foto pertama dan foto keenam jadi besar" juga dihapus. Posisi besar
    ditentukan oleh urutan array, bukan oleh isi fotonya, jadi tidak
    mengatakan apa pun tentang kegiatan mana yang penting (R-05).

    Ketiga, section ini dipindahkan ke panel gelap. Foto di atas teal gelap
    jauh lebih menonjol daripada di atas kertas hangat, karena yang dihitung
    oleh mata adalah terang-gelap relatif: foto terang di atas latar terang
    nyaris tidak terlihat. Latar gelap juga membuat galeri terbaca sebagai jeda
    di antara dua section yang sama padatnya.
--}}

@if ($fotos->isNotEmpty() || $daftarPrestasi->isNotEmpty())
    <section id="galeri" class="on-dark bg-primary-900 py-24 sm:py-32">
        <div class="mx-auto max-w-6xl px-5 sm:px-6">

            @if ($fotos->isNotEmpty())
                <div class="max-w-2xl">
                    <p class="motif-rule mb-4 w-32 justify-self-start text-sm font-semibold uppercase tracking-[0.14em] text-secondary-300">
                        @include('spmb.partials.motif-star', ['ukuran' => 'h-4 w-4', 'kelas' => 'shrink-0'])
                        <span class="shrink-0">Dokumentasi</span>
                    </p>
                    <h2 class="reveal font-display text-3xl font-extrabold leading-tight text-white sm:text-4xl">
                        Galeri &amp; <span class="text-secondary-300">Prestasi</span>
                    </h2>
                    <p class="reveal mt-5 text-base leading-relaxed text-primary-200">
                        Lihat momen-momen berharga dan prestasi membanggakan
                        siswa kami. Klik salah satu untuk melihat lebih besar.
                    </p>
                </div>

                {{-- `aspect-[4/3]` menjaga semua sel sama tinggi tanpa tinggi
                     piksel tetap, jadi grid ini tetap rapi kalau nanti sekolah
                     menambah foto dengan rasio berbeda.

                     Tombolnya tidak diberi warna latar: di panel gelap, kotak
                     primary-800 hanya 1.22:1 terhadap primary-900 sehingga
                     nyaris hilang. Fotony sendiri yang jadi bentuk, dan cincin
                     coral saat hover yang menandai ini bisa diklik. --}}
                <ul class="mt-10 grid grid-cols-2 gap-4 sm:gap-6 lg:grid-cols-3">
                    @foreach ($fotos as $foto)
                        <li class="reveal">
                            <button type="button"
                                    class="js-open-preview group block w-full overflow-hidden rounded-md text-left"
                                    aria-haspopup="dialog"
                                    data-image="{{ Storage::disk('public')->url($foto->image_path) }}"
                                    data-title="{{ $foto->title }}">
                                <span class="block aspect-[4/3] overflow-hidden rounded-md ring-1 ring-primary-500 transition group-hover:ring-2 group-hover:ring-secondary-400">
                                    <img width="480" height="360" src="{{ Storage::disk('public')->url($foto->image_path) }}"
                                         alt="{{ $foto->title }}"
                                         class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
                                         loading="lazy" decoding="async">
                                </span>
                                <span class="mt-3 block">
                                    <span class="block font-display text-sm font-extrabold leading-snug text-white">
                                        {{ $foto->title }}
                                    </span>
                                    @if ($foto->desc)
                                        <span class="mt-1 block text-sm leading-relaxed text-primary-200">{{ $foto->desc }}</span>
                                    @endif
                                </span>
                            </button>
                        </li>
                    @endforeach
                </ul>
            @endif

            {{-- Prestasi ditulis sebagai daftar bertanggal, bukan tiga kartu
                 berwarna. Kartu membuat semua prestasi terasa sama penting,
                 padahal yang baru terjadi biasanya yang paling dicari. --}}
            @if ($daftarPrestasi->isNotEmpty())
                <div class="{{ $fotos->isNotEmpty() ? 'mt-20' : '' }} max-w-3xl">
                    <h2 class="reveal font-display text-2xl font-extrabold leading-tight text-white">
                        Prestasi yang tercatat
                    </h2>

                    {{-- Garis memakai primary-500, bukan primary-700. Di atas
                         teal-900, primary-700 hanya 1.56:1 dan primary-600
                         2.25:1, keduanya di bawah batas 3:1 untuk batas yang
                         harus terlihat (WCAG 1.4.11). --}}
                    <ol class="mt-6 border-y border-primary-500">
                        @foreach ($daftarPrestasi as $prestasi)
                            <li class="flex flex-wrap items-baseline gap-x-4 gap-y-1 border-b border-primary-800 py-4 last:border-b-0">
                                @if ($prestasi->tanggal)
                                    <time datetime="{{ $prestasi->tanggal }}"
                                          class="w-28 shrink-0 text-sm font-semibold text-secondary-300 tabular">
                                        {{ \Carbon\Carbon::parse($prestasi->tanggal)->translatedFormat('M Y') }}
                                    </time>
                                @endif
                                <div class="min-w-0 flex-1">
                                    <h3 class="font-display text-base font-extrabold text-white">{{ $prestasi->title }}</h3>
                                    @if ($prestasi->desc)
                                        <p class="mt-1 text-sm leading-relaxed text-primary-200">{{ $prestasi->desc }}</p>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ol>
                </div>
            @endif
        </div>
    </section>
@endif
