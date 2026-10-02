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
                // Tubuh teks. Sudah self-hosted sebelumnya tapi halaman publik
                // masih memuat Poppins dari Google Fonts - Poppins adalah salah
                // satu font yang paling sering dipakai template landing page
                // sekolah di Indonesia, jadi sangat terasa sebagai "template".
                bunny('Instrument Sans', {
                    weights: [400, 500, 600],
                }),
                // Judul. Serif untuk display memberi kesan lembaga yang sudah
                // mapan, bukan halaman produk/startup. Dipasangkan dengan
                // Instrument Sans karena keduanya dari foundry yang sama.
                bunny('Instrument Serif', {
                    weights: [400],
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
