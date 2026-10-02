<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Sistem Penerimaan Murid Baru (SPMB) - Daftar sekarang dan bergabung bersama kami!">
    <title>{{ $title ?? 'SPMB - Penerimaan Murid Baru' }}</title>

    {{-- Font (Instrument Sans + Instrument Serif) sudah di-self-host lewat
         plugin Vite, jadi halaman ini tidak lagi memanggil Google Fonts.
         Script Heroicons juga dihapus: tidak ada view yang memakainya,
         semua ikon ditulis inline sebagai <path>. --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-paper text-ink">

    @yield('content')

    {{-- Smooth scroll behavior --}}
    <script>
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                const href = this.getAttribute('href');

                // Tautan yang isinya hanya tanda pagar bukan anchor yang valid:
                // querySelector akan melempar DOMException. Abaikan saja daripada
                // membatalkan navigasi lalu menghasilkan error di console.
                if (!href || href === '#' || href.length < 2) {
                    return;
                }

                const target = document.querySelector(href);
                if (!target) {
                    return;
                }

                e.preventDefault();
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            });
        });
    </script>
</body>
</html>
