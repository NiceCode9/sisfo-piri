{{--
    Lightbox bersama untuk Galeri dan Berkas Unduhan.

    Kenapa satu dialog, bukan satu per section: galeri dan unduhan butuh
    interaksi yang sama, dan dua salinan berarti dua tempat untuk kesalahan.
    Section Unduhan bisa hilang kalau belum ada brosur, sedangkan Galeri
    selalu ada; kalau lightbox-nya ikut hilang bersama section itu, tombol
    galeri jadi tidak melakukan apa-apa.

    Dialog sungguhan: punya role dan aria-modal, bisa ditutup dengan Escape,
    mengembalikan fokus ke tombol pemicunya, dan mengunci gulir halaman selama
    terbuka. Tanpa pengembalian fokus, pengguna keyboard akan tersesat di
    dalam halaman tanpa tahu harus ke mana (R-32).
--}}

<div id="preview-lightbox"
     class="fixed inset-0 z-[100] hidden items-center justify-center bg-primary-900/95 p-4"
     style="overscroll-behavior: contain"
     role="dialog" aria-modal="true" aria-labelledby="preview-title">
    <button type="button" id="preview-close" aria-label="Tutup tampilan gambar"
            class="absolute right-4 top-4 grid h-11 w-11 place-items-center rounded-md text-white transition-colors hover:bg-white/10">
        <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
        </svg>
    </button>

    <div class="flex max-h-full w-full max-w-4xl flex-col items-center gap-4">
        {{-- `src` sengaja tidak ditulis di markup. `src=""` membuat sebagian
             peramban meminta ulang halaman ini sendiri sebagai gambar, jadi
             berkedip sekilas sebelum JS mengisinya. --}}
        <img id="preview-image" alt="" width="1200" height="800"
             class="max-h-[70vh] w-auto rounded-md object-contain">
        <div class="flex flex-wrap items-center justify-center gap-4">
            <span id="preview-title" class="text-center font-semibold text-secondary-100"></span>
            <a id="preview-download" href="" download class="btn bg-white text-primary-900 hover:bg-kertas-200">
                Unduh Gambar
            </a>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const lightbox = document.getElementById('preview-lightbox');
        if (!lightbox) return;

        const previewImage = document.getElementById('preview-image');
        const previewTitle = document.getElementById('preview-title');
        const downloadLink = document.getElementById('preview-download');
        const closeBtn = document.getElementById('preview-close');

        // Disimpan supaya fokus bisa dikembalikan ke tombol yang membuka.
        let pemicu = null;

        // Elemen yang bisa difokus di dalam dialog. `aria-modal` hanya
        // memberi tahu kepada pembaca layar bahwa ini modal; ia tidak
        // benar-benar mengunci Tab. Tanpa daftar ini dan penguncian di
        // keydown, pengguna keyboard bisa menekan Tab berulang kali dan
        // berakhir di tombol di belakang dialog yang tidak terlihat.
        const FOKUSABLE = 'button:not([disabled]), a[href], [tabindex]:not([tabindex="-1"])';

        function fokusDiDalamDialog() {
            return Array.from(lightbox.querySelectorAll(FOKUSABLE))
                .filter((el) => el.offsetParent !== null);
        }

        // Kunci gulir di belakang dialog sekaligus cegah scroll chaining:
        // di layar sentuh, drag di atas dialog yang sudah penuh biasanya
        // ikut menarik halaman di belakangnya.
        let overflowLama = '';

        function closePreview() {
            if (lightbox.classList.contains('hidden')) return;
            lightbox.classList.add('hidden');
            lightbox.classList.remove('flex');
            document.body.style.overflow = overflowLama;
            if (pemicu) pemicu.focus();
        }

        function openPreview(pembuka) {
            pemicu = pembuka;
            previewImage.src = pembuka.dataset.image;
            previewImage.alt = pembuka.dataset.title || '';
            previewTitle.textContent = pembuka.dataset.title || '';
            downloadLink.href = pembuka.dataset.image;

            // Hanya tombol unduh yang punya aksi. Menampilkan tombol unduh di
            // galeri sekolah (foto kegiatan, bukan dokumen) cuma menambah
            // kontrol yang tidak ada gunanya.
            downloadLink.classList.toggle('hidden', pembuka.dataset.downloadable !== 'true');

            lightbox.classList.remove('hidden');
            lightbox.classList.add('flex');
            overflowLama = document.body.style.overflow;
            document.body.style.overflow = 'hidden';
            closeBtn.focus();
        }

        // Delegasi, jadi tombol yang ditambahkan setelah DOMContentLoaded
        // tetap tertangkap.
        document.addEventListener('click', function (event) {
            const pembuka = event.target.closest('.js-open-preview');
            if (pembuka) openPreview(pembuka);
        });

        closeBtn.addEventListener('click', closePreview);

        // Klik pada area kosong di luar gambar yang menutup, klik pada
        // gambarnya sendiri tidak.
        lightbox.addEventListener('click', function (event) {
            if (event.target === lightbox) closePreview();
        });

        document.addEventListener('keydown', function (event) {
            if (lightbox.classList.contains('hidden')) return;

            if (event.key === 'Escape') {
                closePreview();

                return;
            }

            // Focus trap. Tab dipantulkan di dalam dialog, Shift+Tab dipantulkan
            // ke arah sebaliknya. Tanpa ini, satu Tab dari tombol terakhir
            // membuat fokus hilang ke navbar di belakang overlay.
            if (event.key !== 'Tab') return;

            const bisaFokus = fokusDiDalamDialog();
            if (!bisaFokus.length) return;

            const pertama = bisaFokus[0];
            const terakhir = bisaFokus[bisaFokus.length - 1];

            if (event.shiftKey && document.activeElement === pertama) {
                event.preventDefault();
                terakhir.focus();
            } else if (!event.shiftKey && document.activeElement === terakhir) {
                event.preventDefault();
                pertama.focus();
            }
        });
    });
</script>