{{--
    Peta lokasi sekolah.

    Dipakai di dua halaman: section Identitas Sekolah (Tentang) dan section
    Kontak (Beranda). Keduanya harus menampilkan peta yang persis sama, jadi
    ini partial, bukan markup yang disalin. Kalau peta ditulis dua kali,
    pasti akan ada saat keduanya mulai berbeda.

    Sumber peta selalu punya nilai, jadi blok ini tidak pernah kosong:

    - `maps_embed_url` yang diisi admin di panel administrasi, kalau ada.
    - `ProfilSekolah::PETA_CADANGAN`, peta cadangan yang dipusatkan ke Ngaglik,
      Sleman, bukan ke satu titik bangunan. Peta cadangan ini bukan pin yang
      dikarang.

    Null hanya kalau baris `profil_sekolahs` belum ada sama sekali, karena
    tanpa nama sekolah tidak ada yang bisa dipusatkan.

    PARAMETER
    - $tinggi  kelas tinggi iframe per breakpoint. Default netral.
--}}
@php
    $petaUrl = $profileSekolah?->petaEmbedUrl();
    $petaDariAdmin = filled($profileSekolah?->maps_embed_url);
    $tinggi = $tinggi ?? 'h-[280px] w-full sm:h-[360px]';
@endphp

@if ($petaUrl)
    <div class="reveal overflow-hidden rounded-lg border border-kertas-300">
        {{-- `title` wajib. Tanpa itu, pembaca layar membacakan URL peta yang
             panjang dan tidak berguna sebagai ganti nama tempat yang sedang
             dibuka. --}}
        <iframe src="{{ $petaUrl }}"
                title="Peta lokasi {{ $namaSekolah }}"
                class="{{ $tinggi }}"
                style="border: 0"
                loading="lazy"
                referrerpolicy="no-referrer-when-downgrade"
                allowfullscreen></iframe>
    </div>

    @unless ($petaDariAdmin)
        <p class="mt-3 text-sm leading-relaxed text-ink-500">
            Peta ini masih memakai patokan Ngaglik, Sleman, karena
            <span class="font-semibold">URL Embed Google Maps</span> di panel
            administrasi belum diisi. Begitu diisi, peta ini otomatis menunjuk
            ke lokasi yang sebenarnya.
        </p>
    @endunless
@else
    <div class="reveal rounded-lg border border-kertas-300 bg-kertas-100 p-8">
        <h3 class="font-display text-base font-extrabold text-ink-900">Peta lokasi</h3>
        <p class="mt-2 text-sm leading-relaxed text-ink-600">
            Profil sekolah belum dibuat di panel administrasi, jadi peta belum
            bisa dipasang.
        </p>
    </div>
@endif