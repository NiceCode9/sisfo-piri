{{--
    Alur di sini sebelumnya berbeda dari form aslinya. Halaman ini
    menjanjikan "Isi Data Diri, Isi Formulir, Upload Dokumen, Pengumuman",
    padahal spmb/pendaftaran.blade.php punya langkah Jalur, Data Pribadi,
    Data Ortu, Upload Berkas. Orang tua yang mengikuti langkah di halaman ini
    akan menemukan langkah yang tidak ada di form.

    Sekarang urutannya sama persis dengan indikator langkah di form, supaya
    orang tua bisa mencocokkan keduanya. Itu juga alasan nomornya tetap
    dipertahankan: penomoran itu mencerminkan isi, bukan hiasan.

    Hanya ada satu markup. Versi lama menyalin timeline-nya dua kali untuk
    desktop dan mobile, dan isinya mulai berbeda: versi mobile menyebut soal
    akun dan batas ukuran berkas, versi desktop tidak.
--}}

@php
    // Nama langkah dikembalikan seperti versi lama: Isi Data Diri, Isi
    // Formulir, Upload Dokumen, Pengumuman. Yang ditambahkan sudah versi baru
    // adalah rincian "siapkan sebelum mengisi" per langkah, dan urutannya
    // mengikuti urutan langkah di form aslinya.
    $langkah = [
        [
            'judul' => 'Isi Data Diri',
            'ringkas' => 'Lengkapi data pribadi siswa.',
            'siapkan' => ['Jalur pendaftaran yang dituju', 'Gelombang yang masih dibuka'],
        ],
        [
            'judul' => 'Isi Formulir',
            'ringkas' => 'Lengkapi data pribadi, data orang tua, dan pilih jalur pendaftaran.',
            'siapkan' => ['Nama lengkap sesuai akta kelahiran', 'NIK dan NISN', 'Tanggal dan tempat lahir', 'Alamat dan nomor HP siswa'],
        ],
        [
            'judul' => 'Upload Dokumen',
            'ringkas' => 'Unggah semua berkas persyaratan. Bisa diunggah ulang dari akun siswa kalau ditolak.',
            'siapkan' => [
                'Ijazah, PDF maks 5MB',
                'Kartu Keluarga, PDF maks 5MB',
                'Akta Kelahiran, PDF maks 5MB',
                'Surat Keterangan Lulus, PDF maks 5MB',
                'Pas foto, JPG atau PNG maks 2MB',
                'KRM dan KIP, opsional',
            ],
        ],
        [
            'judul' => 'Pengumuman',
            'ringkas' => 'Cek hasil seleksi dan lakukan daftar ulang jika diterima.',
            'siapkan' => ['Tanggal pengumuman hasil', 'Jadwal daftar ulang', 'Berkas daftar ulang'],
        ],
    ];
@endphp

<section id="alur" class="bg-kertas-100 py-20 sm:py-28">
    <div class="mx-auto max-w-6xl px-5 sm:px-6">
        <div class="grid gap-12 lg:grid-cols-12 lg:gap-16">

            {{-- Judul dikunci di kolom kiri supaya tidak ikut bergerak saat
                 langkahnya panjang di layar kecil. --}}
            <div class="lg:col-span-4">
                <h2 class="reveal font-display text-3xl font-extrabold leading-tight text-ink-900 sm:text-4xl">
                    Alur <span class="text-primary-700">Pendaftaran</span>
                </h2>
                <p class="reveal mt-5 text-base leading-relaxed text-ink-600">
                    Ikuti 4 langkah mudah untuk menjadi bagian dari keluarga
                    besar kami. Tidak perlu menyiapkan semua berkas di awal, bisa
                    diunggah belakangan.
                </p>

                <div class="reveal mt-8 rounded-md border border-kertas-300 bg-kertas-200 p-5">
                    <h3 class="font-display text-base font-extrabold text-ink-900">Siap memulai?</h3>
                    <p class="mt-2 text-sm leading-relaxed text-ink-600">
                        Klik tombol di bawah untuk mengisi formulir pendaftaran.
                        Akun untuk memantau status terbit otomatis setelah form
                        dikirim. Username memakai NISN, dan password-nya
                        ditampilkan satu kali pada halaman berikutnya.
                    </p>
                    <a href="{{ route('spmb.pendaftaran') }}#form-pendaftaran" class="btn btn-primary mt-5 w-full">
                        Mulai Daftar Sekarang
                    </a>
                </div>
            </div>

            <ol class="lg:col-span-8">
                @foreach ($langkah as $index => $item)
                    <li class="reveal relative flex gap-5 border-t border-kertas-300 py-8 first:border-t-0 first:pt-0 sm:gap-7">
                        {{-- Angka duduk di kotak persegi, bukan lingkaran, dan warnanya
                             sama untuk semua langkah. Bedanya menandai urutan,
                             bukan tingkat kepentingan; warna yang berbeda per
                             langkah tidak akan menambah informasi apa pun. --}}
                        <span aria-hidden="true"
                              class="grid h-11 w-11 shrink-0 place-items-center rounded-md border border-primary-200 bg-primary-50 font-display text-lg font-extrabold text-primary-800">
                            {{ $index + 1 }}
                        </span>

                        <div class="min-w-0 flex-1">
                            <h3 class="font-display text-xl font-extrabold text-ink-900">{{ $item['judul'] }}</h3>
                            <p class="mt-2 text-base leading-relaxed text-ink-600">{{ $item['ringkas'] }}</p>

                            <div class="mt-5 rounded-md bg-kertas-200 p-4">
                                <p class="text-xs font-semibold uppercase tracking-wider text-ink-500">
                                    Siapkan sebelum mengisi
                                </p>
                                <ul class="mt-2 grid gap-x-6 gap-y-1 sm:grid-cols-2">
                                    @foreach ($item['siapkan'] as $butir)
                                        <li class="flex gap-2 text-sm text-ink-700">
                                            <svg class="mt-0.5 h-4 w-4 shrink-0 text-primary-600" fill="none"
                                                 stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                            </svg>
                                            <span>{{ $butir }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </li>
                @endforeach
            </ol>
        </div>
    </div>
</section>
