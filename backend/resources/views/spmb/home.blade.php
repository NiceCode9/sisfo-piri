@extends('layouts.spmb')

@section('title', ($namaSekolah ?? 'Sekolah').' - Penerimaan Murid Baru')

@section('content')
    <main id="konten-utama">
        {{-- Navbar. Transparan karena section pertama di bawahnya adalah
             panel teal gelap; halaman tanpa hero gelap membiarkan nilainya
             default false. --}}
        @include('spmb.partials.navbar', ['transparent' => true])

        @include('spmb.partials.hero')

        {{--
            Urutan di bawah mengikuti urutan yang dibaca orang tua: sekolah
            ini seperti apa, masuknya lewat jalur mana, harus lewat langkah
            apa, biayanya berapa, jadwalnya kapan, lalu kabar terbaru.

            Ritme section: terang, gelap, terang, gelap. Tiga panel gelap ada
            di hero, Jalur, dan Galeri. Jaraknya tidak seragam supaya halaman
            tidak berdenyut seperti metronom yang bisa dihitung orang tua, tapi
            tidak juga berganti tiap section supaya polanya terlalu mudah
            ditebak.

            Versi lama memakai satu pola yang diulang sebelas kali: judul di
            tengah, subtitle di bawahnya, lalu grid kartu, dengan latar yang
            hanya bergantian antara dua warna kertas dan jarak yang selalu
            `py-20 sm:py-24`. Sebelas section dengan ritme yang sama berarti
            halaman tidak punya titik berat, dan mata tidak punya alasan untuk
            melambat di tempat mana pun.
        --}}

        @include('spmb.partials.keunggulan')

        @include('spmb.partials.jalur-seleksi')

        @include('spmb.partials.alur-pendaftaran')

        @include('spmb.partials.unduhan')

        @include('spmb.partials.gelombang')

        @include('spmb.partials.biaya')

        @include('spmb.partials.pengumuman-home')

        @include('spmb.partials.ekstrakurikuler')

        @include('spmb.partials.galeri')

        @include('spmb.partials.kontak2')

        {{-- Satu dialog untuk Galeri dan Berkas Unduhan. --}}
        @include('spmb.partials.lightbox')
    </main>
@endsection