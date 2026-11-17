@extends('layouts.spmb')

@section('title', 'Guru & Karyawan - '.($namaSekolah ?? 'SPMB'))

@section('content')
    <main id="konten-utama">
        @include('spmb.partials.navbar', ['transparent' => true])

        <section class="on-dark bg-primary-900 pt-28 pb-20 text-white sm:pt-36 sm:pb-28">
            <div class="mx-auto max-w-6xl px-5 sm:px-6">
                <p class="inline-flex items-center gap-2 rounded-md bg-white/10 px-3 py-1.5 text-sm font-semibold text-secondary-300">
                    Guru &amp; Karyawan
                </p>
                <h1 class="mt-6 max-w-3xl text-balance font-display text-4xl font-extrabold leading-[1.1] tracking-tight sm:text-5xl">
                    Tenaga pendidik dan kependidikan
                    <span class="text-secondary-300">{{ $namaSekolah }}</span>
                </h1>
                <p class="reveal mt-6 max-w-2xl text-base leading-relaxed text-primary-200 sm:text-lg">
                    Daftar guru dan karyawan yang aktif melayani di sekolah.
                </p>
            </div>
        </section>

        <section class="bg-kertas-100 py-20 sm:py-24">
            <div class="mx-auto max-w-6xl px-5 sm:px-6">
                @if ($gurus->isEmpty())
                    <p class="text-base text-ink-600">Belum ada data guru dan karyawan.</p>
                @else
                    <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($gurus as $guru)
                            <article class="reveal overflow-hidden rounded-lg border border-kertas-300 bg-kertas-200">
                                @if ($guru->foto_path)
                                    <img src="{{ Storage::disk('public')->url($guru->foto_path) }}"
                                         alt="{{ $guru->nama }}"
                                         class="block h-56 w-full object-cover"
                                         loading="lazy" decoding="async">
                                @else
                                    <div class="grid h-56 place-items-center bg-kertas-300 text-ink-500">
                                        <span class="font-display text-4xl font-extrabold">{{ mb_substr($guru->nama, 0, 1) }}</span>
                                    </div>
                                @endif
                                <div class="p-5">
                                    <h2 class="font-display text-lg font-extrabold text-ink-900">{{ $guru->nama }}</h2>
                                    @if ($guru->jabatan)
                                        <p class="mt-1 text-sm font-semibold text-primary-700">{{ $guru->jabatan }}</p>
                                    @endif
                                    <dl class="mt-3 space-y-1 text-sm text-ink-600">
                                        @if ($guru->tanggal_lahir)
                                            <div class="flex gap-2">
                                                <dt class="font-semibold">Tgl Lahir:</dt>
                                                <dd>{{ $guru->tanggal_lahir->translatedFormat('d M Y') }}</dd>
                                            </div>
                                        @endif
                                        @if ($guru->alamat)
                                            <div class="flex gap-2">
                                                <dt class="font-semibold">Alamat:</dt>
                                                <dd class="min-w-0 break-words">{{ $guru->alamat }}</dd>
                                            </div>
                                        @endif
                                    </dl>
                                </div>
                            </article>
                        @endforeach
                    </div>
                @endif
            </div>
        </section>
    </main>
@endsection