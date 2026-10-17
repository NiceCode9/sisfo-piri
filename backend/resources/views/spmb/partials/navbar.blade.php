@php
    // $profileSekolah dishare otomatis via View composer (spmb.*); fallback
    // bila baris profil belum pernah diisi.
    $schoolName = $namaSekolah ?? 'Sekolah';
    $schoolLogo = ! empty($profileSekolah?->logo_path)
        ? Storage::disk('public')->url($profileSekolah->logo_path)
        : null;

    // Awal (huruf pertama) dipakai sebagai penanda kalau logo belum diunggah.
    // Placeholder yang jujur lebih baik daripada kotak abu-abu dari layanan
    // pihak ketiga yang tidak bisa dikontrol sekolah (R-23).
    $schoolInitial = mb_substr($schoolName, 0, 1);

    // Set true HANYA kalau section persis di bawah navbar adalah bidang
    // hijau tua. Di halaman tanpa hero gelap, navbar tetap solid sejak awal
    // supaya tidak pernah jadi teks putih di atas kertas.
    $transparent = $transparent ?? false;

    // Satu daftar untuk desktop dan mobile. Versi lama menyalin tautan
    // sendiri-sendiri ke dua tempat, dan tiga di antaranya sempat dikomentari
    // sampai tautan mati.
    $navLinks = [
        ['label' => 'Beranda', 'url' => route('spmb.home')],
        ['label' => 'Cara Daftar', 'url' => route('spmb.home') . '#alur'],
        ['label' => 'Biaya', 'url' => route('spmb.home') . '#biaya'],
        ['label' => 'Pengumuman', 'url' => route('spmb.pengumuman.index')],
        ['label' => 'Tentang', 'url' => route('spmb.about')],
    ];
@endphp

<nav id="navbar"
     data-transparent="{{ $transparent ? 'true' : 'false' }}"
     {{-- Navbar hanya satu-satunya elemen yang menempel di atas halaman, jadi
     hanya dia yang memakai bayangan. Kartu dan section tetap rata (R-12). --}}
     class="fixed top-0 inset-x-0 z-50 transition-colors duration-300
            {{ $transparent ? 'bg-transparent' : 'bg-kertas-100/95 backdrop-blur-sm shadow-float border-b border-kertas-300' }}"
     aria-label="Navigasi utama">

    <div class="mx-auto max-w-6xl px-5 sm:px-6">
        <div class="flex h-16 items-center justify-between gap-4">

            <a href="{{ route('spmb.home') }}"
               class="flex min-h-11 items-center gap-3 rounded-md">
                @if ($schoolLogo)
                    <img width="480" height="360" src="{{ $schoolLogo }}" alt="Logo {{ $schoolName }}"
                         class="h-10 w-10 shrink-0 object-contain">
                @else
                    <span aria-hidden="true"
                          class="grid h-10 w-10 shrink-0 place-items-center rounded-md bg-primary-700 font-display text-lg font-extrabold text-white">
                        {{ $schoolInitial }}
                    </span>
                @endif
                <span class="navbar-text hidden sm:block">
                    <span class="block font-display text-[0.95rem] font-extrabold leading-tight
                                 {{ $transparent ? 'text-white' : 'text-ink-900' }}">{{ $schoolName }}</span>
                    <span class="block text-[0.7rem] leading-tight
                                 {{ $transparent ? 'text-secondary-200' : 'text-ink-500' }}">Penerimaan Murid Baru</span>
                </span>
            </a>

            <ul class="hidden items-center gap-1 lg:flex">
                @foreach ($navLinks as $link)
                    <li>
                        <a href="{{ $link['url'] }}"
                           class="inline-flex min-h-11 items-center rounded-md px-3 text-sm font-medium transition-colors
                                  {{ $transparent ? 'text-white/85 hover:bg-white/10 hover:text-white' : 'text-ink-700 hover:bg-primary-50 hover:text-primary-800' }}">
                            {{ $link['label'] }}
                        </a>
                    </li>
                @endforeach
            </ul>

            <div class="hidden items-center gap-2 lg:flex">
                <a href="{{ route('login') }}"
                   class="btn btn-quiet">
                    Masuk
                </a>
                <a href="{{ route('spmb.pendaftaran') }}" class="btn btn-accent">
                    Mulai Pendaftaran
                </a>
            </div>

            <button id="hamburger"
                    type="button"
                    class="inline-flex min-h-11 items-center gap-2 rounded-md px-2 lg:hidden
                           {{ $transparent ? 'text-white' : 'text-ink-800' }}"
                    aria-label="Buka menu navigasi"
                    aria-expanded="false"
                    aria-controls="mobile-menu">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2"
                     stroke-linecap="round" viewBox="0 0 24 24" aria-hidden="true">
                    <path id="burger-line-top" d="M4 7h16"/>
                    <path id="burger-line-mid" d="M4 12h16"/>
                    <path id="burger-line-bot" d="M4 17h16"/>
                </svg>
                <span class="text-sm font-semibold">Menu</span>
            </button>
        </div>
    </div>

    <div id="mobile-menu"
         class="hidden border-b border-kertas-300 bg-kertas-100 lg:hidden"
         role="dialog"
         aria-label="Menu navigasi">
        <ul class="mx-auto max-w-6xl px-5 py-3 sm:px-6">
            @foreach ($navLinks as $link)
                <li>
                    <a href="{{ $link['url'] }}"
                       class="flex min-h-12 items-center rounded-md px-2 text-base font-medium text-ink-800 transition-colors hover:bg-primary-50">
                        {{ $link['label'] }}
                    </a>
                </li>
            @endforeach
        </ul>
        <div class="mx-auto max-w-6xl px-5 pb-5 sm:px-6">
            <div class="grid gap-2 border-t border-kertas-300 pt-4">
                <a href="{{ route('login') }}" class="btn btn-quiet w-full">Masuk</a>
                <a href="{{ route('spmb.pendaftaran') }}" class="btn btn-accent w-full">Mulai Pendaftaran</a>
            </div>
        </div>
    </div>
</nav>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const navbar = document.getElementById('navbar');
        const hamburger = document.getElementById('hamburger');
        const menu = document.getElementById('mobile-menu');
        const isTransparent = navbar.dataset.transparent === 'true';

        const paint = (solid) => {
            navbar.classList.toggle('bg-transparent', !solid);
            navbar.classList.toggle('bg-kertas-100/95', solid);
            navbar.classList.toggle('backdrop-blur-sm', solid);
            navbar.classList.toggle('shadow-float', solid);
            navbar.classList.toggle('border-b', solid);
            navbar.classList.toggle('border-kertas-300', solid);

            // Hanya navbar transparan yang perlu menukar warna; navbar solid
            // sudah gelap sejak awal.
            if (!isTransparent) return;

            const light = solid ? 'text-ink-900' : 'text-white';
            navbar.querySelectorAll('.navbar-text > span').forEach((el) => {
                el.classList.toggle('text-white', light === 'text-white');
                el.classList.toggle('text-ink-900', light === 'text-ink-900');
                el.classList.toggle('text-secondary-200', !solid);
                el.classList.toggle('text-ink-500', solid);
            });

            navbar.querySelectorAll('ul a').forEach((el) => {
                el.classList.toggle('text-white/85', !solid);
                el.classList.toggle('hover:bg-white/10', !solid);
                el.classList.toggle('hover:text-white', !solid);
                el.classList.toggle('text-ink-700', solid);
                el.classList.toggle('hover:bg-primary-50', solid);
                el.classList.toggle('hover:text-primary-800', solid);
            });

            const burger = hamburger.querySelector('span');
            burger.classList.toggle('text-white', !solid);
            burger.classList.toggle('text-ink-800', solid);
        };

        // Gambar ulang saat jendela dikecilkan lewat breakpoint, karena kelas
        // hamburger tidak ikut ditulis oleh paint().
        if (isTransparent) {
            const sync = () => {
                if (window.scrollY > 24 || window.innerWidth < 1024) paint(true);
                else paint(false);
            };
            window.addEventListener('scroll', sync, { passive: true });
            window.addEventListener('resize', sync);
            sync();
        }

        const setOpen = (open) => {
            menu.classList.toggle('hidden', !open);
            hamburger.setAttribute('aria-expanded', String(open));
            hamburger.setAttribute('aria-label', open ? 'Tutup menu navigasi' : 'Buka menu navigasi');

            // Tiga garis berubah jadi silang saat menu terbuka.
            document.getElementById('burger-line-top').setAttribute('d', open ? 'M6 6l12 12' : 'M4 7h16');
            document.getElementById('burger-line-mid').setAttribute('d', open ? 'M6 12h12' : 'M4 12h16');
            document.getElementById('burger-line-bot').setAttribute('d', open ? 'M6 18l-12 -12' : 'M4 17h16');

            if (open) {
                const first = menu.querySelector('a');
                if (first) first.focus();
            } else {
                hamburger.focus();
            }
        };

        hamburger.addEventListener('click', () => {
            setOpen(hamburger.getAttribute('aria-expanded') !== 'true');
        });

        // Escape menutup menu dan mengembalikan fokus ke tombolnya, supaya
        // pengguna keyboard tidak terjebak di dalam dialog.
        menu.addEventListener('keydown', (event) => {
            if (event.key !== 'Escape') return;
            setOpen(false);
        });

        document.addEventListener('keydown', (event) => {
            if (event.key !== 'Escape') return;
            if (hamburger.getAttribute('aria-expanded') !== 'true') return;
            setOpen(false);
        });

        menu.querySelectorAll('a').forEach((link) => {
            link.addEventListener('click', () => menu.classList.add('hidden'));
        });

        window.addEventListener('resize', () => {
            if (window.innerWidth >= 1024 && !menu.classList.contains('hidden')) {
                menu.classList.add('hidden');
                hamburger.setAttribute('aria-expanded', 'false');
            }
        });
    });
</script>
