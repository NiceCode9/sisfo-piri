{{--
    Kepala section - Editorial Heritage

    Sepuluh section di halaman publik sebelumnya memakai kerangka yang sama
    persis: pil berwarna, `<h2 class="text-3xl md:text-4xl font-extrabold">`,
    lalu satu baris teks abu-abu. Pengulangan itu membuat tidak ada section
    yang terasa disusun dengan sengaja.

    Partial ini menggantinya dengan satu pola editorial: nomor dua digit
    (`01`), garis aksen pendek, judul serif, dan sub-teks. Nomor itu membuat
    urutan section terbaca dan harus diisi manual - memang disengaja supaya
    menambah section baru terasa seperti keputusan, bukan sekadar copy-paste.

    Pakai:
        @include('spmb.partials.section-head', [
            'nomor' => '01',
            'judulAwal' => 'Biaya',
            'judulAksen' => 'Pendidikan',
            'sub' => 'Rincian biaya',
        ])
--}}

@php
    $align = $align ?? 'center';
    $sub = $sub ?? null;
@endphp

<div class="{{ $align === 'left' ? 'text-left max-w-2xl' : 'text-center max-w-3xl mx-auto' }} mb-14">
    @if (! empty($nomor))
        <div class="flex items-center {{ $align === 'left' ? '' : 'justify-center' }} gap-3 mb-4">
            <span class="rule-accent shrink-0"></span>
            <span class="font-display text-sm tracking-[0.2em] text-ink-muted">{{ $nomor }}</span>
        </div>
    @endif

    <h2 class="font-display text-display-md text-ink">
        {{ $judulAwal }}{!! isset($judulAksen) ? ' <span class="text-primary-700">' . $judulAksen . '</span>' : '' !!}
    </h2>

    @if ($sub)
        <p class="text-lg text-ink-muted mt-4 leading-relaxed">{!! $sub !!}</p>
    @endif
</div>
