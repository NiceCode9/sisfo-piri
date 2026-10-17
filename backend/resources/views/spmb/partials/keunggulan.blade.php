{{--
    Empat kartu keunggulan, seperti aslinya. Dua di antaranya (Program
    Unggulan, Fasilitas, Prestasi) masih hardcode dan belum punya tabel sendiri,
    jadi nilainya harus diisi dari panel administrasi atau dibiarkan sebagai
    catatan sampai kelasnya punya sumber data.

    Bentuknya bento, bukan empat kotak sama besar.

    Alasannya bukan selera. Di layout empat kolom rata, kartu Kuota
    menumpuk lima informasi (angka, bar, persentase, jumlah kelas) sementara
    tiga kartu lain hanya daftar empat butir. Bobot visualnya sama, padahal
    isinya tidak sama, jadi mata tidak tahu harus lihat mana lebih dulu.
     Memberi Kuota dua kolom dan dua baris sementara tiga kartu lainnya
    sisa ruang membuat hierarki baca mengikuti isi, bukan urutan array.

    Kartu kuota tetap membaca `ringkasanKuota` dari database, bukan angka
    tetap, karena inilah angka yang paling sering berubah sendiri.
--}}

@php
    $kuota = $ringkasanKuota ?? ['kapasitas' => 0, 'terisi' => 0, 'persen' => 0, 'jumlah_kelas' => 0, 'tanpa_batas' => false];
@endphp

@php
    // Isi ketiga kartu yang datanya masih hardcode. Dipisah ke array supaya
    // setiap kartu cuma tinggal memanggil, dan menambah satu poin cukup
    // menyisipkan satu baris di sini.
    $programUnggulan = ['Kurikulum Merdeka', 'Kelas Digital Interaktif', 'Pembinaan Karakter', 'Program Bahasa Asing'];

    $fasilitas = ['Lab Komputer & Sains', 'Perpustakaan Digital', 'Lapangan Olahraga', 'Aula Serbaguna'];

    $prestasi = ['Terakreditasi A', 'Juara Olimpiade Sains', 'Sekolah Adiwiyata', 'Tingkat Kelulusan 100%'];

    // Ikon dicetak inline, bukan lewat ikon font: tidak ada paket ikon di
    // proyek ini, dan aset SVG per ikon justru menambah request untuk
    // gambar yang kecil.
    $centang = 'M5 13l4 4L19 7';

    // Ikon tiap kartu disimpan di satu tempat supaya grid bisa mengulang
    // markup yang sama tiga kali. Höhe kartu ditentukan isi, bukan urutan.
    $kartu = [
        ['judul' => 'Program Unggulan', 'ikon' => 'M9.663 17h4.673M12 3v1m6.364 1.636-.707.707M21 12h-1M4 12H3m3.343-5.657-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z', 'butir' => $programUnggulan],
        ['judul' => 'Fasilitas Lengkap', 'ikon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4', 'butir' => $fasilitas],
        ['judul' => 'Prestasi Gemilang', 'ikon' => 'M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z', 'butir' => $prestasi],
    ];
@endphp
{{-- Tanpa `on-dark`. Section ini terang, dan `on-dark` memaksa cincin fokus
     memakai coral terang yang hanya 2.78:1 di atas teal gelap. Di atas kertas
     cincin gelaplah yang lolos, jadi penandanya harus ikut hilang. --}}
<section id="tentang" class="bg-kertas-200 py-20 sm:py-24">
    <div class="mx-auto max-w-6xl px-5 sm:px-6">

        {{-- Judul rata kiri. Section ini dibaca sambil menggulir, bukan
             ditonton, jadi blok judul yang tinggi di tengah hanya menambah
             jarak kosong sebelum informasi pertama. --}}
        <div class="max-w-2xl">
            <p class="motif-rule mb-4 w-32 justify-self-start text-sm font-semibold uppercase tracking-[0.14em] text-primary-700">
                @include('spmb.partials.motif-star', ['ukuran' => 'h-4 w-4', 'kelas' => 'shrink-0'])
                <span class="shrink-0">Keunggulan</span>
            </p>
            <h2 class="reveal font-display text-3xl font-extrabold leading-tight text-ink-900 sm:text-4xl">
                Kenapa harus pilih <span class="text-primary-700">sekolah kami?</span>
            </h2>
            <p class="reveal mt-5 text-base leading-relaxed text-ink-600">
                Empat hal yang paling sering ditanyakan orang tua sebelum
                mendaftar: programnya, fasilitasnya, sisa kursinya, dan rekam
                prestasinya.
            </p>
        </div>

        {{-- `lg:row-span-2` dipakai di kartu kuota supaya kartunya membentang
             dua baris, dan tiga kartu daftar saling berbagi baris sisanya.
             Pada mobile semua satu kolom, jadi urutan baca tetap mengikuti
             urutan yang di-$kartu: program, fasilitas, prestasi, lalu kuota. --}}
        <div class="mt-12 grid gap-6 lg:grid-cols-4">

            {{-- Kartu 1-3: daftar butir. `min-w-0` pada wrapper `<li>`
                 wajib: tanpa itu, butir yang panjang seperti "Lab Komputer &
                 Sains" menolak untuk turun, dan flex memotong sisanya jadi
                 satu baris yang meluber keluar kartu. --}}
            @foreach (array_slice($kartu, 0, 2) as $k)
                <article class="reveal flex flex-col rounded-lg border border-kertas-300 bg-kertas-100 p-6">
                    <span class="grid h-10 w-10 place-items-center rounded-md bg-primary-50 text-primary-700">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2"
                             viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $k['ikon'] }}"/>
                        </svg>
                    </span>
                    <h3 class="mt-5 font-display text-lg font-extrabold text-ink-900">{{ $k['judul'] }}</h3>
                    <ul class="mt-4 space-y-2.5">
                        @foreach ($k['butir'] as $butir)
                            <li class="flex min-w-0 gap-2 text-sm leading-relaxed text-ink-700">
                                <svg class="mt-0.5 h-4 w-4 shrink-0 text-primary-600" fill="none"
                                     stroke="currentColor" stroke-width="3" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $centang }}"/>
                                </svg>
                                <span class="min-w-0">{{ $butir }}</span>
                            </li>
                        @endforeach
                    </ul>
                </article>
            @endforeach

            {{-- Kartu kuota: tile besar di panel gelap, di sisi kiri grid.

                 Warna teks di sini bukan primary-100 dengan opasitas. Secondary
                 terang di atas teal gelap diukur: secondary-300 = 7.93:1,
                 secondary-400 = 5.97:1, secondary-500 cuma 4.36:1 dan gagal.
                 Jadi teksnya memakai slot 200 ke atas tanpa modifier opacity,
                 yang selalu berada di atas ambang. --}}
            <article class="reveal relative isolate flex flex-col overflow-hidden rounded-lg bg-primary-800 p-7 text-white lg:col-span-2 lg:row-span-2 lg:p-9">
                @include('spmb.partials.motif-star', ['ukuran' => 'h-40 w-40', 'kelas' => 'motif-watermark -right-8 -top-10'])
                <span class="grid h-10 w-10 place-items-center rounded-md bg-primary-700 text-secondary-300">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2"
                         viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </span>
                <h3 class="mt-5 font-display text-lg font-extrabold text-white">Kuota Pendaftaran</h3>

                {{-- Angka besar. `data-hitung` membuat JS menghitung naik
                     dari 0 saat kartu ini masuk viewport, dan `tabular`
                     menjaga lebar glyph tetap sama supaya baris tidak
                     bergeser setiap digit berganti. Tanpa JavaScript
                     angkanya tetap tampil utuh, karena JS hanya menulis ulang
                     teks yang sudah ada. --}}
                <p class="mt-6 font-display text-6xl font-extrabold leading-none text-secondary-300 tabular lg:text-7xl">
                    @if ($kuota['tanpa_batas'])
                        <span class="text-4xl">Tanpa batas</span>
                    @elseif ($kuota['kapasitas'])
                        <span data-hitung="{{ $kuota['kapasitas'] }}">{{ $kuota['kapasitas'] }}</span>
                        <span class="text-lg font-semibold text-secondary-200">kursi</span>
                    @else
                        <span class="text-4xl">Belum diatur</span>
                    @endif
                </p>

                {{-- `tabular` ada di elemen pembungkus, bukan di dalam setiap angka.

                     Menyelubungi tiap angka dengan `<span>` sendiri terlihat lebih
                     rapi, tapi ia memutus satu kalimat jadi beberapa text node
                     sehingga teksnya jadi "75 dari300 kursi": angka dan kata
                     yang mengikutinya tidak lagi berbagi lebar glyph yang sama,
                     dan pencarian teks di halaman ini tidak menemukan
                     "75 dari 300 kursi terisi" sebagai satu kalimat. --}}
                <p class="mt-4 max-w-sm text-sm leading-relaxed text-secondary-200 tabular">
                    @if ($kuota['tanpa_batas'])
                        Sekolah membuka pendaftaran tanpa batas jumlah peserta
                        untuk tahun ajaran ini.
                    @elseif ($kuota['kapasitas'])
                        {{ $kuota['terisi'] }} dari {{ $kuota['kapasitas'] }} kursi terisi
                    @else
                        Kuota pendaftaran belum diatur sekolah.
                    @endif
                </p>

                {{-- Bar progress adalah data, dan angka yang sama ditulis ulang
                     sebagai teks supaya maknanya tidak bergantung pada
                     penglihatan warna saja (C-4). --}}
                @unless ($kuota['tanpa_batas'])
                    <div class="mt-6 h-2 w-full overflow-hidden rounded-full bg-primary-700">
                        <div class="h-2 rounded-full bg-secondary-400"
                             style="width: {{ $kuota['kapasitas'] ? min($kuota['persen'], 100) : 0 }}%"></div>
                    </div>
                    <p class="mt-2 text-xs text-secondary-200 tabular">
                        @if ($kuota['kapasitas'])
                            {{ $kuota['persen'] }}% dari kursi sudah terisi.
                        @else
                            Kuota per batch belum diatur.
                        @endif
                    </p>
                @endunless

                <dl class="mt-auto pt-6">
                    <div class="flex items-baseline justify-between gap-3 border-t border-primary-700 pt-4 text-sm">
                        <dt class="text-secondary-200">Kelas tersedia</dt>
                        <dd class="font-semibold text-white tabular">{{ $kuota['jumlah_kelas'] ?: 'Belum ada' }}</dd>
                    </div>
                </dl>
            </article>

            {{-- Kartu 4. HTML-nya sama seperti dua kartu pertama; hanya
                 posisinya di grid yang berubah, jadi kodenya dijaga oleh
                 loop yang sama, bukan disalin. --}}
            @foreach (array_slice($kartu, 2) as $k)
                <article class="reveal flex flex-col rounded-lg border border-kertas-300 bg-kertas-100 p-6">
                    <span class="grid h-10 w-10 place-items-center rounded-md bg-primary-50 text-primary-700">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2"
                             viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $k['ikon'] }}"/>
                        </svg>
                    </span>
                    <h3 class="mt-5 font-display text-lg font-extrabold text-ink-900">{{ $k['judul'] }}</h3>
                    <ul class="mt-4 space-y-2.5">
                        @foreach ($k['butir'] as $butir)
                            <li class="flex min-w-0 gap-2 text-sm leading-relaxed text-ink-700">
                                <svg class="mt-0.5 h-4 w-4 shrink-0 text-primary-600" fill="none"
                                     stroke="currentColor" stroke-width="3" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $centang }}"/>
                                </svg>
                                <span class="min-w-0">{{ $butir }}</span>
                            </li>
                        @endforeach
                    </ul>
                </article>
            @endforeach
        </div>
    </div>
</section>
