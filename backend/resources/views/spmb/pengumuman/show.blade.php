@extends('layouts.spmb')

@section('title', ($pengumuman->judul ?? 'Pengumuman') . ' - ' . ($namaSekolah ?? 'Sekolah'))

@php
    // Objek pengumuman selalu datang dari route model binding
    // (SpmbController@pengumumanShow), jadi tidak ada cadangan yang perlu
    // ditulis di sini. Versi lama punya objek dummy berisi pengumuman
    // "hasil seleksi gelombang 1" lengkap dengan tanggal daftar ulang. Kalau
    // controller someday salah dan variabelnya kosong, halaman publik akan
    // menampilkan pengumuman yang tidak pernah ada (R-38).

    $tanggal = \Carbon\Carbon::parse($pengumuman->tanggal_pengumuman);

    // Bagikan lewat WhatsApp karena itu kanal yang paling mungkin dipakai
    // orang tua di Indonesia. Teksnya hanya judul dan tautan, keduanya
    // benar-benar ada di halaman ini.
    $teksBagikan = rawurlencode($pengumuman->judul . ' - ' . request()->url());
@endphp

@section('content')
    <main id="konten-utama">
        @include('spmb.partials.navbar')

        <article class="bg-kertas-100 pt-28 pb-20 sm:pt-32 sm:pb-24">
            <div class="mx-auto max-w-3xl px-5 sm:px-6">

                <a href="{{ route('spmb.pengumuman.index') }}"
                   class="inline-flex min-h-11 items-center gap-2 text-sm font-medium text-primary-800 transition-colors hover:text-primary-900">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2"
                         stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Semua pengumuman
                </a>

                {{-- Metadata ditulis sebagai daftar, bukan badge pill. Badge
                     "Pengumuman" di atas judul hanya mengulang isi halaman,
                     dan pill-nya sendiri tidak membawa informasi apa pun. --}}
                <div class="mt-8 flex flex-wrap items-center gap-x-4 gap-y-1 border-b border-kertas-300 pb-6 text-sm">
                    <time datetime="{{ $tanggal->toDateString() }}" class="font-semibold text-secondary-700">
                        {{ $tanggal->translatedFormat('j F Y') }}
                    </time>
                    @if ($pengumuman->tahunAjaran?->nama_tahun_ajaran)
                        <span class="text-ink-600">Tahun ajaran {{ $pengumuman->tahunAjaran->nama_tahun_ajaran }}</span>
                    @endif
                </div>

                <h1 class="mt-8 font-display text-3xl font-extrabold leading-tight text-ink-900 sm:text-4xl">
                    {{ $pengumuman->judul }}
                </h1>

                {{-- Isi pengumuman ditulis sekolah sebagai teks biasa dengan
                     baris baru, jadi `white-space: pre-line` dipakai untuk
                     menghormati paragrafnya. Kalau nanti isi diisi HTML dari
                     editor kaya, ganti blok ini dengan {!! !!} yang sudah
                     disanitasi. --}}
                <div class="mt-8 whitespace-pre-line text-base leading-relaxed text-ink-800 sm:text-lg">
                    {{ $pengumuman->isi }}
                </div>

                <div class="mt-12 flex flex-wrap gap-3 border-t border-kertas-300 pt-6">
                    <a href="https://wa.me/?text={{ $teksBagikan }}"
                       target="_blank" rel="noopener"
                       class="btn btn-quiet">
                        Bagikan lewat WhatsApp
                    </a>
                    <button type="button" id="cetak-pengumuman" class="btn btn-quiet">
                        Cetak halaman ini
                    </button>
                </div>

                <p class="mt-8 text-sm leading-relaxed text-ink-500">
                    Ada yang tidak sesuai dengan informasi di atas? Hubungi sekolah
                    lewat halaman kontak, dan sekolah bisa memperbarui pengumuman ini.
                </p>
            </div>
        </article>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const tombol = document.getElementById('cetak-pengumuman');
            if (tombol) tombol.addEventListener('click', function () { window.print(); });
        });
    </script>
@endsection