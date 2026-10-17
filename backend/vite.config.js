import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
            fonts: [
                // Dua keluarga, satu tugas masing-masing: Nunito untuk judul,
                // Poppins untuk badan teks. Disimpan lewat Bunny, bukan CDN
                // Google di <head>, supaya tidak ada request pihak ketiga yang
                // menahan render di jaringan sekolah.
                //
                // `preload` dibatasi ke bobot yang benar-benar dipakai di
                // layar pertama. Preload semua woff2 akan memblokir paint
                // untuk bobot yang baru muncul jauh di bawah lipatan.
                bunny('Nunito', {
                    weights: [600, 700, 800, 900],
                    preload: [{ weight: 800 }, { weight: 900 }],
                }),
                bunny('Poppins', {
                    weights: [400, 500, 600],
                    preload: [{ weight: 400 }],
                }),
            ],
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
