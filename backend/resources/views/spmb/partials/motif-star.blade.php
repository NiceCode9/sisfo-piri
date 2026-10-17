{{--
    Bintang delapan (Rub el Hizb) sebagai motif, bukan sebagai gambar.

    Dipakai di empat ukuran yang selalu menunjuk ke tempat yang sama, supaya
    halaman terasa punya milik sendiri:

    - `badge` 20px di samping judul section
    - `rule` sebagai pembatas di `.motif-rule`
    - `watermark` besar di panel gelap
    - `ring` di belakang angka kuota

    Bintang delapan dipakai karena itu lambang yang paling dikenal di
    arsitektur Nusantara dan di sekolah menengah keislaman, jadi tidak
    perlu dijelaskan. Yang membuat ia motifsistem, bukan hiasan, adalah
    konsistensi tempatnya: kalau muncul bergantian, ia jadi noise.

    PARAMETER
    - $ukuran  kelas UKURAN (`h-5`, `h-8`, ...). Default 20px.
    - $kelas   kelas tambahan untuk elemen svg-nya.

    `@include` dipakai, bukan komponen, supaya warna dan ukurannya ikut
    `currentColor` dari induknya.
--}}
@php
    $ukuran = $ukuran ?? 'h-5 w-5';
    $kelas = $kelas ?? '';
@endphp

{{-- `aria-hidden` dan `focusable="false"` wajib: bintang ini dekorasi yang
     diulang puluhan kali per halaman. Kalau terbaca sebagai gambar, pembaca
     layar akan membacakan "image" puluhan kali tanpa isi. --}}
<svg class="{{ $ukuran }} {{ $kelas }}"
     viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false">
    {{-- Sudut luar r=11.5, sudut dalam r=5.0, diselingi 22.5°. --}}
    <path d="M23.5 12 16.62 10.09 20.13 3.87 13.91 7.38 12 .5 10.09 7.38 3.87 3.87 7.38 10.09.5 12 7.38 13.91 3.87 20.13 10.09 16.62 12 23.5 13.91 16.62 20.13 20.13 16.62 13.91"/>
</svg>
