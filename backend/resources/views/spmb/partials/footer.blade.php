@php
    // $profileSekolah dishare otomatis via View composer (spmb.*); fallback bila baris profil belum ada.
    $schoolName = $profileSekolah->nama_sekolah ?? 'SMP Harapan Bangsa';
    $schoolLogo = ! empty($profileSekolah->logo_path)
        ? Storage::disk('public')->url($profileSekolah->logo_path)
        : 'https://via.placeholder.com/100x100?text=Logo';
    $schoolAlamat = $profileSekolah->alamat ?? null;
    $schoolTelp = $profileSekolah->telp ?? '(022) 1234-5678';
    $schoolEmail = $profileSekolah->email ?? 'spmb@smpharapanbangsa.sch.id';
    $currentYear = date('Y');
@endphp

<footer class="bg-gradient-to-br from-slate-800 to-slate-900 text-white">
    
    {{-- Main Footer --}}
    <div class="container mx-auto px-4 py-16">
        <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-8">
            
            {{-- Column 1: Logo & Description --}}
            <div class="lg:col-span-1">
                <div class="flex items-center space-x-3 mb-6">
                    <img src="{{ $schoolLogo }}" alt="Logo {{ $schoolName }}" class="h-16 w-16 object-contain">
                    <div>
                        <h3 class="text-xl font-bold">{{ $schoolName }}</h3>
                        <p class="text-sm text-slate-400">Penerimaan Murid Baru</p>
                    </div>
                </div>
                <p class="text-sm text-slate-400 leading-relaxed mb-6">
                    Membentuk generasi cerdas, berkarakter, dan berprestasi untuk masa depan Indonesia yang gemilang.
                </p>
                {{-- Ikon media sosial (Facebook/Instagram/YouTube/Twitter) sengaja
                     disembunyikan sampai pihak sekolah menyediakan URL resmi. Sempat
                     memakai href="#" sehingga tidak bisa diklik sama sekali dan
                     membuat handler smooth-scroll melempar DOMException. --}}
            </div>

            {{-- Column 2: Quick Links --}}
            <div>
                <h4 class="text-lg font-bold mb-6">Menu Cepat</h4>
                <ul class="space-y-3">
                    <li>
                        <a href="#beranda" class="text-slate-400 hover:text-white transition flex items-center group">
                            <svg class="w-4 h-4 mr-2 text-primary-500 group-hover:translate-x-1 transition" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd"/>
                            </svg>
                            Beranda
                        </a>
                    </li>
                    <li>
                        <a href="#tentang" class="text-slate-400 hover:text-white transition flex items-center group">
                            <svg class="w-4 h-4 mr-2 text-primary-500 group-hover:translate-x-1 transition" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd"/>
                            </svg>
                            Tentang SPMB
                        </a>
                    </li>
                    <li>
                        <a href="#jalur" class="text-slate-400 hover:text-white transition flex items-center group">
                            <svg class="w-4 h-4 mr-2 text-primary-500 group-hover:translate-x-1 transition" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd"/>
                            </svg>
                            Jalur Pendaftaran
                        </a>
                    </li>
                    <li>
                        <a href="#alur" class="text-slate-400 hover:text-white transition flex items-center group">
                            <svg class="w-4 h-4 mr-2 text-primary-500 group-hover:translate-x-1 transition" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd"/>
                            </svg>
                            Alur Pendaftaran
                        </a>
                    </li>
                    <li>
                        <a href="#biaya" class="text-slate-400 hover:text-white transition flex items-center group">
                            <svg class="w-4 h-4 mr-2 text-primary-500 group-hover:translate-x-1 transition" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd"/>
                            </svg>
                            Biaya Pendidikan
                        </a>
                    </li>
                    <li>
                        <a href="#kontak" class="text-slate-400 hover:text-white transition flex items-center group">
                            <svg class="w-4 h-4 mr-2 text-primary-500 group-hover:translate-x-1 transition" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd"/>
                            </svg>
                            Kontak
                        </a>
                    </li>
                </ul>
            </div>

            {{-- Column 3: Informasi --}}
            <div>
                <h4 class="text-lg font-bold mb-6">Informasi</h4>
                <ul class="space-y-3">
                    <li>
                        <a href="{{ route('spmb.about') }}" class="text-slate-400 hover:text-white transition flex items-center group">
                            <svg class="w-4 h-4 mr-2 text-secondary-500 group-hover:translate-x-1 transition" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd"/>
                            </svg>
                            Profil Sekolah
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('spmb.about') }}#visi-misi" class="text-slate-400 hover:text-white transition flex items-center group">
                            <svg class="w-4 h-4 mr-2 text-secondary-500 group-hover:translate-x-1 transition" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd"/>
                            </svg>
                            Visi & Misi
                        </a>
                    </li>
                    <li>
                        <a href="#hasil" class="text-slate-400 hover:text-white transition flex items-center group">
                            <svg class="w-4 h-4 mr-2 text-secondary-500 group-hover:translate-x-1 transition" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd"/>
                            </svg>
                            Hasil Seleksi
                        </a>
                    </li>
                    {{-- FAQ, Syarat & Ketentuan, dan Kebijakan Privasi disembunyikan:
                         halamannya belum ada di route sehingga tautannya tidak punya tujuan. --}}
                </ul>
            </div>

            {{-- Column 4: Kontak Info --}}
            <div>
                <h4 class="text-lg font-bold mb-6">Kontak Kami</h4>
                <ul class="space-y-4">
                    <li class="flex items-start">
                        <svg class="w-5 h-5 text-accent-500 mr-3 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <span class="text-slate-400 text-sm">
                            {!! nl2br(e($schoolAlamat ?? "Jl. Pendidikan No. 123\nKota Bandung, Jawa Barat 40123")) !!}
                        </span>
                    </li>
                    <li class="flex items-start">
                        <svg class="w-5 h-5 text-accent-500 mr-3 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                        </svg>
                        <div class="text-slate-400 text-sm">
                            <div>{{ $schoolTelp }}</div>
                            <a href="https://wa.me/6281234567890" class="text-accent-400 hover:text-accent-300 transition">0812-3456-7890 (WA)</a>
                        </div>
                    </li>
                    <li class="flex items-start">
                        <svg class="w-5 h-5 text-accent-500 mr-3 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        <a href="mailto:{{ $schoolEmail }}" class="text-slate-400 hover:text-white transition text-sm">
                            {{ $schoolEmail }}
                        </a>
                    </li>
                </ul>
            </div>

        </div>
    </div>

    {{-- Bottom Footer --}}
    <div class="border-t border-slate-700">
        <div class="container mx-auto px-4 py-6">
            <div class="flex flex-col md:flex-row justify-between items-center space-y-4 md:space-y-0">
                <div class="text-sm text-slate-400 text-center md:text-left">
                    &copy; {{ $currentYear }} {{ $schoolName }}. All rights reserved.
                </div>
                <div class="flex items-center space-x-6 text-sm text-slate-400">
                    <span>Made with ❤️ by Tim IT {{ $schoolName }}</span>
                </div>
            </div>
        </div>
    </div>

</footer>
