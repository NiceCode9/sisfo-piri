@extends('layouts.spmb')

@section('title', 'Pengumuman - ' . ($namaSekolah ?? 'Sekolah'))

@section('content')
    <main id="konten-utama">
        @include('spmb.partials.navbar')

        <section class="bg-kertas-200 pt-28 pb-20 sm:pt-32 sm:pb-24">
            <div class="mx-auto max-w-3xl px-5 sm:px-6">
                <h1 class="reveal font-display text-3xl font-extrabold leading-tight text-ink-900 sm:text-4xl">
                    Pengumuman
                </h1>
                <p class="reveal mt-5 text-base leading-relaxed text-ink-600">
                    Hanya pengumuman yang masih aktif dari sekolah yang tampil di
                    halaman ini. Pengumuman yang sudah kedaluwarsa dipindahkan ke
                    arsip sekolah.
                </p>

                @if ($pengumumans->isEmpty())
                    {{-- Empty state (R-27): menyebut sebab dan jalan keluar. --}}
                    <div class="reveal mt-10 rounded-md border border-kertas-300 bg-kertas-100 p-8">
                        <h2 class="font-display text-lg font-extrabold text-ink-900">Belum ada pengumuman</h2>
                        <p class="mt-2 text-base leading-relaxed text-ink-600">
                            Sekolah belum memasang pengumuman apa pun. Informasi
                            resmi soal daftar ulang, jadwal seleksi, dan hasil
                            akan muncul di sini begitu diterbitkan.
                        </p>
                        <a href="{{ route('spmb.home') }}" class="btn btn-quiet mt-6">
                            Kembali ke Beranda
                        </a>
                    </div>
                @else
                    <ul class="mt-10 border-y border-kertas-300">
                        @foreach ($pengumumans as $p)
                            @php $tanggal = \Carbon\Carbon::parse($p->tanggal_pengumuman); @endphp
                            <li>
                                <a href="{{ route('spmb.pengumuman.show', $p) }}"
                                   class="group flex flex-wrap items-baseline justify-between gap-x-6 gap-y-2 border-b border-kertas-200 py-6 transition-colors hover:bg-kertas-100 last:border-b-0">
                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-sm">
                                            <time datetime="{{ $tanggal->toDateString() }}" class="font-semibold text-secondary-700">
                                                {{ $tanggal->translatedFormat('j F Y') }}
                                            </time>
                                            @if ($p->tahunAjaran?->nama_tahun_ajaran)
                                                <span class="text-ink-500">Tahun ajaran {{ $p->tahunAjaran->nama_tahun_ajaran }}</span>
                                            @endif
                                        </div>
                                        <h2 class="mt-2 font-display text-lg font-extrabold leading-snug text-ink-900">
                                            {{ $p->judul }}
                                        </h2>
                                        <p class="mt-2 text-sm leading-relaxed text-ink-600">
                                            {{ \Illuminate\Support\Str::limit(strip_tags($p->isi), 160) }}
                                        </p>
                                    </div>
                                    <span class="inline-flex min-h-11 shrink-0 items-center font-semibold text-primary-800">
                                        Baca
                                    </span>
                                </a>
                            </li>
                        @endforeach
                    </ul>

                    <div class="mt-10">
                        {{ $pengumumans->links() }}
                    </div>
                @endif
            </div>
        </section>
    </main>
@endsection