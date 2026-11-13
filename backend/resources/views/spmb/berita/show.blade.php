@extends('layouts.spmb')

@section('title', ($berita->meta_title ?: $berita->judul) . ' - ' . ($namaSekolah ?? 'Sekolah'))

@section('meta_description', $berita->meta_description ?: \Illuminate\Support\Str::limit(strip_tags($berita->ringkasan ?: $berita->konten), 160))

@section('canonical', route('berita.show', $berita))

@section('og_type', 'article')

@section('og_title', $berita->meta_title ?: $berita->judul)

@section('og_description', $berita->meta_description ?: \Illuminate\Support\Str::limit(strip_tags($berita->ringkasan ?: $berita->konten), 160))

@if ($berita->gambar_utama)
    @section('og_image', asset('storage/' . $berita->gambar_utama))
@endif

@section('content')
    <main id="konten-utama">
        @include('spmb.partials.navbar')

        <article class="bg-kertas-100 pt-28 pb-20 sm:pt-32 sm:pb-24">
            <div class="mx-auto max-w-3xl px-5 sm:px-6">

                <a href="{{ route('berita.index') }}"
                   class="inline-flex min-h-11 items-center gap-2 text-sm font-medium text-primary-800 transition-colors hover:text-primary-900">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2"
                         stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Semua berita
                </a>

                <div class="mt-8 flex flex-wrap items-center gap-x-4 gap-y-1 border-b border-kertas-300 pb-6 text-sm">
                    @if ($berita->published_at)
                        <time datetime="{{ $berita->published_at->toDateString() }}" class="font-semibold text-secondary-700">
                            {{ $berita->published_at->translatedFormat('j F Y') }}
                        </time>
                    @endif
                    @if ($berita->kategori)
                        <span class="text-ink-600">{{ $berita->kategori->nama }}</span>
                    @endif
                    @if ($berita->penulis)
                        <span class="text-ink-500">Oleh {{ $berita->penulis->name }}</span>
                    @endif
                </div>

                <h1 class="mt-8 font-display text-3xl font-extrabold leading-tight text-ink-900 sm:text-4xl">
                    {{ $berita->judul }}
                </h1>

                @if ($berita->ringkasan)
                    <p class="mt-4 text-lg leading-relaxed text-ink-700">{{ $berita->ringkasan }}</p>
                @endif

                <div class="mt-8 text-base leading-relaxed text-ink-800 sm:text-lg">
                    {!! $berita->konten !!}
                </div>

                <div class="mt-8 flex flex-wrap items-center gap-3 border-t border-kertas-300 pt-6">
                    @auth
                        <form method="POST" action="{{ route('berita.like', $berita) }}">
                            @csrf
                            <button type="submit" class="inline-flex min-h-11 items-center rounded-md border border-kertas-300 px-4 text-sm font-semibold text-ink-800">
                                {{ $liked ? 'Batal suka' : 'Suka' }} ({{ $berita->likes()->count() }})
                            </button>
                        </form>
                    @else
                        <span class="text-sm text-ink-600">{{ $berita->likes()->count() }} suka. Masuk untuk menyukai.</span>
                    @endauth
                    <a class="text-sm font-semibold text-primary-800" href="https://wa.me/?text={{ urlencode($berita->judul.' '.route('berita.show', $berita)) }}" target="_blank" rel="noopener">Bagikan WhatsApp</a>
                </div>

                <section class="mt-10">
                    <h2 class="font-display text-xl font-extrabold text-ink-900">Komentar</h2>
                    @if (session('success'))
                        <p class="mt-3 text-sm text-secondary-700">{{ session('success') }}</p>
                    @endif
                    @if ($komentars->isEmpty())
                        <p class="mt-3 text-sm text-ink-600">Belum ada komentar yang disetujui.</p>
                    @else
                        <ul class="mt-4 space-y-4">
                            @foreach ($komentars as $komentar)
                                <li class="border-b border-kertas-300 pb-3">
                                    <p class="text-sm font-semibold text-ink-900">{{ $komentar->nama }}</p>
                                    <p class="mt-1 text-sm text-ink-700">{{ $komentar->isi }}</p>
                                </li>
                            @endforeach
                        </ul>
                    @endif

                    <form method="POST" action="{{ route('berita.komentar.store', $berita) }}" class="mt-6 space-y-3">
                        @csrf
                        <input type="text" name="website" class="hidden" tabindex="-1" autocomplete="off">
                        @guest
                            <input type="text" name="nama" value="{{ old('nama') }}" placeholder="Nama" required class="w-full rounded-md border border-kertas-300 px-3 py-2">
                            <input type="email" name="email" value="{{ old('email') }}" placeholder="Email" required class="w-full rounded-md border border-kertas-300 px-3 py-2">
                        @endguest
                        <textarea name="isi" required rows="4" placeholder="Tulis komentar" class="w-full rounded-md border border-kertas-300 px-3 py-2">{{ old('isi') }}</textarea>
                        @error('isi')<p class="text-sm text-red-700">{{ $message }}</p>@enderror
                        <button type="submit" class="inline-flex min-h-11 items-center rounded-md bg-primary-800 px-4 text-sm font-semibold text-white">Kirim komentar</button>
                    </form>
                </section>

                @if ($berita->tags->isNotEmpty())
                    <div class="mt-8 flex flex-wrap gap-2">
                        @foreach ($berita->tags as $t)
                            <a href="{{ route('berita.index', ['tag' => $t->slug]) }}" class="text-sm text-primary-800 underline">{{ $t->nama }}</a>
                        @endforeach
                    </div>
                @endif

                @if ($related->isNotEmpty())
                    <section class="mt-12 border-t border-kertas-300 pt-8">
                        <h2 class="font-display text-xl font-extrabold text-ink-900">Berita Terkait</h2>
                        <ul class="mt-4 space-y-3">
                            @foreach ($related as $r)
                                <li>
                                    <a href="{{ route('berita.show', $r) }}" class="font-semibold text-primary-800 hover:text-primary-900">{{ $r->judul }}</a>
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endif
            </div>
        </article>

        <script type="application/ld+json">
        {!! json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'NewsArticle',
            'headline' => $berita->judul,
            'description' => $berita->meta_description ?: \Illuminate\Support\Str::limit(strip_tags($berita->ringkasan ?: $berita->konten), 160),
            'datePublished' => optional($berita->published_at)->toIso8601String(),
            'dateModified' => optional($berita->updated_at)->toIso8601String(),
            'author' => ['@type' => 'Person', 'name' => $berita->penulis?->name],
            'publisher' => ['@type' => 'Organization', 'name' => $namaSekolah ?? 'Sekolah'],
            'mainEntityOfPage' => route('berita.show', $berita),
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
        </script>
    </main>
@endsection