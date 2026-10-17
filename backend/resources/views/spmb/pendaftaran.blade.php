@extends('layouts.spmb')

@section('title', 'Pendaftaran Siswa Baru')

@php
    // ============================================================
    // Fallback hanya untuk data yang belum ada di database. Tanggal
    // TIDAK boleh di-fallback dengan nilai hardcode: dulu controller
    // mengirim $jadwalPpdbs (jamak) sementara view membaca $jadwalPpdb
    // (tunggal), sehingga timeline selalu menampilkan tanggal palsu
    // Nov/Des 2026.
    //
    // Timeline sekarang dibangun dari Gelombang + tahapnya, bukan JadwalPPDB,
    // supaya tanggal yang tampil adalah satu-satunya tanggal yang dipakai gate
    // pendaftaran.
    //
    // Jalur juga TIDAK diberi fallback. Versi lama membuat dua jalur palsu
    // (Reguler dan Prestasi/Beasiswa) supaya dropdown tidak kosong; kalau
    // sekolah belum mengatur jalur, orang tua akan melihat pilihan yang tidak
    // bisa memakai, lalu gagal saat disimpan (R-38).
    $jalurPendaftarans = $jalurPendaftarans ?? collect();

    $semuaGelombangs = $semuaGelombangs ?? collect();

    // Timeline dikelompokkan per gelombang, bukan diratakan jadi satu daftar
    // tahap. Versi lama melakukan flatMap sehingga 3 gelombang x 5 tahap
    // menjadi 15 kartu bolak-balik setinggi ~3000px, dan nama gelombang hanya
    // muncul sebagai pil kecil di tiap kartu sehingga calon harus membaca
    // seluruh halaman untuk menemukan batch yang sedang dibuka.
    //
    // Sekarang tiap gelombang jadi satu blok <details>: header-nya selalu
    // terlihat (nama, status, jendela pendaftaran, strip tahap), detailnya
    // dibuka on demand.

    // Nomor kontak asal template ("(022) 1234-5678",
    // "spmb@smpharapanbangsa.sch.id") sudah dihapus: bukan milik sekolah ini.
    // Kontak diambil dari profil, dan disembunyikan bila belum diisi.
    $kontakTelp = $profileSekolah?->telp_bersih;
    $kontakTelpTel = $profileSekolah?->telp_tel;
    $kontakWhatsapp = $profileSekolah?->whatsapp;
    $kontakEmail = $profileSekolah?->email_bersih;

    // Ikon inline (pengganti Font Awesome, konsisten dengan section lain di site ini).
    $icons = [
        'calendar' => ['M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'],
        'clipboard' => ['M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4'],
        'document' => ['M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
        'bell' => ['M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9'],
        'check-circle' => ['M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
        'graduation-cap' => ['M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14zm-4 6v-7.5l4-2.222'],
        'bolt' => ['M13 10V3L4 14h7v7l9-11h-7z'],
        'info-circle' => ['M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
        'exclamation-triangle' => ['M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z'],
        'globe' => ['M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
        'book-open' => ['M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253'],
        'phone' => ['M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z'],
        'route' => ['M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7'],
        'user' => ['M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'],
        'users' => ['M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zM7 7a3 3 0 11-6 0 3 3 0 016 0z'],
        'id-card' => ['M3 10h18M7 15h1m4 0h1m-7 4h12a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z'],
        'location' => ['M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0zM15 11a3 3 0 11-6 0 3 3 0 016 0z'],
        'home' => ['M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
        'mobile' => ['M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z'],
        'envelope' => ['M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z'],
        'briefcase' => ['M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z'],
        'upload' => ['M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3-3m3 3v12'],
        'image' => ['M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14M14 8h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z'],
        'paper-plane' => ['M12 19l9 2-9-18-9 18 9-2zm0 0v-8'],
        'arrow-left' => ['M10 19l-7-7m0 0l7-7m-7 7h18'],
        'arrow-right' => ['M14 5l7 7m0 0l-7 7m7-7H3'],
        'sparkles' => ['M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-6.714 2.143L12 21l-2.286-6.857L3 12l6.714-2.143L12 3z'],
        'shield-check' => ['M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z'],
    ];

    // Ikon diambil dari `tipe` tahap, yang sudah berupa enum dari database.
    // Kalau admin memberi label sendiri, teksnya tetap dipakai sebagai cadangan
    // supaya ikon tidak pernah hilang.
    $timelineIconKeywords = [
        'pendaftaran' => 'calendar',
        'tes' => 'clipboard',
        'verifikasi' => 'document',
        'pengumuman' => 'bell',
        'daftar ulang' => 'check-circle',
    ];

    $cariIkonTahap = function (string $tipe, string $nama) use ($timelineIconKeywords): string {
        $teks = mb_strtolower($tipe.' '.$nama);

        foreach ($timelineIconKeywords as $kata => $ikon) {
            if (str_contains($teks, $kata)) {
                return $ikon;
            }
        }

        return 'calendar';
    };

    // Warna strip tahap memakai predikat yang sudah ada di model, jadi strip
    // dan timeline detail tidak mungkin berbeda pendapat soal "tahap mana yang
    // sudah lewat".
    $kelasTitikTahap = fn ($tahap) => match (true) {
        $tahap->berlangsung() => 'bg-accent-600 ring-4 ring-accent-100',
        $tahap->sudahLewat() => 'bg-gray-300',
        default => 'bg-white border-2 border-primary-300',
    };

    $kelasGarisTahap = fn ($tahap) => $tahap->sudahLewat() ? 'bg-gray-300' : 'bg-primary-200';

    // Label untuk strip. Strip sengaja tidak mencetak nama tahap karena lima
    // nama + lima tanggal tidak muat di layar 375px, jadi informasinya
    // dibawa lewat title (hover) dan screen reader.
    $judulTahap = function ($tahap) {
        $rentang = $tahap->punyaJendela()
            ? $tahap->tanggal_mulai->locale('id')->translatedFormat('d M Y')
                .' - '.$tahap->tanggalAkhir()->locale('id')->translatedFormat('d M Y')
            : $tahap->tanggal_mulai->locale('id')->translatedFormat('d M Y');

        return $tahap->nama_tahap.' · '.$rentang;
    };

    // Daftar berkas di bawah harus sama persis dengan `$uploadFields` yang
    // jadi kolom unggahan di langkah 4. Versi lama menulis "fotokopi rapor
    // SD kelas 4-6" di sini, padahal tidak ada kolom rapor di form, jadi
    // orang tua menyiapkan berkas yang tidak pernah diminta.
    $persyaratanList = [
        'Ijazah atau Surat Keterangan Lulus, PDF maks 5MB',
        'Kartu Keluarga, PDF maks 5MB',
        'Akta Kelahiran, PDF maks 5MB',
        'Pas foto berwarna, JPG atau PNG maks 2MB',
    ];

    // Catatan ini dulu berbunyi "Tes masuk meliputi: Matematika, IPA, dan
    // Bahasa Indonesia". Sistem ini tidak punya modul tes seleksi sama
    // sekali, jadi kalimat itu hanya mengarang proses yang tidak ada (R-36).
    $infoPentingList = [
        ['icon' => 'clipboard', 'text' => 'Pendaftaran dilakukan lewat formulir di halaman ini'],
        ['icon' => 'document', 'text' => 'Berkas diunggah di langkah 4 dan bisa diunggah ulang kalau ditolak'],
        ['icon' => 'bell', 'text' => 'Pengumuman resmi terbit di halaman pengumuman sekolah'],
        ['icon' => 'phone', 'text' => 'Pertanyaan soal berkas bisa langsung ditanyakan ke sekolah'],
    ];

    // Tiga pil ini ("Info Penting") dikembalikan sesuai permintaan: komponen
    // ini ada di versi lama dan hilang saat section ini ditulis ulang. Isinya
    // masih klaim hardcode tanpa SLA di sistem, jadi tetap berupa janji yang
    // harus dipenuhi sekolah, bukan fakta yang bisa diverifikasi aplikasi.
    $trustStrip = [
        ['icon' => 'globe', 'title' => '100% Online', 'desc' => 'Daftar dari mana saja'],
        ['icon' => 'bolt', 'title' => 'Proses 3 Hari', 'desc' => 'Verifikasi berkas cepat'],
        ['icon' => 'shield-check', 'title' => 'Data Aman', 'desc' => 'Privasi terjaga'],
    ];

    /*
     * `autocomplete` per kolom, bukan satu nilai global.
     *
     * Tanpa ini, orang tua yang mengisi di ponsel harus mengetik NIK 16 digit,
     * NISN 10 digit, nama, dan alamat lengkap dari nol setiap kali. Browser
     * sebenarnya sudah tahu sebagian jawaban ini dari formulir yang pernah
     * diisi sebelumnya, atau dari akun yang sedang(login. Meminta token
     * `autocomplete` yang tepat adalah perubahan paling murah untuk mengisi
     * form ini.
     *
     * `inputmode` menentukan keyboard virtual yang muncul di ponsel, dan
     * itu menentukan apakah orang bisa mengetik cepat atau tidak: NIK dan
     * NISN harus mendapat keyboard angka, nomor HP harus mendapat `tel` yang
     * memunculkan tombol +, bukan keyboard alfabet.
     *
     * `spellcheck` dimatikan untuk kolom yang isinya kode (NIK, NISN, email).
     * Tanpa itu Android menandai NIK sebagai salah eja dan menawarkan koreksi
     * yang tidak pernah benar.
     */
    $dataPribadiFields = [
        ['id' => 'nama_lengkap', 'label' => 'Nama Lengkap', 'icon' => 'user', 'type' => 'text', 'autocomplete' => 'name', 'spellcheck' => true, 'placeholder' => 'Masukkan nama lengkap sesuai akta kelahiran'],
        ['id' => 'jenis_kelamin', 'label' => 'Jenis Kelamin', 'icon' => 'users', 'type' => 'select', 'autocomplete' => 'sex', 'options' => ['L' => 'Laki-laki', 'P' => 'Perempuan'], 'placeholder' => 'Pilih jenis kelamin'],
        ['id' => 'nik', 'label' => 'NIK', 'icon' => 'id-card', 'type' => 'text', 'autocomplete' => 'off', 'inputmode' => 'numeric', 'pattern' => '[0-9]{16}', 'spellcheck' => false, 'placeholder' => '16 digit NIK', 'maxlength' => 16],
        ['id' => 'nisn', 'label' => 'NISN', 'icon' => 'graduation-cap', 'type' => 'text', 'autocomplete' => 'off', 'inputmode' => 'numeric', 'pattern' => '[0-9]{10}', 'spellcheck' => false, 'placeholder' => '10 digit NISN', 'maxlength' => 10],
        ['id' => 'tempat_lahir', 'label' => 'Tempat Lahir', 'icon' => 'location', 'type' => 'text', 'autocomplete' => 'address-level2', 'placeholder' => 'Kota atau kabupaten tempat lahir'],
        ['id' => 'tanggal_lahir', 'label' => 'Tanggal Lahir', 'icon' => 'calendar', 'type' => 'date', 'autocomplete' => 'bday', 'placeholder' => 'Tanggal lahir'],
        ['id' => 'agama', 'label' => 'Agama', 'icon' => 'book-open', 'type' => 'select', 'autocomplete' => 'off', 'options' => array_combine(\App\Models\CalonSiswa::AGAMA, \App\Models\CalonSiswa::AGAMA), 'placeholder' => 'Pilih agama'],
        ['id' => 'asal_sekolah', 'label' => 'Asal Sekolah', 'icon' => 'graduation-cap', 'type' => 'text', 'autocomplete' => 'off', 'placeholder' => 'Nama sekolah asal lengkap'],
        ['id' => 'alamat', 'label' => 'Alamat', 'icon' => 'home', 'type' => 'textarea', 'autocomplete' => 'street-address', 'placeholder' => 'Alamat lengkap tempat tinggal', 'span' => 'md:col-span-2'],
        ['id' => 'no_hp', 'label' => 'No. HP Siswa', 'icon' => 'mobile', 'type' => 'tel', 'autocomplete' => 'tel', 'inputmode' => 'tel', 'placeholder' => '08xxxxxxxxxx'],
        ['id' => 'email', 'label' => 'Email', 'icon' => 'envelope', 'type' => 'email', 'autocomplete' => 'email', 'spellcheck' => false, 'placeholder' => 'email@contoh.com'],
    ];

    $dataOrangTuaFields = [
        ['id' => 'nama_ayah', 'label' => 'Nama Ayah', 'icon' => 'user', 'type' => 'text', 'autocomplete' => 'off', 'spellcheck' => true, 'placeholder' => 'Nama lengkap ayah'],
        ['id' => 'pekerjaan_ayah', 'label' => 'Pekerjaan Ayah', 'icon' => 'briefcase', 'type' => 'text', 'autocomplete' => 'off', 'placeholder' => 'Pekerjaan ayah'],
        ['id' => 'nama_ibu', 'label' => 'Nama Ibu', 'icon' => 'user', 'type' => 'text', 'autocomplete' => 'off', 'spellcheck' => true, 'placeholder' => 'Nama lengkap ibu'],
        ['id' => 'pekerjaan_ibu', 'label' => 'Pekerjaan Ibu', 'icon' => 'briefcase', 'type' => 'text', 'autocomplete' => 'off', 'placeholder' => 'Pekerjaan ibu'],
        ['id' => 'no_hp_orang_tua', 'label' => 'No. HP Orang Tua', 'icon' => 'phone', 'type' => 'tel', 'autocomplete' => 'tel', 'inputmode' => 'tel', 'placeholder' => '08xxxxxxxxxx', 'span' => 'md:col-span-2'],
    ];

    $uploadFields = [
        ['id' => 'ijazah_path', 'label' => 'Ijazah', 'icon' => 'document', 'accept' => '.pdf', 'hint' => 'PDF, maks 5MB', 'required' => true],
        ['id' => 'kk_path', 'label' => 'Kartu Keluarga', 'icon' => 'document', 'accept' => '.pdf', 'hint' => 'PDF, maks 5MB', 'required' => true],
        ['id' => 'akta_path', 'label' => 'Akta Kelahiran', 'icon' => 'document', 'accept' => '.pdf', 'hint' => 'PDF, maks 5MB', 'required' => true],
        ['id' => 'foto_path', 'label' => 'Pas Foto', 'icon' => 'image', 'accept' => 'image/*', 'hint' => 'JPG/PNG, maks 2MB', 'required' => true],
        ['id' => 'skl_path', 'label' => 'Surat Keterangan Lulus', 'icon' => 'document', 'accept' => '.pdf', 'hint' => 'PDF, maks 5MB', 'required' => true],
        ['id' => 'krm_path', 'label' => 'KRM', 'icon' => 'document', 'accept' => '.pdf,image/*', 'hint' => 'PDF/JPG, maks 5MB (opsional)', 'required' => false],
        ['id' => 'kip_path', 'label' => 'KIP', 'icon' => 'document', 'accept' => '.pdf,image/*', 'hint' => 'PDF/JPG, maks 5MB (opsional)', 'required' => false],
    ];

    $stepLabels = [1 => 'Jalur', 2 => 'Data Pribadi', 3 => 'Data Ortu', 4 => 'Upload Berkas'];

    $renderIcon = fn ($key) => $icons[$key] ?? $icons['check-circle'];
@endphp

@section('content')

    {{-- `id` ini jadi tujuan skip link di layout. Tanpa <main>, pengguna
         keyboard menekan "Lompat ke isi utama" akan mendarat di nowhere
         karena tidak ada elemen dengan id itu di halaman ini. --}}
    <main id="konten-utama">
        @include('spmb.partials.navbar', ['transparent' => true])

    {{-- ============ HERO ============ --}}
    {{--
        Hero versi lama punya ilustrasi dari `via.placeholder.com` (kotak abu
        abu layanan pihak ketiga yang bukan milik sekolah), dua kartu yang
        melayang tanpa henti, dan tiga pil klaim: "100% Online", "Proses 3
        Hari", dan "Data Aman / Privasi terjaga". Tidak ada satu pun angka
        atau jaminan itu yang punya sumber: tidak ada SLA verifikasi berkas
        dan tidak ada klaim keamanan yang bisa ditunjukkan (R-17, R-36).

        Yang diganti adalah hal yang memang diketahui sistem: berapa
        langkah formnya, dan berkas apa yang harus disiapkan. Itu yang
        paling sering ditanyakan orang tua sebelum mulai mengisi.
    --}}
    <section class="bg-primary-900 pt-28 pb-16 text-white sm:pt-36 sm:pb-20">
        <div class="mx-auto max-w-6xl px-5 sm:px-6">
            <div class="grid gap-12 lg:grid-cols-12 lg:gap-16">

                <div class="lg:col-span-7">
                    <h1 class="font-display text-4xl font-extrabold leading-[1.1] tracking-tight sm:text-5xl">
                        Formulir pendaftaran
                        <span class="text-secondary-300">{{ $tahunAjaranAktif?->nama_tahun_ajaran }}</span>
                    </h1>

                    <p class="mt-6 max-w-xl text-base leading-relaxed text-secondary-100/90 sm:text-lg">
                        Ada empat langkah. Isi data yang sudah kamu tahu, lalu
                        unggah berkasnya di langkah terakhir. Kalau belum punya
                        salinan digital dari berkas, tidak apa-apa, bisa diunggah
                        nanti lewat akun siswa.
                    </p>

                    <div class="mt-9 flex flex-col gap-3 sm:flex-row">
                        <a href="#form-pendaftaran" class="btn btn-accent w-full sm:w-auto">
                            Mulai Mengisi Formulir
                        </a>
                        <a href="#info-ppdb"
                           class="btn w-full border border-white/25 text-white hover:border-white hover:bg-white/10 sm:w-auto">
                            Lihat Jadwal Pendaftaran
                        </a>
                    </div>

                    {{-- Tiga pill ini kembali ke posisi aslinya, di bawah
                         tombol. Nilai "100% Online / Proses 3 Hari / Data
                         Aman" memang klaim sekolah, bukan angka dari
                         sistem, jadi dipakai sebagai janji yang harus
                         diterima sekolah. --}}
                    <ul class="mt-12 grid gap-6 border-t border-primary-800 pt-8 sm:grid-cols-3">
                        @foreach ($trustStrip as $trust)
                            <li>
                                <span class="grid h-10 w-10 place-items-center rounded-md bg-primary-800 text-secondary-300">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8"
                                         viewBox="0 0 24 24" aria-hidden="true">
                                        @foreach ($renderIcon($trust['icon']) as $d)
                                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $d }}"/>
                                        @endforeach
                                    </svg>
                                </span>
                                <p class="mt-4 font-display text-base font-extrabold text-white">{{ $trust['title'] }}</p>
                                <p class="mt-1 text-sm leading-relaxed text-secondary-100/80">{{ $trust['desc'] }}</p>
                            </li>
                        @endforeach
                    </ul>
                </div>

                {{-- Berkas yang harus disiapkan. Diletakkan di hero karena
                     inilah yang membuat orang tua berhenti dan menyiapkan
                     dokumen sebelum mulai mengisi. --}}
                <div class="lg:col-span-5">
                    <div class="rounded-lg bg-primary-800 p-6">
                        <h2 class="font-display text-sm font-extrabold uppercase tracking-wider text-secondary-300">
                            Siapkan sebelum mengisi
                        </h2>
                        <ul class="mt-4 space-y-2.5">
                            @foreach ($persyaratanList as $butir)
                                <li class="flex gap-2.5 text-sm leading-relaxed text-secondary-100/90">
                                    <svg class="mt-0.5 h-4 w-4 shrink-0 text-secondary-300" fill="none"
                                         stroke="currentColor" stroke-width="3" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    <span>{{ $butir }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @if ($errors->any())
        {{-- Pesan kesalahan memakai `role="alert"` supaya pembaca layar
             membacanya saat halaman dimuat, bukan hanya kelihatan secara
             visual. Status tidak pernah hanya mengandalkan warna: ada ikon
             dan daftar teksnya. --}}
        <div class="mx-auto max-w-4xl px-4 sm:px-6">
            <div role="alert" class="error-box p-6">
                <div class="flex items-start gap-3">
                    <svg class="h-6 w-6 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        @foreach ($renderIcon('exclamation-triangle') as $d)
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $d }}"/>
                        @endforeach
                    </svg>
                    <div>
                        <h3 class="error-heading text-sm font-semibold">Ada kesalahan dalam pengisian form:</h3>
                        <ul class="mt-2 space-y-1 pl-5 text-sm leading-relaxed">
                            @foreach ($errors->all() as $error)
                                <li class="list-disc">{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- ============ INFO PPDB ============ --}}
    <section id="info-ppdb" class="bg-kertas-200 py-20 sm:py-24">
        <div class="mx-auto max-w-4xl px-5 sm:px-6">

            <div class="max-w-2xl">
                <h2 class="reveal font-display text-3xl font-extrabold leading-tight text-ink-900 sm:text-4xl">
                    Jadwal pendaftaran
                </h2>
                <p class="reveal mt-5 text-base leading-relaxed text-ink-600">
                    Pilih gelombang untuk melihat tahapannya satu per satu.
                    Gelombang yang sedang dibuka terbuka sendiri.
                </p>
            </div>

            <div class="mt-10 space-y-4">
                    @forelse ($semuaGelombangs as $gelombangTimeline)
                        @php
                            // Status memakai objek yang sama dengan badge hero, jadi
                            // kartu dan hero tidak pernah berbeda pendapat.
                            $statusGelombang = \App\Support\StatusPendaftaran::untuk($gelombangTimeline);
                            $sedangBuka = $statusGelombang->state === \App\Support\StatusPendaftaran::BUKA;
                            $tahapPendaftaran = $gelombangTimeline->tahapPendaftaran();
                            // Pita status memakai token palet sendiri. Versi lama memakai
                            // `bg-green-100`, `bg-sky-100`, `bg-amber-100`,
                            // `bg-gray-200`, yaitu empat warna dari luar sistem
                            // warna halaman ini.
                            $kelasStatus = match ($statusGelombang->state) {
                                \App\Support\StatusPendaftaran::BUKA => 'bg-primary-50 text-primary-800 ring-primary-200',
                                \App\Support\StatusPendaftaran::BELUM => 'bg-secondary-50 text-secondary-700 ring-secondary-200',
                                default => 'bg-kertas-200 text-ink-600 ring-kertas-400',
                            };

                            // Tahap yang sedang berjalan, atau yang paling dekat
                            // akan datang. Ini yang dicari calon ("saya harus
                            // siapkan apa sekarang?"), jadi ditulis sebagai teks
                            // dan bukan cuma ditunjukkan oleh titik.
                            $tahapSekarang = $gelombangTimeline->tahapan->first(
                                fn ($t) => $t->berlangsung() || $t->belumMulai()
                            );
                        @endphp

                        {{-- Gelombang yang sedang dibuka otomatis terbuka, yang
                             lain tertutup. Halaman tetap ringkas, tapi tidak ada
                             informasi yang disembunyikan: <details> tetap
                             membloknya di dalam HTML. --}}
                        <details @if ($sedangBuka) open @endif class="group overflow-hidden rounded-md border border-kertas-300 bg-kertas-100">
                            <summary class="flex cursor-pointer list-none flex-col gap-3 px-5 py-5 transition-colors hover:bg-kertas-200 [&::-webkit-details-marker]:hidden sm:flex-row sm:items-center sm:gap-5 sm:px-6">
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h3 class="font-display text-lg font-extrabold text-ink-900">{{ $gelombangTimeline->nama_gelombang }}</h3>
                                        <span class="rounded-md px-2.5 py-0.5 text-xs font-semibold ring-1 {{ $kelasStatus }}">{{ $statusGelombang->labelGelombang() }}</span>
                                    </div>
                                    <p class="mt-1 text-sm text-ink-600">
                                        @if ($tahapPendaftaran)
                                            Pendaftaran
                                            {{ $tahapPendaftaran->tanggal_mulai->locale('id')->translatedFormat('d M Y') }}
                                            @if ($tahapPendaftaran->punyaJendela())
                                                &ndash; {{ $tahapPendaftaran->tanggalAkhir()->locale('id')->translatedFormat('d M Y') }}
                                            @endif
                                        @else
                                            Jadwal pendaftaran belum ditetapkan
                                        @endif
                                    </p>
                                    @if ($tahapSekarang)
                                        <p class="mt-1 text-sm font-medium text-secondary-700">
                                            {{ $tahapSekarang->berlangsung() ? 'Tahap sekarang' : 'Tahap berikutnya' }}:
                                            {{ $tahapSekarang->nama_tahap }}
                                        </p>
                                    @endif
                                </div>

                                {{-- Strip tahap: menunjukkan posisi batch ini tanpa
                                     harus membuka detail. Hiasan saja, maknanya
                                     sudah ditulis sebagai teks di sebelahnya.
                                     `title` tetap dipasang supaya pengguna mouse
                                     bisa membaca nama dan tanggal penuh tanpa
                                     membuka detail; titiknya sendiri disembunyikan
                                     dari pembaca layar karena maknanya sudah
                                     tertulis sebagai teks. --}}
                                <div class="flex shrink-0 items-center gap-1.5" aria-hidden="true">
                                    @foreach ($gelombangTimeline->tahapan as $titik)
                                        @if (! $loop->first)
                                            <span class="h-0.5 w-4 {{ $kelasGarisTahap($titik) }}"></span>
                                        @endif
                                        <span class="h-3 w-3 rounded-full {{ $kelasTitikTahap($titik) }}"
                                              title="{{ $judulTahap($titik) }}"></span>
                                    @endforeach
                                </div>

                                <svg class="h-5 w-5 shrink-0 text-ink-400 transition-transform group-open:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </summary>

                            {{-- Detail: rail di kiri, satu kolom di semua ukuran
                                 layar. Versi lama memakai w-5/12 tanpa breakpoint
                                 sehingga di ponsel 375px tiap kartu hanya
                                 ~140px dan tanggalnya terpotong. --}}
                            <div class="border-t border-kertas-300 bg-kertas-200 px-5 py-6 sm:px-6">
                                <ol class="relative ml-2 space-y-4 border-l-2 border-primary-200">
                                    @foreach ($gelombangTimeline->tahapan as $tahap)
                                        @php
                                            $iconKey = $cariIkonTahap($tahap->tipe, $tahap->nama_tahap);
                                            $sedangBerlangsung = $tahap->berlangsung();
                                        @endphp
                                        <li class="relative ml-5">
                                            <span class="absolute -left-[27px] top-1 h-6 w-6 rounded-full border-4 bg-white {{ $sedangBerlangsung ? 'border-accent-600' : 'border-primary-400' }}"></span>
                                            <div class="rounded-md border bg-kertas-100 p-4 {{ $sedangBerlangsung ? 'border-accent-300 ring-2 ring-accent-100' : 'border-kertas-300' }}">
                                                <div class="flex items-start gap-3">
                                                    <div class="w-9 h-9 shrink-0 rounded-lg flex items-center justify-center text-white {{ $sedangBerlangsung ? 'bg-accent-600' : 'bg-primary-600' }}">
                                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                            @foreach ($renderIcon($iconKey) as $d)
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $d }}"/>
                                                            @endforeach
                                                        </svg>
                                                    </div>
                                                    <div class="min-w-0 flex-1">
                                                        <h4 class="text-sm font-semibold text-ink-900">{{ $tahap->nama_tahap }}</h4>
                                                        <p class="text-sm text-ink-600">
                                                            {{ $tahap->tanggal_mulai->locale('id')->translatedFormat('d M Y') }}
                                                            @if ($tahap->punyaJendela())
                                                                sampai {{ $tahap->tanggalAkhir()->locale('id')->translatedFormat('d M Y') }}
                                                            @endif
                                                        </p>
                                                        @if ($tahap->keterangan)
                                                            <p class="mt-1 text-sm leading-relaxed text-ink-600">{{ $tahap->keterangan }}</p>
                                                        @endif
                                                    </div>
                                                    @if ($sedangBerlangsung)
                                                        <span class="shrink-0 rounded-md bg-accent-50 px-2.5 py-0.5 text-xs font-semibold text-accent-700 ring-1 ring-accent-200">Berlangsung</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </li>
                                    @endforeach
                                </ol>
                            </div>
                        </details>
                    @empty
                        {{-- Empty state (R-27): menyebut sebab dan jalan keluar. --}}
                        <div class="rounded-md border border-kertas-300 bg-kertas-100 p-8">
                            <h3 class="font-display text-lg font-extrabold text-ink-900">Jadwal pendaftaran belum dipublikasikan</h3>
                            <p class="mt-2 max-w-lg text-base leading-relaxed text-ink-600">
                                Sekolah belum menetapkan tanggal gelombang untuk
                                tahun ajaran ini. Halaman pengumuman akan memberi
                                tahu begitu jadwalnya keluar.
                            </p>
                            <a href="{{ route('spmb.pengumuman.index') }}" class="btn btn-quiet mt-6">
                                Lihat Pengumuman
                            </a>
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- Daftar berkas sudah pindah ke hero, jadi di sini tidak perlu
                     kartu yang mengulanginya. Yang tersisa adalah catatan
                     cara kerjanya, satu panel, bukan dua kartu gradien yang
                     saling menyaingkan. --}}
                <div class="mt-10 max-w-3xl rounded-lg bg-primary-800 p-7 text-white sm:p-8">
                    <h3 class="font-display text-lg font-extrabold">Cara kerjanya</h3>
                    <ul class="mt-4 space-y-3">
                        @foreach ($infoPentingList as $info)
                            <li class="flex gap-3 text-base leading-relaxed text-secondary-100/90">
                                <svg class="mt-1 h-5 w-5 shrink-0 text-secondary-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    @foreach ($renderIcon($info['icon']) as $d)
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $d }}"/>
                                    @endforeach
                                </svg>
                                <span>{{ $info['text'] }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
        </div>
    </section>

    {{-- ============ FORM WIZARD ============ --}}
    <section id="form-pendaftaran" class="bg-kertas-100 py-20 sm:py-24">
        <div class="relative mx-auto max-w-4xl px-4 sm:px-6">
            <div class="max-w-2xl">
                <h2 class="reveal font-display text-3xl font-extrabold leading-tight text-ink-900 sm:text-4xl">
                    Isi formulirnya
                </h2>
                <p class="reveal mt-5 text-base leading-relaxed text-ink-600">
                    Formulir dibagi empat langkah. Kamu bisa mundur dan
                    memperbaiki langkah yang sudah terisi sebelum mengirim.
                </p>
            </div>

            @if (session('success'))
                <div role="status" class="mb-8 rounded-md border border-primary-200 bg-primary-50 p-6">
                    <div class="flex items-center gap-3">
                        <svg class="h-6 w-6 shrink-0 text-primary-700" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            @foreach ($renderIcon('check-circle') as $d)
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $d }}"/>
                            @endforeach
                        </svg>
                        <p class="text-sm font-medium text-primary-900">{{ session('success') }}</p>
                    </div>
                </div>
            @endif

            @unless ($pendaftaranDibuka)
                <div role="status" class="mx-auto mb-8 max-w-3xl rounded-md border border-secondary-200 bg-secondary-50 p-7">
                    <div class="flex items-start gap-4">
                        <span aria-hidden="true" class="grid h-11 w-11 shrink-0 place-items-center rounded-md bg-secondary-600 text-white">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M5.07 19h13.86a2 2 0 001.74-3L13.74 4a2 2 0 00-3.48 0l-7 12a2 2 0 001.74 3z"/>
                            </svg>
                        </span>
                        <div>
                            {{-- Pesan berasal dari StatusPendaftaran, jadi menyebut batch
                                 yang konkret, bukan lagi tanggal global yang bisa
                                 bertentangan dengan daftar gelombang di bawah. --}}
                            <h3 class="font-display text-lg font-extrabold text-secondary-700">
                                {{ $statusPendaftaran->state === \App\Support\StatusPendaftaran::PENUH
                                    ? 'Kuota gelombang ini penuh'
                                    : 'Pendaftaran belum dibuka' }}
                            </h3>
                            <p class="mt-2 leading-relaxed text-secondary-700">{{ $statusPendaftaran->pesan }}</p>

                            @if ($statusPendaftaran->gelombangBerikut)
                                @php $tahap = $statusPendaftaran->gelombangBerikut->tahapPendaftaran(); @endphp
                                <p class="mt-3 text-sm text-secondary-700">
                                    {{ $statusPendaftaran->gelombangBerikut->nama_gelombang }} dibuka mulai
                                    {{ $tahap?->tanggal_mulai->translatedFormat('j M Y') }}
                                    sampai {{ $tahap?->tanggalAkhir()->translatedFormat('j M Y') }}
                                </p>
                            @endif
                        </div>
                    </div>
                </div>
            @endunless

            {{-- Kartu form tidak memakai bayangan besar. Batasnya digambar dengan
                 garis rambut, supaya form ini tidak terlihat melayang di atas
                 halaman seperti kartu biasa (R-12). --}}
            <div class="overflow-hidden rounded-lg border border-kertas-300 bg-kertas-100 {{ $pendaftaranDibuka ? '' : 'pointer-events-none opacity-60' }}">
                <form method="POST" action="{{ route('spmb.store') }}" enctype="multipart/form-data" id="form-wizard" class="p-6 md:p-10">
                    @csrf

                    {{-- Progress Steps. Indikatornya memakai nama label yang sama dengan
                         judul tiap langkah, supaya calon bisa mencocokkan
                         "langkah 3" yang tertulis di bawah dengan lingkaran
                         yang menyala di atas. --}}
                    <div class="sticky top-20 z-20 mb-10">
                        <div class="rounded-md border border-kertas-300 bg-kertas-100/95 p-4 backdrop-blur-sm">
                            <div class="flex items-center justify-between">
                                @foreach ($stepLabels as $num => $label)
                                    <div class="flex flex-1 items-center {{ $num == count($stepLabels) ? 'flex-none' : '' }}" data-step-indicator="{{ $num }}">
                                        <div class="flex shrink-0 flex-col items-center">
                                            <div class="step-circle flex h-10 w-10 items-center justify-center rounded-md font-bold transition-colors duration-200 {{ $num == 1 ? 'bg-primary-700 text-white ring-2 ring-primary-700 ring-offset-2' : 'bg-kertas-300 text-ink-600' }}">
                                                {{ $num }}
                                            </div>
                                            <span class="mt-1 hidden text-xs font-medium sm:block {{ $num == 1 ? 'text-primary-800' : 'text-ink-500' }}">{{ $label }}</span>
                                        </div>
                                        @if ($num < count($stepLabels))
                                            <div class="mx-2 h-1 flex-1 rounded-full bg-kertas-300 sm:mx-4"></div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    {{-- Step 1: Jalur Pendaftaran.

                         Step ini TIDAK diberi `hidden` di HTML. Versi lama
                         menyembunyikan keempat langkah sekaligus lalu
                         mengandalkan JS untuk membuka langkah pertama, tapi
                         tidak pernah memanggil `showStep(1)` saat inisialisasi.
                         Akibatnya seluruh wizard tidak terlihat: tombol
                         "Lanjut" ada di dalam panel yang tersembunyi, jadi
                         tidak ada jalan untuk membukanya.

                         Memilih halaman yang tampil di langkah pertama, bukan
                         hasil JS, membuat form tetap terbaca kalau JS gagal
                         dimuat atau dimatikan (R-27). --}}
                    <div data-step="1">
                        <div class="bg-primary-50 rounded-2xl p-6 md:p-8 mb-8 border border-primary-100">
                            <div class="flex items-center mb-6">
                                <div class="w-12 h-12 bg-primary-600 rounded-xl flex items-center justify-center text-white mr-4 flex-shrink-0 shadow-md">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        @foreach ($renderIcon('route') as $d)
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $d }}"/>
                                        @endforeach
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-xl font-bold text-ink-900">Pilih Jalur Pendaftaran</h3>
                                    <p class="text-sm text-ink-500">Langkah 1 dari 4</p>
                                </div>
                            </div>

                            <label for="jalur_pendaftaran_id" class="block text-sm font-semibold text-ink-700 mb-2">
                                Jalur Pendaftaran <span aria-hidden="true" class="error-text">*</span><span class="sr-only"> (wajib diisi)</span>
                            </label>
                            <select id="jalur_pendaftaran_id" name="jalur_pendaftaran_id" required autocomplete="off"
                                    class="field @error('jalur_pendaftaran_id') is-invalid @enderror">
                                <option value="">🎯 Pilih Jalur Pendaftaran</option>
                                @foreach ($jalurPendaftarans as $jalur)
                                    @if (! $jalur->aktif)
                                        @continue
                                    @endif
                                    @php
                                        $kuota = ($kuotaMap ?? collect())->get($jalur->id);
                                        $kapasitas = $kuota?->kuota_pendaftaran;
                                        $sisa = $kapasitas ? max(0, $kapasitas - $kuota->terisi_pendaftaran) : null;
                                        $penuh = $kuota?->pendaftaranPenuh() ?? false;
                                    @endphp
                                    <option value="{{ $jalur->id }}"
                                            data-wajib-sertifikat="{{ $jalur->wajib_sertifikat ? 1 : 0 }}"
                                            data-sisa="{{ $sisa ?? '' }}"
                                            data-kapasitas="{{ $kapasitas ?? '' }}"
                                            @disabled($penuh)
                                            @selected(old('jalur_pendaftaran_id') == $jalur->id)>
                                        {{ $jalur->nama_jalur }}@if ($penuh) — Penuh @elseif ($sisa !== null) — sisa {{ $sisa }} kursi @endif
                                    </option>
                                @endforeach
                            </select>
                             {{-- `aria-live="polite"`: isi paragraf ini diganti JS setiap kali jalur
     dipilih berubah. Tanpa itu, pembaca layar tidak diberi tahu bahwa sisa kuota
     berganti, dan dampaknya justru di kolom yang paling informatif. --}}
                            <p id="info-kuota" class="mt-2 text-sm text-ink-500" role="status" aria-live="polite"></p>
                            <noscript>
                                <p class="mt-2 text-xs text-ink-500">Jalur bertanda "Penuh" sudah tidak menerima pendaftar baru.</p>
                            </noscript>
                            @error('jalur_pendaftaran_id')
                                <p class="field-error">{{ $message }}</p>
                            @enderror

                            {{-- Gelombang pendaftaran. Hanya dirender kalau ada
                                 gelombang yang jendelanya benar-benar terbuka,
                                 supaya sekolah yang belum memakai gelombang
                                 tidak pernah melihat field yang tak berguna.
                                 Sisa kursi berasal dari database, bukan
                                 ketik manual. --}}
                            @if (($gelombangs ?? collect())->isNotEmpty())
                                <div class="mt-6 pt-6 border-t border-primary-100">
                                    <label for="gelombang_id" class="block text-sm font-semibold text-ink-700 mb-2">
                                        Gelombang Pendaftaran <span aria-hidden="true" class="error-text">*</span><span class="sr-only"> (wajib diisi)</span>
                                    </label>
                                    <select id="gelombang_id" name="gelombang_id" required autocomplete="off"
                                            class="field @error('gelombang_id') is-invalid @enderror">
                                        <option value="">Pilih Gelombang</option>
                                        @foreach ($gelombangs as $gelombang)
                                            <option value="{{ $gelombang->id }}"
                                                    data-sisa="{{ $gelombang->sisa_kursi ?? '' }}"
                                                    @selected(old('gelombang_id') == $gelombang->id)>
                                                {{ $gelombang->nama_gelombang }}@if ($gelombang->sisa_kursi !== null) (sisa {{ $gelombang->sisa_kursi }} kursi) @endif
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('gelombang_id')
                                        <p class="field-error">{{ $message }}</p>
                                    @enderror
                                </div>
                            @endif
                        </div>

                        <div class="flex justify-end">
                            <button type="button" data-next="2"
                                    class="btn btn-primary">
                                Lanjut
                                <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    @foreach ($renderIcon('arrow-right') as $d)
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $d }}"/>
                                    @endforeach
                                </svg>
                            </button>
                        </div>
                    </div>

                    {{-- Step 2: Data Pribadi --}}
                    {{-- Kepala keempat langkah sengaja memakai warna yang sama.
                         Dulu tiap langkah punya warna sendiri, jadi warna di
                         halaman jadi dekorasi dan tidak menandai apa pun.
                         Biru langit hanya dipakai untuk keadaan "sedang
                         berlangsung" pada jadwal gelombang. --}}
                    <div data-step="2" class="hidden">
                        <div class="rounded-lg border border-kertas-300 bg-kertas-200 p-6 md:p-8">
                            <div class="mb-6 flex items-center">
                                <div class="mr-4 grid h-12 w-12 shrink-0 place-items-center rounded-md bg-primary-700 text-white">
                                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        @foreach ($renderIcon('user') as $d)
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $d }}"/>
                                        @endforeach
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-xl font-bold text-ink-900">Data Pribadi Siswa</h3>
                                    <p class="text-sm text-ink-500">Langkah 2 dari 4</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                @foreach ($dataPribadiFields as $field)
                                    <div class="space-y-2 {{ $field['span'] ?? '' }}">
                                        <label for="{{ $field['id'] }}" class="block text-sm font-semibold text-ink-700">
                                            {{ $field['label'] }} <span aria-hidden="true" class="error-text">*</span><span class="sr-only"> (wajib diisi)</span>
                                        </label>
                                        <div class="relative">
                                            <svg class="field-icon-slot" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                @foreach ($renderIcon($field['icon']) as $d)
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $d }}"/>
                                                @endforeach
                                            </svg>

                                            @if ($field['type'] === 'select')
                                                <select id="{{ $field['id'] }}" name="{{ $field['id'] }}" required
                                                        @if (! empty($field['autocomplete'])) autocomplete="{{ $field['autocomplete'] }}" @endif
                                                        class="field field-icon @error($field['id']) is-invalid @enderror">
                                                    <option value="">{{ $field['placeholder'] }}</option>
                                                    @foreach ($field['options'] as $val => $optLabel)
                                                        <option value="{{ $val }}" {{ old($field['id']) == $val ? 'selected' : '' }}>{{ $optLabel }}</option>
                                                    @endforeach
                                                </select>
                                            @elseif ($field['type'] === 'textarea')
                                                <textarea id="{{ $field['id'] }}" name="{{ $field['id'] }}" required rows="3"
                                                          @if (! empty($field['autocomplete'])) autocomplete="{{ $field['autocomplete'] }}" @endif
                                                          class="field field-icon @error($field['id']) is-invalid @enderror"
                                                          placeholder="{{ $field['placeholder'] }}">{{ old($field['id']) }}</textarea>
                                            @else
                                                <input type="{{ $field['type'] }}" id="{{ $field['id'] }}" name="{{ $field['id'] }}" required
                                                       @if (! empty($field['autocomplete'])) autocomplete="{{ $field['autocomplete'] }}" @endif
                                                       @if (! empty($field['inputmode'])) inputmode="{{ $field['inputmode'] }}" @endif
                                                       @if (! empty($field['pattern'])) pattern="{{ $field['pattern'] }}" @endif
                                                       @if (isset($field['spellcheck'])) spellcheck="{{ $field['spellcheck'] ? 'true' : 'false' }}" @endif
                                                       @if (isset($field['maxlength'])) maxlength="{{ $field['maxlength'] }}" @endif
                                                       class="field field-icon @error($field['id']) is-invalid @enderror"
                                                       value="{{ old($field['id']) }}" placeholder="{{ $field['placeholder'] ?? '' }}">
                                            @endif
                                        </div>
                                        @error($field['id'])
                                            <p class="field-error">{{ $message }}</p>
                                        @enderror
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="flex justify-between">
                            <button type="button" data-prev="1"
                                    class="inline-flex min-h-11 items-center rounded-md border border-kertas-300 bg-kertas-100 px-5 text-sm font-semibold text-ink-700 transition-colors hover:bg-kertas-200 hover:border-ink-400">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    @foreach ($renderIcon('arrow-left') as $d)
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $d }}"/>
                                    @endforeach
                                </svg>
                                Kembali
                            </button>
                            <button type="button" data-next="3"
                                    class="btn btn-primary">
                                Lanjut
                                <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    @foreach ($renderIcon('arrow-right') as $d)
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $d }}"/>
                                    @endforeach
                                </svg>
                            </button>
                        </div>
                    </div>

                    {{-- Step 3: Data Orang Tua --}}
                    <div data-step="3" class="hidden">
                        <div class="rounded-lg border border-kertas-300 bg-kertas-200 p-6 md:p-8">
                            <div class="mb-6 flex items-center">
                                <div class="mr-4 grid h-12 w-12 shrink-0 place-items-center rounded-md bg-primary-700 text-white">
                                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        @foreach ($renderIcon('users') as $d)
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $d }}"/>
                                        @endforeach
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-xl font-bold text-ink-900">Data Orang Tua</h3>
                                    <p class="text-sm text-ink-500">Langkah 3 dari 4</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                @foreach ($dataOrangTuaFields as $field)
                                    <div class="space-y-2 {{ $field['span'] ?? '' }}">
                                        <label for="{{ $field['id'] }}" class="block text-sm font-semibold text-ink-700">
                                            {{ $field['label'] }} <span aria-hidden="true" class="error-text">*</span><span class="sr-only"> (wajib diisi)</span>
                                        </label>
                                        <div class="relative">
                                            <svg class="field-icon-slot" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                @foreach ($renderIcon($field['icon']) as $d)
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $d }}"/>
                                                @endforeach
                                            </svg>
                                            <input type="{{ $field['type'] }}" id="{{ $field['id'] }}" name="{{ $field['id'] }}" required
                                                   @if (! empty($field['autocomplete'])) autocomplete="{{ $field['autocomplete'] }}" @endif
                                                   @if (! empty($field['inputmode'])) inputmode="{{ $field['inputmode'] }}" @endif
                                                   @if (isset($field['spellcheck'])) spellcheck="{{ $field['spellcheck'] ? 'true' : 'false' }}" @endif
                                                   class="field field-icon @error($field['id']) is-invalid @enderror"
                                                   value="{{ old($field['id']) }}" placeholder="{{ $field['placeholder'] }}">
                                        </div>
                                        @error($field['id'])
                                            <p class="field-error">{{ $message }}</p>
                                        @enderror
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="flex justify-between">
                            <button type="button" data-prev="2"
                                    class="inline-flex min-h-11 items-center rounded-md border border-kertas-300 bg-kertas-100 px-5 text-sm font-semibold text-ink-700 transition-colors hover:bg-kertas-200 hover:border-ink-400">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    @foreach ($renderIcon('arrow-left') as $d)
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $d }}"/>
                                    @endforeach
                                </svg>
                                Kembali
                            </button>
                            <button type="button" data-next="4"
                                    class="btn btn-primary">
                                Lanjut
                                <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    @foreach ($renderIcon('arrow-right') as $d)
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $d }}"/>
                                    @endforeach
                                </svg>
                            </button>
                        </div>
                    </div>

                    {{-- Step 4: Upload Berkas
                         Warna oranye dihapus: oranye tidak ada di palet
                         halaman ini sama sekali, dan `bg-orange-500` dengan
                         teks putih hanya sekitar 3:1 sehingga ikon praktiknya
                         tak terlihat (R-25). --}}
                    <div data-step="4" class="hidden">
                        <div class="rounded-lg border border-kertas-300 bg-kertas-200 p-6 md:p-8">
                            <div class="mb-6 flex items-center">
                                <div class="mr-4 grid h-12 w-12 shrink-0 place-items-center rounded-md bg-primary-700 text-white">
                                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        @foreach ($renderIcon('upload') as $d)
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $d }}"/>
                                        @endforeach
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-xl font-bold text-ink-900">Upload Berkas Persyaratan</h3>
                                    <p class="text-sm text-ink-500">Langkah 4 dari 4, ini bagian terakhir.</p>
                                </div>
                            </div>

                            <div class="grid sm:grid-cols-2 gap-6">
                                @foreach ($uploadFields as $field)
                                    <div class="space-y-2 {{ $loop->last ? 'sm:col-span-2' : '' }}">
                                        <label for="{{ $field['id'] }}" class="block text-sm font-semibold text-ink-700">
                                            {{ $field['label'] }} @if($field['required'] ?? true)<span aria-hidden="true" class="error-text">*</span><span class="sr-only"> (wajib diisi)</span>@else<span class="text-ink-500 font-normal">(opsional)</span>@endif
                                        </label>
                                        {{-- Area unggah berkas. Garis putus-putus memakai warna palet, bukan
                                     `border-gray-300` yang hanya 1.47:1 di
                                     putih sehingga batas kotak nyaris tak
                                     terlihat (R-25, batas non-teks butuh 3:1). --}}
                                         <div class="relative rounded-md border-2 border-dashed border-ink-400 bg-white p-6 transition-colors hover:border-primary-600">
                                            <div class="text-center pointer-events-none">
                                                <svg class="text-ink-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                    @foreach ($renderIcon($field['icon']) as $d)
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $d }}"/>
                                                    @endforeach
                                                </svg>
                                                <div class="text-sm text-ink-600 mb-1">
                                                    <strong>Klik untuk upload</strong>
                                                </div>
                                                <div class="text-xs text-ink-500">{{ $field['hint'] }}</div>
                                            </div>
                                            <input type="file" id="{{ $field['id'] }}" name="{{ $field['id'] }}" accept="{{ $field['accept'] }}" @if($field['required'] ?? true) required @endif
                                                   class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                                        </div>
                                        @error($field['id'])
                                            <p class="field-error">{{ $message }}</p>
                                        @enderror
                                    </div>
                                @endforeach
                            </div>

                            {{-- Sertifikat prestasi: tampil hanya bila jalur mewajibkan --}}
                            @php $oldSertifikat = old('sertifikat', []); @endphp
                            <div id="sertifikat-section" class="mt-8 {{ collect($oldSertifikat)->isNotEmpty() ? '' : 'hidden' }}">
                                <h4 class="text-lg font-bold text-ink-900 mb-1">Sertifikat Prestasi <span aria-hidden="true" class="error-text">*</span><span class="sr-only"> (wajib diisi)</span></h4>
                                <p class="text-sm text-ink-500 mb-4">Jalur yang dipilih mewajibkan minimal 1 sertifikat (maks 5). PDF/JPG, maks 5MB per file.</p>
                                <div id="sertifikat-rows" class="space-y-4">
                                    @forelse ($oldSertifikat as $i => $row)
                                        <div class="sertifikat-row grid grid-cols-1 gap-4 rounded-md border border-kertas-300 bg-kertas-100 p-4 md:grid-cols-[1fr_1fr_auto] md:items-end">
                                            <div>
                                                <label for="sertifikat-{{ $i }}-nama" class="block text-sm font-semibold text-ink-700 mb-2">Nama Kejuaraan <span aria-hidden="true" class="error-text">*</span><span class="sr-only"> (wajib diisi)</span></label>
                                                <input type="text" id="sertifikat-{{ $i }}-nama" name="sertifikat[{{ $i }}][nama]" value="{{ $row['nama'] ?? '' }}" placeholder="cth: Juara 1 Pencak Silat Provinsi 2025"
                                                       class="field" />
                                                @error("sertifikat.{$i}.nama")<p class="field-error">{{ $message }}</p>@enderror
                                            </div>
                                            <div>
                                                <label for="sertifikat-{{ $i }}-file" class="block text-sm font-semibold text-ink-700 mb-2">File Sertifikat <span aria-hidden="true" class="error-text">*</span><span class="sr-only"> (wajib diisi)</span></label>
                                                <input type="file" id="sertifikat-{{ $i }}-file" name="sertifikat[{{ $i }}][file]" accept=".pdf,.jpg,.jpeg,.png"
                                                       class="field file" />
                                                @error("sertifikat.{$i}.file")<p class="field-error">{{ $message }}</p>@enderror
                                            </div>
                                            <button type="button" class="btn-remove sertifikat-remove inline-flex h-11 w-11 items-center justify-center rounded-md font-semibold" aria-label="Hapus baris sertifikat ini" title="Hapus baris">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                            </button>
                                        </div>
                                    @empty
                                        <div class="sertifikat-row grid grid-cols-1 gap-4 rounded-md border border-kertas-300 bg-kertas-100 p-4 md:grid-cols-[1fr_1fr_auto] md:items-end">
                                            <div>
                                                <label for="sertifikat-0-nama" class="block text-sm font-semibold text-ink-700 mb-2">Nama Kejuaraan <span aria-hidden="true" class="error-text">*</span><span class="sr-only"> (wajib diisi)</span></label>
                                                <input type="text" id="sertifikat-0-nama" name="sertifikat[0][nama]" value="" placeholder="cth: Juara 1 Pencak Silat Provinsi 2025"
                                                       class="field" />
                                            </div>
                                            <div>
                                                <label for="sertifikat-0-file" class="block text-sm font-semibold text-ink-700 mb-2">File Sertifikat <span aria-hidden="true" class="error-text">*</span><span class="sr-only"> (wajib diisi)</span></label>
                                                <input type="file" id="sertifikat-0-file" name="sertifikat[0][file]" accept=".pdf,.jpg,.jpeg,.png"
                                                       class="field file" />
                                            </div>
                                            <button type="button" class="btn-remove sertifikat-remove inline-flex h-11 w-11 items-center justify-center rounded-md font-semibold" aria-label="Hapus baris sertifikat ini" title="Hapus baris">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                            </button>
                                        </div>
                                    @endforelse
                                </div>
                                <button type="button" id="sertifikat-add"
                                        class="mt-4 inline-flex min-h-11 items-center gap-2 rounded-md border-2 border-dashed border-primary-300 bg-white px-5 text-sm font-semibold text-primary-800 transition-colors hover:bg-primary-50">
                                    + Tambah Sertifikat (maks 5)
                                </button>
                                @error('sertifikat')<p class="field-error">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        <div class="flex justify-between items-center">
                            <button type="button" data-prev="3"
                                    class="inline-flex min-h-11 items-center rounded-md border border-kertas-300 bg-kertas-100 px-5 text-sm font-semibold text-ink-700 transition-colors hover:bg-kertas-200 hover:border-ink-400">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    @foreach ($renderIcon('arrow-left') as $d)
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $d }}"/>
                                    @endforeach
                                </svg>
                                Kembali
                            </button>
                            {{-- `data-kirim` menandai tombol ini sebagai target
                                 status yang terlihat. Tanpa itu, orang tua menekan
                                 "Kirim Pendaftaran", tombol tetap berbunyi dan
                                 tidak terjadi apa-apa selama beberapa detik
                                 selagi berkas diunggah. Beberapa orang akan
                                 menekan dua kali, dan dua kali submit berarti
                                 dua pendaftar. --}}
                            <button type="submit" data-kirim class="btn btn-primary w-full text-base sm:w-auto">
                                <span data-kirim-siapa class="contents">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        @foreach ($renderIcon('paper-plane') as $d)
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $d }}"/>
                                        @endforeach
                                    </svg>
                                    Kirim Pendaftaran
                                </span>

                                {{-- Spinner pakai border-currentColor supaya warnanya
                                     mengikuti tombolnya sendiri, tanpa warna
                                     tambahan yang tidak ada di palet. --}}
                                <span data-kirim-duduk hidden class="contents">
                                    <svg class="h-5 w-5 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="3" opacity="0.25"/>
                                        <path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round"/>
                                    </svg>
                                    Mengirim, jangan tutup halaman
                                </span>
                            </button>
                        </div>
                        {{-- Kalimat versi lama menyebut "Anda menyetujui syarat
                             dan ketentuan yang berlaku". Halaman syarat dan
                             ketentuan itu tidak ada di route mana pun, jadi
                             kalimat itu meminta persetujuan atas dokumen yang
                             tidak bisa dibaca pendaftar. Yang
                             tersisa hanya territory yang sebenarnya: data
                             yang dikirim dipakai untuk memproses pendaftaran. --}}
                        <p class="mt-4 text-center text-sm leading-relaxed text-ink-500">
                            Data yang dikirim dipakai sekolah untuk memproses pendaftaran
                            dan menghubungi kamu kalau ada berkas yang perlu diperbaiki.
                        </p>
                    </div>
                </form>
            </div>
        </div>
    </section>

    {{-- ============ CTA BANTUAN ============ --}}
    <section class="bg-kertas-200 py-16 sm:py-20">
        <div class="mx-auto max-w-4xl px-5 sm:px-6">
            <h2 class="reveal font-display text-2xl font-extrabold leading-tight text-ink-900 sm:text-3xl">
                Kendala saat mengisi?
            </h2>
            <p class="reveal mt-4 max-w-xl text-base leading-relaxed text-ink-600">
                Kalau ada yang tidak jelas atau formulirnya tidak bisa dikirim,
                hubungi sekolah langsung. Lebih baik ditanyakan sekarang
                daripada berkasnya ditolak karena salah kolom.
            </p>

            {{-- Tombol kontak dirender hanya kalau memang ada nomornya. Kalau
                 sekolah belum mengisi kontak, section ini lenyap saja, bukan
                 menampilkan tombol yang tidak punya tujuan. --}}
            @if ($kontakTelpTel || $kontakWhatsapp || $kontakEmail)
                <div class="mt-7 flex flex-col gap-3 sm:flex-row sm:flex-wrap">
                    @if ($kontakTelpTel)
                        <a href="tel:{{ $kontakTelpTel }}" class="btn btn-primary">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                @foreach ($renderIcon('phone') as $d)
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $d }}"/>
                                @endforeach
                            </svg>
                            Telepon {{ $kontakTelp }}
                        </a>
                    @endif

                    @if ($kontakWhatsapp)
                        <a href="https://wa.me/{{ $kontakWhatsapp }}" target="_blank" rel="noopener" class="btn btn-quiet">
                            WhatsApp Sekolah
                        </a>
                    @endif

                    @if ($kontakEmail)
                        <a href="mailto:{{ $kontakEmail }}" class="btn btn-quiet">
                            {{ $kontakEmail }}
                        </a>
                    @endif
                </div>
            @endif
        </div>
    </section>

    <script>
        (function () {
            const form = document.getElementById('form-wizard');
            const totalSteps = {{ count($stepLabels) }};
            let current = 1;

            function stepEl(n) {
                return form.querySelector(`[data-step="${n}"]`);
            }

            function validateStep(n) {
                const fields = stepEl(n).querySelectorAll('input[required], select[required], textarea[required]');
                let valid = true;
                fields.forEach((f) => {
                    // `aria-invalid` dibaca oleh CSS `.field[aria-invalid="true"]`
                    // dan dibaca juga oleh pembaca layar, jadi penanda galat
                    // tidak hanya bergantung pada warna garis (R-25, C-4).
                    if (!f.checkValidity()) {
                        f.classList.add('is-invalid');
                        f.setAttribute('aria-invalid', 'true');
                        valid = false;
                    } else {
                        f.classList.remove('is-invalid');
                        f.removeAttribute('aria-invalid');
                    }
                });
                if (!valid) fields[0]?.reportValidity();
                return valid;
            }

            // Tiga keadaan: sudah lewat (terang, tanda sudah dikerjakan),
            // sedang aktif (cincin), dan belum dicapai (redup). Warna
            // sendirian tidak membedakan ketiganya, bedanya juga ada di
            // cincin dan di teks label.
            function updateIndicators() {
                form.querySelectorAll('[data-step-indicator]').forEach((wrap) => {
                    const num = parseInt(wrap.dataset.stepIndicator, 10);
                    const circle = wrap.querySelector('.step-circle');
                    const label = wrap.querySelector('span');

                    circle.classList.remove(
                        'bg-primary-700', 'bg-primary-600', 'bg-primary-100', 'bg-kertas-300',
                        'text-white', 'text-primary-800', 'text-ink-600',
                        'ring-2', 'ring-primary-700', 'ring-offset-2'
                    );
                    label?.classList.remove('text-primary-800', 'text-primary-600', 'text-ink-500');

                    if (num < current) {
                        circle.classList.add('bg-primary-100', 'text-primary-800');
                        label?.classList.add('text-primary-800');
                    } else if (num === current) {
                        circle.classList.add('bg-primary-700', 'text-white', 'ring-2', 'ring-primary-700', 'ring-offset-2');
                        label?.classList.add('text-primary-800');
                    } else {
                        circle.classList.add('bg-kertas-300', 'text-ink-600');
                        label?.classList.add('text-ink-500');
                    }
                });
            }

            function showStep(n, opsi = {}) {
                for (let i = 1; i <= totalSteps; i++) {
                    const el = stepEl(i);
                    if (!el) continue;
                    el.classList.toggle('hidden', i !== n);
                }

                // Fokus pindah ke kepala langkah supaya pengguna keyboard
                // tahu kalau langkahnya sudah berganti, bukan hanya melihat layar
                // berubah. Judul langkah dibuat bisa difokus lewat tabindex -1.
                if (!opsi.skipFocus) {
                    const judul = stepEl(n)?.querySelector('h3');
                    if (judul) {
                        judul.setAttribute('tabindex', '-1');
                        judul.focus({ preventScroll: true });
                    }
                }

                current = n;
                updateIndicators();

                if (!opsi.skipScroll) {
                    stepEl(n).scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            }

            form.querySelectorAll('[data-next]').forEach((btn) => {
                btn.addEventListener('click', () => {
                    const fromStep = current;
                    const toStep = parseInt(btn.dataset.next, 10);
                    if (validateStep(fromStep)) showStep(toStep);
                });
            });

            form.querySelectorAll('[data-prev]').forEach((btn) => {
                btn.addEventListener('click', () => showStep(parseInt(btn.dataset.prev, 10)));
            });

            // Sertifikat prestasi dinamis (wajib bila jalur ber-flag)
            const jalurSelect = document.getElementById('jalur_pendaftaran_id');
            const sertSection = document.getElementById('sertifikat-section');
            const sertRows = document.getElementById('sertifikat-rows');
            const sertAdd = document.getElementById('sertifikat-add');
            // Nomor baris berikutnya dihitung dari index terbesar yang sudah
            // dipakai, bukan dari jumlah baris. Setelah validasi gagal, kunci
            // `sertifikat` bisa bolong (mis. 0 dan 3), lalu baris baru akan
            // menabrak `id` milik baris lama.
            const sertIndex = sertRows
                .querySelectorAll('input[name^="sertifikat["]')
                .reduce((max, inp) => {
                    const n = parseInt(inp.name.match(/sertifikat\[(\d+)\]/)?.[1] ?? '-1', 10);
                    return Number.isNaN(n) ? max : Math.max(max, n + 1);
                }, 0);

            function sertifikatWajib() {
                const opt = jalurSelect.selectedOptions[0];
                return !!(opt && opt.dataset.wajibSertifikat === '1');
            }

            // Sisa kuota pendaftaran ikut ditampilkan agar pendaftar tahu
            // kapasitas sebelum mengisi formulir panjang.
            function updateInfoKuota() {
                const info = document.getElementById('info-kuota');
                const opt = jalurSelect.selectedOptions[0];

                if (!info) return;

                if (!opt || !opt.value) {
                    info.textContent = 'Pilih jalur untuk melihat sisa kuota pendaftaran.';
                    return;
                }

                if (opt.disabled) {
                    info.textContent = 'Jalur ini sudah penuh dan tidak menerima pendaftar baru.';
                    return;
                }

                const sisa = opt.dataset.sisa;
                const kapasitas = opt.dataset.kapasitas;

                info.textContent = (sisa === '' || kapasitas === '')
                    ? 'Pendaftaran untuk jalur ini tidak dibatasi jumlahnya.'
                    : `Sisa ${sisa} dari ${kapasitas} kursi pendaftaran.`;
            }

            function toggleSertifikat() {
                const wajib = sertifikatWajib();
                updateInfoKuota();
                sertSection.classList.toggle('hidden', !wajib);
                sertRows.querySelectorAll('.sertifikat-row').forEach((row) => {
                    row.querySelectorAll('input').forEach((inp) => {
                        inp.required = wajib;
                        // Input yang dinonaktifkan tidak ikut terkirim ke server.
                        // Tanpa ini browser tetap mengirim baris kosong dan
                        // validasi sertifikat tetap gagal walau jalur tidak mewajibkannya.
                        inp.disabled = !wajib;
                        if (!wajib) inp.value = '';
                    });
                });
            }

            function refreshSertifikatButtons() {
                const rows = sertRows.querySelectorAll('.sertifikat-row');
                rows.forEach((row) => {
                    row.querySelector('.sertifikat-remove').style.display = rows.length > 1 ? '' : 'none';
                });
                sertAdd.disabled = rows.length >= 5;
                sertAdd.classList.toggle('opacity-50', rows.length >= 5);
            }

            sertAdd.addEventListener('click', () => {
                if (sertRows.querySelectorAll('.sertifikat-row').length >= 5) return;
                const idx = sertIndex++;
                const div = document.createElement('div');
                div.className = 'sertifikat-row grid grid-cols-1 gap-4 rounded-md border border-kertas-300 bg-kertas-100 p-4 md:grid-cols-[1fr_1fr_auto] md:items-end';
                div.innerHTML =
                    `<div><label for="sertifikat-${idx}-nama" class="block text-sm font-semibold text-ink-700 mb-2">Nama Kejuaraan <span aria-hidden="true" class="error-text">*</span><span class="sr-only"> (wajib diisi)</span></label>` +
                    `<input type="text" id="sertifikat-${idx}-nama" name="sertifikat[${idx}][nama]" placeholder="cth: Juara 1 Pencak Silat Provinsi 2025" class="field" /></div>` +
                    `<div><label for="sertifikat-${idx}-file" class="block text-sm font-semibold text-ink-700 mb-2">File Sertifikat <span aria-hidden="true" class="error-text">*</span><span class="sr-only"> (wajib diisi)</span></label>` +
                    `<input type="file" id="sertifikat-${idx}-file" name="sertifikat[${idx}][file]" accept=".pdf,.jpg,.jpeg,.png" class="field file" /></div>` +
                    `<button type="button" class="btn-remove sertifikat-remove inline-flex h-11 w-11 items-center justify-center rounded-md font-semibold" aria-label="Hapus baris sertifikat ini" title="Hapus baris">` +
                    `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>`;
                sertRows.appendChild(div);
                if (sertifikatWajib()) div.querySelectorAll('input').forEach((inp) => { inp.required = true; });
                refreshSertifikatButtons();
            });

            sertRows.addEventListener('click', (e) => {
                const btn = e.target.closest('.sertifikat-remove');
                if (!btn || sertRows.querySelectorAll('.sertifikat-row').length <= 1) return;
                btn.closest('.sertifikat-row').remove();
                refreshSertifikatButtons();
            });

            jalurSelect.addEventListener('change', toggleSertifikat);
            toggleSertifikat();
            refreshSertifikatButtons();
            updateInfoKuota();

            // Preview file upload
            form.querySelectorAll('input[type="file"]').forEach((input) => {
                input.addEventListener('change', function () {
                    const file = this.files[0];
                    const wrap = this.closest('.border-dashed');
                    const hint = wrap.querySelector('.text-sm.text-ink-600');
                    if (file) {
                        // Status "sudah dipilih" ditandai lewat garis, latar, dan
                        // nama file yang tercetak. Warna saja tidak cukup, karena
                        // tidak terlihat oleh pengguna yang membedakan warna
                        // dengan buruk (C-4).
                        wrap.classList.remove('border-ink-400', 'hover:border-primary-600');
                        wrap.classList.add('border-accent-600', 'bg-accent-50');
                        if (hint) {
                            hint.className = 'text-sm text-ink-700';
                            hint.innerHTML = `<strong class="text-accent-700">Dipilih: ${file.name}</strong>`;
                        }
                    }
                });
            });

            // Inisialisasi: tampilkan langkah pertama dan samakan indikatornya.
            // Sengaja tidak memakai showStep() karena fungsi itu memindahkan
            // fokus dan menggulir ke langkah, dan itu tidak pantas dijalankan
            // saat halaman baru dibuka.
            showStep(current, { skipScroll: true, skipFocus: true });
        })();

        /*
         * Status tombol kirim.
         *
         * Mengirim berkas di jaringan sekolah bisa memakan beberapa detik,
         * dan selama itu tidak ada yang terjadi di layar. Tanpa perubahan
         * apa pun, orang tua menekan ulang. Dua kali submit berarti dua
         * pendaftar dengan data sama, dan yang kedua biasanya gagal dengan
         * pesan yang tidak jelas.
         *
         * Yang dijaga di sini:
         *
         * - Tombol dinonaktifkan, bukan hanya diganti teksnya. Tombol yang
         *   masih bisa diklik tapi tidak terlihat berubahnya akan tetap
         *   ditekan orang tua yang tidak sabar.
         * - Pesannya menyebut "jangan tutup halaman" karena inilah satu
         *   kasus yang benar-benar merusak: menutup tab di tengah unggahan
         *   berarti berkas hilang dan tidak ada jalan untuk mengetahuinya.
         * - Tidak ada pemulihan state di sini, dan tidak perlu. Form ini
         *   mengirim lewat navigasi halaman biasa, bukan fetch, jadi setiap
         *   jawaban server memuat ulang halaman dengan tombol yang utuh.
         *   Membbekukan tombol juga akan salah kalau browser mengembalikan
         *   halaman dari cache, jadi tombol tetap bisa diklik lagi di sana.
         */
        (function () {
            const form = document.getElementById('form-wizard');

            if (!form) return;

            const tombol = form.querySelector('[data-kirim]');

            if (!tombol) return;

            const labelSiapa = tombol.querySelector('[data-kirim-siapa]');
            const labelDuduk = tombol.querySelector('[data-kirim-duduk]');

            form.addEventListener('submit', function () {
                tombol.disabled = true;

                if (labelSiapa) labelSiapa.hidden = true;
                if (labelDuduk) labelDuduk.hidden = false;
            });
        })();
    </script>

@endsection
