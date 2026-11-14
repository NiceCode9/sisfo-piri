@extends('layouts.spmb')

@section('title', 'Kebijakan Privasi - ' . ($namaSekolah ?? 'Sekolah'))
@section('meta_description', 'Kebijakan privasi dan penggunaan data pada website ' . ($namaSekolah ?? 'Sekolah') . '.')

@section('content')
<main id="konten-utama">
    @include('spmb.partials.navbar')

    <article class="bg-kertas-100 pt-28 pb-20 sm:pt-32 sm:pb-24">
        <div class="mx-auto max-w-3xl px-5 sm:px-6">
            <h1 class="font-display text-3xl font-extrabold text-ink-900 sm:text-4xl">Kebijakan Privasi</h1>
            <p class="mt-2 text-sm text-ink-600">Terakhir diperbarui: {{ now()->translatedFormat('j F Y') }}</p>

            <div class="mt-8 space-y-6 text-base leading-relaxed text-ink-800">
                <section>
                    <h2 class="font-display text-xl font-bold text-ink-900">1. Data yang kami kumpulkan</h2>
                    <p class="mt-2">Saat Anda mengunjungi website ini, kami dapat mengumpulkan informasi berikut:</p>
                    <ul class="mt-2 list-disc pl-6">
                        <li><strong>Data teknis</strong>: alamat IP (disimpan dalam bentuk hash, tidak dapat dibaca ulang), jenis peramban, sistem operasi, dan halaman yang dikunjungi.</li>
                        <li><strong>Data sesi</strong>: identifikasi sesi peramban (cookie) untuk membedakan kunjungan.</li>
                        <li><strong>Data interaksi</strong>: pada halaman berita, kami mencatat durasi membaca (waktu tab aktif) untuk mengetahui popularitas konten.</li>
                        <li><strong>Data akun</strong>: jika Anda masuk sebagai pengguna (admin, guru, siswa, jurnalistik), data profil sesuai peran Anda.</li>
                        <li><strong>Google Analytics 4</strong>: jika diaktifkan, Google mengumpulkan data penggunaan agregat (pageview, durasi, perangkat) sesuai kebijakan Google.</li>
                    </ul>
                </section>

                <section>
                    <h2 class="font-display text-xl font-bold text-ink-900">2. Tujuan penggunaan data</h2>
                    <ul class="mt-2 list-disc pl-6">
                        <li>Menyediakan dan meningkatkan layanan website.</li>
                        <li>Menganalisis popularitas konten untuk redaksi (berita terpopuler, durasi baca).</li>
                        <li>Keamanan: mendeteksi penyalahgunaan dan bot.</li>
                        <li>Kepatuhan hukum dan audit internal.</li>
                    </ul>
                </section>

                <section>
                    <h2 class="font-display text-xl font-bold text-ink-900">3. Cookie</h2>
                    <p class="mt-2">Website ini menggunakan cookie sesi (untuk login dan keamanan CSRF) dan, jika Google Analytics aktif, cookie analitik dari Google. Anda dapat menonaktifkan cookie di pengaturan peramban, namun beberapa fitur (login) mungkin tidak berfungsi.</p>
                </section>

                <section>
                    <h2 class="font-display text-xl font-bold text-ink-900">4. Penyimpanan dan retensi</h2>
                    <p class="mt-2">Data kunjungan internal disimpan maksimal 90 hari, lalu dihapus otomatis. Data akun disimpan selama akun aktif. Anda dapat meminta penghapusan data dengan menghubungi administrator sekolah.</p>
                </section>

                <section>
                    <h2 class="font-display text-xl font-bold text-ink-900">5. Pihak ketiga</h2>
                    <p class="mt-2">Kami menggunakan Google Analytics (jika diaktifkan) yang tunduk pada <a href="https://policies.google.com/privacy" class="text-primary-800 underline" target="_blank" rel="noopener">Kebijakan Privasi Google</a>. Kami tidak menjual data pribadi Anda kepada pihak ketiga.</p>
                </section>

                <section>
                    <h2 class="font-display text-xl font-bold text-ink-900">6. Hak Anda</h2>
                    <p class="mt-2">Anda berhak mengetahui, memperbaiki, atau meminta penghapusan data pribadi Anda. Hubungi administrator sekolah melalui kontak yang tersedia.</p>
                </section>

                <section>
                    <h2 class="font-display text-xl font-bold text-ink-900">7. Kontak</h2>
                    <p class="mt-2">Pertanyaan terkait kebijakan privasi dapat disampaikan ke administrator {{ $namaSekolah ?? 'Sekolah' }}.</p>
                </section>
            </div>
        </div>
    </article>

    @include('spmb.partials.footer')
</main>
@endsection