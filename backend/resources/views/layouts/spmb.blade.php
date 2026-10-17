<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="@yield('meta_description', 'Pendaftaran Murid Baru ' . ($namaSekolah ?? '') . '. Lihat jalur pendaftaran, biaya, jadwal gelombang, dan cara mendaftar online.')">
    <title>{{ $title ?? ($namaSekolah ?? 'Sekolah') }}</title>

    {{-- Warna address bar dan notch harus sama dengan warna atas halaman,
         kalau tidak ada garis putih di antara web app dan browser (PWA
         safe area). Nilai ini mengikuti `bg-kertas-100` di app.css. --}}
    <meta name="theme-color" content="#f7f4ef">

    {{--
        Nunito untuk judul, Poppins untuk badan teks.
        Dipisah agar tiap huruf punya satu tugas: Nunito yang membawa karakter
        ramah anak pada ukuran besar, Poppins yang menjaga keterbacaan
        kalimat panjang berbahasa Indonesia.

        Dimuat sendiri lewat Bunny (lihat `fonts` di vite.config.js), bukan
        dari CDN Google, supaya tidak ada request pihak ketiga yang menahan
        render di jaringan sekolah. `@fonts` tidak bisa ikut @vite, jadi
        directive-nya didaftarkan di AppServiceProvider.
    --}}
    @fonts(['nunito', 'poppins'])

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-kertas-100 text-ink-800">

    <a href="#konten-utama"
       class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-[60] focus:rounded-md focus:bg-white focus:px-4 focus:py-3 focus:font-semibold focus:text-primary-800 focus:shadow-float">
        Lompat ke isi utama
    </a>

    @yield('content')

    @include('spmb.partials.footer')

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            /*
             * Angka besar yang dihitung naik dari nol.
             *
             * Fungsinya membuat satu perubahan dari 0 ke 300 terbaca sebagai
             * perubahan, bukan sebagai angka yang selalu ada. Yang membuatnya
             * tidak menyebalkan: durasi pendek, easing keluar, dan hanya
             * berjalan sekali.
             *
             * Tiga hal yang dijaga supaya tidak merusak halaman:
             *
             * - Tanpa JavaScript angkanya tetap utuh. Nilai akhir sudah ada di
             *   HTML sebagai isi elemen, jadi `textContent` hanya menulis ulang
             *   angka yang sama.
             * - `prefers-reduced-motion` mematikan animasi sepenuhnya, bukan
             *   mempercepatnya. Gerak selalu opsional; gerakan yang tidak
             *   diminta tidak perlu jadi lebih cepat.
             * - Angka diformat dengan `id-ID`, jadi 1234 tampil 1.234 sama
             *   seperti yang tertulis di teks di bawahnya.
             *
             * Lebar glyph dijaga oleh kelas `tabular` pada induknya: tanpa
             * itu, digit 9 ke digit 10 melebar dan seluruh baris melompat ke
             * samping selama animasi berjalan.
             */
            function pasangHitungNaik() {
                const targets = document.querySelectorAll('[data-hitung]');

                if (!targets.length) return;

                const kurangiGerak = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

                const format = new Intl.NumberFormat('id-ID');

                targets.forEach((el) => {
                    const akhir = Number(el.dataset.hitung);

                    if (!Number.isFinite(akhir) || akhir <= 0) return;

                    // Angka ditahan di nilai akhir kalau tidak ada observer atau
                    // pengguna minta tanpa gerak.
                    if (kurangiGerak || !('IntersectionObserver' in window)) return;

                    const durasi = 900;
                    let mulai = null;

                    const hitung = (waktu) => {
                        // `waktu` yang diberikan browser sudah relatif terhadap
                        // `performance.now()`, jadi ini titik awal yang benar.
                        if (mulai === null) mulai = waktu;

                        const progres = Math.min((waktu - mulai) / durasi, 1);

                        // Ease-out cubic: cepat di awal, melambat di akhir.
                        // Ease-in membuat angka terasa berat, dan linear membuat
                        // lonjakan di detik pertama.
                        const eased = 1 - Math.pow(1 - progres, 3);

                        el.textContent = format.format(Math.round(akhir * eased));

                        if (progres < 1) requestAnimationFrame(hitung);
                    };

                    const pemicu = new IntersectionObserver((entries) => {
                        entries.forEach((entry) => {
                            if (!entry.isIntersecting) return;
                            pemicu.unobserve(entry.target);
                            requestAnimationFrame(hitung);
                        });
                    }, { threshold: 0.4 });

                    pemicu.observe(el);
                });
            }

            // Anchor hanya dihalangi kalau targetnya benar-benar ada.
            // Versi lama memanggil querySelector tanpa mengecek, jadi tautan
            // ke section yang tidak ada di halaman ini melempar DOMException.
            // Footer ikut di semua halaman, jadi banyak anchor yang valid di
            // Beranda tapi mati di Tentang atau Pendaftaran.
            document.querySelectorAll('a[href^="#"]').forEach(function (anchor) {
                anchor.addEventListener('click', function (event) {
                    const href = this.getAttribute('href');
                    if (!href || href === '#' || href.length < 2) return;

                    const target = document.querySelector(href);
                    if (!target) return;

                    event.preventDefault();
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    // Fokus ikut pindah supaya pengguna keyboard tidak
                    // menggulir dengan mata saja lalu kehilangan tempatnya.
                    target.setAttribute('tabindex', '-1');
                    target.focus({ preventScroll: true });
                });
            });

            // MOTION 2: satu kali masuk viewport, tanpa delay bertingkat.
            // Elemen yang tidak pernah terlihat tetap terlihat, jadi konten
            // tidak hilang kalau IntersectionObserver tidak tersedia.
            //
            // Logikanya dibungkus fungsi sendiri, bukan `return` di tengah
            // handler: `return` pernah dipakai di sini dan membuat blok
            // setelahnya ikut terlewati di browser tanpa IntersectionObserver.
            function pasangReveal() {
                const targets = document.querySelectorAll('.reveal');

                if (!('IntersectionObserver' in window)) {
                    targets.forEach((el) => el.classList.add('is-visible'));
                    return;
                }

                const observer = new IntersectionObserver((entries) => {
                    entries.forEach((entry) => {
                        if (!entry.isIntersecting) return;
                        entry.target.classList.add('is-visible');
                        observer.unobserve(entry.target);
                    });
                }, { rootMargin: '0px 0px -12% 0px', threshold: 0.1 });

                targets.forEach((el) => observer.observe(el));
            }

            pasangReveal();
            pasangHitungNaik();
        });
    </script>
</body>
</html>
