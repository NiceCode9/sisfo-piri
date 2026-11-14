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

@push('head')
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@graph' => [
        [
            '@type' => 'Article',
            'headline' => $berita->meta_title ?: $berita->judul,
            'description' => $berita->meta_description ?: \Illuminate\Support\Str::limit(strip_tags($berita->ringkasan ?: $berita->konten), 160),
            'image' => $berita->gambar_utama ? asset('storage/' . $berita->gambar_utama) : null,
            'datePublished' => optional($berita->published_at)->toIso8601String(),
            'dateModified' => optional($berita->updated_at)->toIso8601String(),
            'author' => ['@type' => 'Person', 'name' => $berita->penulis?->name ?? 'Redaksi'],
            'publisher' => ['@type' => 'Organization', 'name' => $namaSekolah ?? 'Sekolah'],
            'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => route('berita.show', $berita)],
            'keywords' => $berita->tags->pluck('nama')->implode(', '),
            'wordCount' => str_word_count(strip_tags($berita->konten ?? '')),
        ],
        [
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => 'Beranda', 'item' => url('/')],
                ['@type' => 'ListItem', 'position' => 2, 'name' => 'Berita', 'item' => route('berita.index')],
                ['@type' => 'ListItem', 'position' => 3, 'name' => $berita->judul, 'item' => route('berita.show', $berita)],
            ],
        ],
    ],
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
</script>
@endpush

@section('content')
<main id="konten-utama">
    @include('spmb.partials.navbar')

    <article class="bg-kertas-100 pt-24 pb-16 sm:pt-28 sm:pb-20">
        <div class="mx-auto max-w-6xl px-5 sm:px-6">
            <div class="grid gap-8 lg:grid-cols-[1fr_280px]">
                {{-- Main column --}}
                <div class="min-w-0">
                    {{-- Breadcrumb --}}
                    <nav class="flex flex-wrap items-center gap-1.5 text-xs text-ink-500" aria-label="Breadcrumb">
                        <a href="{{ route('spmb.home') }}" class="hover:text-primary-800">Beranda</a>
                        <i class="fa-solid fa-chevron-right text-[10px]"></i>
                        <a href="{{ route('berita.index') }}" class="hover:text-primary-800">Berita</a>
                        @if($berita->kategori)
                            <i class="fa-solid fa-chevron-right text-[10px]"></i>
                            <a href="{{ route('berita.index', ['kategori' => $berita->kategori->slug]) }}" class="hover:text-primary-800">{{ $berita->kategori->nama }}</a>
                        @endif
                    </nav>

                    {{-- Title --}}
                    <h1 class="mt-4 font-display text-2xl font-extrabold leading-tight text-ink-900 sm:text-3xl">
                        {{ $berita->judul }}
                    </h1>

                    {{-- Meta bar --}}
                    <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-ink-500">
                        @if($berita->published_at)
                            <span><i class="fa-regular fa-calendar mr-1"></i>{{ $berita->published_at->translatedFormat('j F Y') }}</span>
                        @endif
                        @if($berita->penulis)
                            <span><i class="fa-regular fa-user mr-1"></i>{{ $berita->penulis->name }}</span>
                        @endif
                        <span><i class="fa-regular fa-clock mr-1"></i>{{ max(1, (int) ceil(str_word_count(strip_tags($berita->konten ?? '')) / 200)) }} menit baca</span>
                        <span><i class="fa-regular fa-eye mr-1"></i>{{ number_format($berita->views_count) }}</span>
                    </div>

                    {{-- Hero image (smaller) --}}
                    @if($berita->gambar_utama)
                        <div class="mt-6 overflow-hidden rounded-xl border border-kertas-300 bg-kertas-200">
                            <img src="{{ asset('storage/' . $berita->gambar_utama) }}" alt="{{ $berita->judul }}"
                                 class="max-h-80 w-full object-cover sm:max-h-96">
                        </div>
                    @endif

                    {{-- Ringkasan --}}
                    @if($berita->ringkasan)
                        <p class="mt-6 text-base leading-relaxed text-ink-700 font-medium">{{ $berita->ringkasan }}</p>
                    @endif

                    {{-- Content --}}
                    <div class="prose prose-base mt-6 max-w-none text-ink-800 [&_img]:rounded-lg [&_img]:max-h-96 [&_img]:w-auto [&_a]:text-primary-800 [&_a]:underline">
                        {!! $berita->konten !!}
                    </div>

                    {{-- Tags --}}
                    @if($berita->tags->isNotEmpty())
                        <div class="mt-6 flex flex-wrap items-center gap-2">
                            <span class="text-sm font-semibold text-ink-600">Tag:</span>
                            @foreach($berita->tags as $t)
                                <a href="{{ route('berita.index', ['tag' => $t->slug]) }}" class="rounded-full border border-kertas-300 px-3 py-1 text-xs text-ink-700 hover:border-primary-300 hover:text-primary-800">
                                    #{{ $t->nama }}
                                </a>
                            @endforeach
                        </div>
                    @endif

                    {{-- Actions: like + share --}}
                    <div class="mt-6 flex flex-wrap items-center gap-2 border-t border-kertas-300 pt-5">
                        @auth
                            <form method="POST" action="{{ route('berita.like', $berita) }}">
                                @csrf
                                <button type="submit" class="inline-flex min-h-9 items-center gap-1.5 rounded-md border border-kertas-300 px-3 py-1.5 text-sm font-medium text-ink-800 hover:border-primary-300 hover:text-primary-800">
                                    <i class="fa-{{ $liked ? 'solid' : 'regular' }} fa-heart {{ $liked ? 'text-red-500' : '' }}"></i>
                                    {{ $liked ? 'Batal suka' : 'Suka' }} ({{ $berita->likes()->count() }})
                                </button>
                            </form>
                        @else
                            <span class="inline-flex min-h-9 items-center gap-1.5 text-sm text-ink-600">
                                <i class="fa-regular fa-heart"></i> {{ $berita->likes()->count() }}
                            </span>
                        @endauth

                        <a href="https://wa.me/?text={{ urlencode($berita->judul . ' ' . route('berita.show', $berita)) }}" target="_blank" rel="noopener"
                           class="inline-flex min-h-9 items-center gap-1.5 rounded-md bg-green-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-green-700">
                            <i class="fa-brands fa-whatsapp"></i> WA
                        </a>
                        <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(route('berita.show', $berita)) }}" target="_blank" rel="noopener"
                           class="inline-flex min-h-9 items-center gap-1.5 rounded-md bg-blue-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-blue-700">
                            <i class="fa-brands fa-facebook-f"></i>
                        </a>
                        <a href="https://twitter.com/intent/tweet?text={{ urlencode($berita->judul) }}&url={{ urlencode(route('berita.show', $berita)) }}" target="_blank" rel="noopener"
                           class="inline-flex min-h-9 items-center gap-1.5 rounded-md bg-sky-500 px-3 py-1.5 text-sm font-medium text-white hover:bg-sky-600">
                            <i class="fa-brands fa-x-twitter"></i>
                        </a>
                        <button type="button" onclick="navigator.clipboard.writeText('{{ route('berita.show', $berita) }}'); this.querySelector('span').textContent='Tersalin!';"
                                class="inline-flex min-h-9 items-center gap-1.5 rounded-md border border-kertas-300 px-3 py-1.5 text-sm font-medium text-ink-800 hover:border-primary-300">
                            <i class="fa-solid fa-link"></i> <span>Salin</span>
                        </button>
                    </div>

                    {{-- Comments --}}
                    <section class="mt-8">
                        <h2 class="font-display text-lg font-extrabold text-ink-900">
                            Komentar ({{ $komentars->count() }})
                        </h2>

                        @if (session('success'))
                            <div class="mt-3 rounded-md border border-green-200 bg-green-50 px-3 py-2 text-sm text-green-800">
                                <i class="fa-solid fa-check mr-1"></i> {{ session('success') }}
                            </div>
                        @endif

                        @if ($komentars->isEmpty())
                            <p class="mt-3 text-sm text-ink-600">Belum ada komentar. Jadilah yang pertama!</p>
                        @else
                            <ul class="mt-4 space-y-3">
                                @foreach ($komentars as $komentar)
                                    <li class="rounded-lg border border-kertas-300 bg-white p-3">
                                        <div class="flex items-start gap-3">
                                            <div class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-primary-100 font-display text-xs font-extrabold text-primary-800">
                                                {{ strtoupper(mb_substr($komentar->nama, 0, 1)) }}
                                            </div>
                                            <div class="min-w-0 flex-1">
                                                <div class="flex flex-wrap items-baseline justify-between gap-1">
                                                    <span class="text-sm font-semibold text-ink-900">{{ $komentar->nama }}</span>
                                                    <time class="text-xs text-ink-400">{{ $komentar->created_at->diffForHumans() }}</time>
                                                </div>
                                                <p class="mt-1 text-sm leading-relaxed text-ink-700">{{ $komentar->isi }}</p>
                                            </div>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        @endif

                        {{-- Comment form --}}
                        <div class="mt-6 rounded-lg border border-kertas-300 bg-kertas-200 p-4">
                            <h3 class="font-display text-sm font-extrabold text-ink-900">Tulis Komentar</h3>
                            <form method="POST" action="{{ route('berita.komentar.store', $berita) }}" class="mt-3 space-y-2">
                                @csrf
                                <input type="text" name="website" class="hidden" tabindex="-1" autocomplete="off">
                                @guest
                                    <div class="grid gap-2 sm:grid-cols-2">
                                        <input type="text" name="nama" value="{{ old('nama') }}" placeholder="Nama" required
                                               class="min-h-9 rounded-md border border-kertas-300 bg-white px-3 py-1.5 text-sm focus:border-primary-500 focus:outline-none">
                                        <input type="email" name="email" value="{{ old('email') }}" placeholder="Email" required
                                               class="min-h-9 rounded-md border border-kertas-300 bg-white px-3 py-1.5 text-sm focus:border-primary-500 focus:outline-none">
                                    </div>
                                @endguest
                                <textarea name="isi" required rows="3" placeholder="Tulis komentar..."
                                          class="w-full rounded-md border border-kertas-300 bg-white px-3 py-2 text-sm focus:border-primary-500 focus:outline-none">{{ old('isi') }}</textarea>
                                @error('isi')<p class="text-sm text-red-700">{{ $message }}</p>@enderror
                                <div class="flex items-center justify-between">
                                    <p class="text-xs text-ink-500">Komentar tampil setelah disetujui.</p>
                                    <button type="submit" class="inline-flex min-h-9 items-center rounded-md bg-primary-800 px-4 text-sm font-medium text-white hover:bg-primary-900">
                                        Kirim
                                    </button>
                                </div>
                            </form>
                        </div>
                    </section>
                </div>

                {{-- Sidebar --}}
                <aside class="space-y-5 lg:sticky lg:top-24 lg:self-start">
                    @if($berita->kategori)
                        <div class="rounded-xl border border-kertas-300 bg-white p-4">
                            <h3 class="font-display text-sm font-extrabold uppercase tracking-wide text-ink-500">Kategori</h3>
                            <a href="{{ route('berita.index', ['kategori' => $berita->kategori->slug]) }}"
                               class="mt-2 inline-flex items-center gap-2 rounded-full bg-primary-50 px-3 py-1.5 text-sm font-semibold text-primary-800 hover:bg-primary-100">
                                {{ $berita->kategori->nama }}
                            </a>
                        </div>
                    @endif

                    @if($berita->tags->isNotEmpty())
                        <div class="rounded-xl border border-kertas-300 bg-white p-4">
                            <h3 class="font-display text-sm font-extrabold uppercase tracking-wide text-ink-500">Tag</h3>
                            <div class="mt-2 flex flex-wrap gap-1.5">
                                @foreach($berita->tags as $t)
                                    <a href="{{ route('berita.index', ['tag' => $t->slug]) }}" class="rounded-full border border-kertas-300 px-2.5 py-1 text-xs text-ink-700 hover:border-primary-300 hover:text-primary-800">
                                        #{{ $t->nama }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if($related->isNotEmpty())
                        <div class="rounded-xl border border-kertas-300 bg-white p-4">
                            <h3 class="font-display text-sm font-extrabold uppercase tracking-wide text-ink-500">Terkait</h3>
                            <ul class="mt-2 space-y-3">
                                @foreach($related as $r)
                                    <li>
                                        <a href="{{ route('berita.show', $r) }}" class="group block">
                                            <span class="text-sm font-medium text-ink-800 group-hover:text-primary-800 leading-snug">{{ \Illuminate\Support\Str::limit($r->judul, 65) }}</span>
                                            <span class="mt-0.5 block text-xs text-ink-400">
                                                @if($r->published_at){{ $r->published_at->translatedFormat('j M Y') }}@endif
                                            </span>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="rounded-xl border border-kertas-300 bg-white p-4">
                        <h3 class="font-display text-sm font-extrabold uppercase tracking-wide text-ink-500">Bagikan</h3>
                        <div class="mt-2 flex flex-wrap gap-2">
                            <a href="https://wa.me/?text={{ urlencode($berita->judul . ' ' . route('berita.show', $berita)) }}" target="_blank" rel="noopener"
                               class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-green-600 text-white hover:bg-green-700" title="WhatsApp">
                                <i class="fa-brands fa-whatsapp"></i>
                            </a>
                            <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(route('berita.show', $berita)) }}" target="_blank" rel="noopener"
                               class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-blue-600 text-white hover:bg-blue-700" title="Facebook">
                                <i class="fa-brands fa-facebook-f"></i>
                            </a>
                            <a href="https://twitter.com/intent/tweet?text={{ urlencode($berita->judul) }}&url={{ urlencode(route('berita.show', $berita)) }}" target="_blank" rel="noopener"
                               class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-sky-500 text-white hover:bg-sky-600" title="Twitter/X">
                                <i class="fa-brands fa-x-twitter"></i>
                            </a>
                            <button type="button" onclick="navigator.clipboard.writeText('{{ route('berita.show', $berita) }}'); this.querySelector('span').textContent='✓';"
                                    class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-kertas-300 text-ink-700 hover:border-primary-300" title="Salin">
                                <i class="fa-solid fa-link"></i>
                            </button>
                        </div>
                    </div>
                </aside>
            </div>
        </div>
    </article>

    @include('spmb.partials.footer')
</main>

@push('scripts')
<script>
(function () {
    const url = '{{ route("berita.read", $berita) }}';
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    let seconds = 0;
    let sent = 0;
    const tick = () => { if (document.visibilityState === 'visible') seconds++; };
    setInterval(tick, 1000);
    const send = (final) => {
        if (seconds <= sent) return;
        sent = seconds;
        const body = new URLSearchParams({ duration: seconds });
        if (navigator.sendBeacon) {
            navigator.sendBeacon(url, new Blob([body.toString()], { type: 'application/x-www-form-urlencoded' }));
        } else {
            fetch(url, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf, 'Content-Type': 'application/x-www-form-urlencoded' }, body: body.toString(), keepalive: true }).catch(() => {});
        }
    };
    document.addEventListener('visibilitychange', () => { if (document.visibilityState === 'hidden') send(true); });
    window.addEventListener('pagehide', () => send(true));
    setInterval(() => send(false), 15000);
})();
</script>
@endpush
@endsection