@extends('layouts.spmb')

@section('title', ($pengumuman->judul ?? 'Pengumuman') . ' — SPMB')

@php
    // TODO: $pengumuman normalnya datang dari controller (PengumumanController@show).
    // Fallback dummy ini hanya untuk keperluan desain, hapus setelah controller-nya siap.
    $pengumuman = $pengumuman ?? (object) [
        'judul' => 'Pengumuman Hasil Seleksi Gelombang 1',
        'tanggal_pengumuman' => now(),
        'isi' => "Selamat kepada seluruh calon siswa yang telah dinyatakan lulus seleksi gelombang 1.\n\nSilakan melakukan daftar ulang paling lambat tanggal 31 Desember 2026 dengan membawa berkas asli sesuai persyaratan.\n\nBagi yang belum lulus, jangan berkecil hati — pendaftaran gelombang 2 masih dibuka.",
        'tahunAjaran' => (object) ['nama_tahun_ajaran' => date('Y') . '/' . (date('Y') + 1)],
    ];

    $icons = [
        'arrow-left' => 'M10 19l-7-7m0 0l7-7m-7 7h18',
        'calendar' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
        'megaphone' => 'M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z',
        'share' => 'M8.684 13.342a3 3 0 110-2.684m0 2.684a3 3 0 100-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z',
        'printer' => 'M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4H7v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z',
    ];

    $tanggal = $pengumuman->tanggal_pengumuman instanceof \Carbon\Carbon
        ? $pengumuman->tanggal_pengumuman
        : \Carbon\Carbon::parse($pengumuman->tanggal_pengumuman);
@endphp

@section('content')
    @include('spmb.partials.navbar')

    <section class="relative pt-32 pb-20 bg-slate-50 overflow-hidden">
        <div class="absolute top-0 right-0 w-96 h-96 bg-primary-50 rounded-full blur-3xl -z-0"></div>

        <div class="relative container mx-auto px-4 max-w-3xl">
            <a href="{{ route('spmb.pengumuman.index') }}"
               class="inline-flex items-center text-sm font-medium text-gray-600 hover:text-primary-600 transition-colors mb-6 group">
                <svg class="w-4 h-4 mr-2 transition-transform group-hover:-translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $icons['arrow-left'] }}"/>
                </svg>
                Kembali ke Pengumuman
            </a>

            <div class="bg-white rounded-3xl shadow-xl p-6 md:p-10 border border-gray-100">
                {{-- Badge + meta --}}
                <div class="flex flex-wrap items-center gap-3 mb-6">
                    <span class="inline-flex items-center px-3 py-1.5 bg-primary-100 text-primary-700 rounded-full text-xs font-semibold">
                        <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $icons['megaphone'] }}"/>
                        </svg>
                        Pengumuman
                    </span>
                    <span class="inline-flex items-center text-sm text-gray-500">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $icons['calendar'] }}"/>
                        </svg>
                        {{ $tanggal->translatedFormat('d F Y') }}
                    </span>
                    @if (!empty($pengumuman->tahunAjaran->nama_tahun_ajaran ?? null))
                        <span class="text-sm text-gray-400">•</span>
                        <span class="text-sm text-gray-500">TA {{ $pengumuman->tahunAjaran->nama_tahun_ajaran }}</span>
                    @endif
                </div>

                <h1 class="text-2xl md:text-4xl font-extrabold text-gray-800 mb-8 leading-tight">
                    {{ $pengumuman->judul }}
                </h1>

                {{-- Konten --}}
                <div class="border-l-4 border-primary-500 pl-6 py-1">
                    <div class="text-gray-700 leading-relaxed text-base md:text-lg space-y-4" style="white-space: pre-line;">{{ $pengumuman->isi }}</div>
                </div>

                {{-- Actions --}}
                <div class="flex flex-wrap gap-3 mt-10 pt-6 border-t border-gray-100">
                    <a href="https://wa.me/?text={{ urlencode($pengumuman->judul . ' - ' . request()->url()) }}"
                       target="_blank" rel="noopener"
                       class="inline-flex items-center px-4 py-2.5 bg-accent-50 text-accent-700 rounded-xl text-sm font-semibold hover:bg-accent-100 transition">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $icons['share'] }}"/>
                        </svg>
                        Bagikan
                    </a>
                    <button type="button" onclick="window.print()"
                            class="inline-flex items-center px-4 py-2.5 bg-gray-50 text-gray-700 rounded-xl text-sm font-semibold hover:bg-gray-100 transition">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $icons['printer'] }}"/>
                        </svg>
                        Cetak
                    </button>
                </div>
            </div>

            <div class="text-center mt-10">
                <a href="{{ route('spmb.pengumuman.index') }}"
                   class="inline-flex items-center px-6 py-3 bg-primary-600 text-white rounded-xl font-semibold hover:bg-primary-700 transition shadow-md hover:shadow-lg">
                    Lihat Pengumuman Lainnya
                </a>
            </div>
        </div>
    </section>

    @include('spmb.partials.footer')
@endsection
