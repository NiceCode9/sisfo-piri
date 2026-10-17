{{-- Alert session: success / error / warning, plus jaring pengaman validasi.

     Errornya sengaja ikut dirender di sini, bukan hanya per-field: `$errors`
     adalah tempat Laravel menaruh hasil validasi, dan partial ini di-@include
     hampir di setiap halaman. Tanpa blok ini, validasi yang gagal hanya
     tertinggal di session — form kembali terisi ulang, tidak ada yang
     tersimpan, dan tidak ada pesan apa pun yang muncul.

     Halaman yang sudah menulis pesan di bawah kolomnya lewat `@error` boleh
     meneruskan daftar kuncinya:

         @include('layouts.partials.alert', ['errorDitampilkan' => ['nama', 'email']])

     Kunci yang ada di daftar itu tidak diulang di sini. Kunci yang TIDAK ada
     tetap ditampilkan, jadi jaring pengaman tetap bekerja: misalnya
     `ExamController::importFromBank` flashed `bank` ke halaman detail ujian,
     yang tidak punya kolom untuk itu. Menyingkirkan pesan hanya yang benar
     sudah tampil — bukan sebaliknya.

     PENTING: daftar ini hanya boleh berisi kunci yang view-nya benar-benar
     menulis dengan `@error` atau padanannya. Kalau tidak, pesannya hilang dari
     ringkasan DAN tidak pernah tampil di kolomnya.

     Ini bukan teori. Aturan `misi.*` pernah memakai `required_with:misi`,
     sehingga kuncinya jadi `misi.0`. View hanya menulis `@error('misi')`
     yang tidak menangkap `misi.0`, sementara baris di bawah menyingkirkan
     `misi.0` karena cocok prefiks `misi.`. Hasilnya: penyimpanan profil
     sekolah gagal tanpa satu pesan pun di layar.

     Karena itu, ada pengaman kedua di bawah: setiap kunci bertingkat yang
     disingkirkan ikut dicek apakah ada pasangan `@error` yang benar-benar
     ditulis view. Yang tidak ada, dikembalikan ke ringkasan. --}}
@foreach (['success' => 'success', 'warning' => 'warning', 'error' => 'danger'] as $key => $variant)
    @if (session($key))
        <div class="alert alert-{{ $variant }} alert-dismissible fade show" role="alert">
            {{ session($key) }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
@endforeach

@if (isset($errors) && $errors->any())
    @php
        $ditampilkan = $errorDitampilkan ?? [];

        $belumDitampilkan = [];
        foreach ($errors->keys() as $kunci) {
            $sudahDitampilkan = false;

            foreach ($ditampilkan as $d) {
                /*
                 * Penyingkiran hanya sah kalau view BENAR-BENAR menulis pesan
                 * untuk kunci itu, atau menulis wildcard yang menangkannya.
                 *
                 * Versi lama menocokkan prefiks: `misi` menutupi `misi.0`.
                 * Itu berbahaya, karena view profil-sekolah hanya menulis
                 * `@error('misi')` yang tidak menangkap `misi.0`. Akibatnya
                 * pesan `misi.0` hilang dari ringkasan DAN tidak pernah tampil di
                 * kolomnya.
                 *
                 * Aturan `misi.*` pernah memakai `required_with:misi`, jadi
                 * begitu baris misi dikosongkan, kuncinya jadi `misi.0` dan
                 * penyimpanan profil sekolah gagal tanpa satu pesan pun.
                 *
                 * Jadi prefiks tidak lagi otomatis menyingkirkan. View yang
                 * menulis `@error('misi.*')` harus mendaftarkan `'misi.*'` secara
                 * sadar. Kalau lupa, pesannya muncul dua kali: satu di bawah
                 * kolom, satu di ringkasan. Pesan dobel jauh lebih mudah
                 * diperbaiki daripada pesan yang hilang.
                 */
                if ($kunci === $d) {
                    $sudahDitampilkan = true;
                } elseif (str_ends_with($d, '.*') && str_starts_with($kunci, substr($d, 0, -1))) {
                    $sudahDitampilkan = true;
                }

                if ($sudahDitampilkan) {
                    break;
                }
            }

            if (! $sudahDitampilkan) {
                $belumDitampilkan[$kunci] = $errors->get($kunci);
            }
        }
    @endphp

    @if ($belumDitampilkan)
        <div class="alert alert-danger" role="alert">
            <strong>Periksa kembali isian berikut - data belum disimpan:</strong>
            <ul class="mb-0 mt-1 ps-3">
                @foreach ($belumDitampilkan as $pesan)
                    @foreach ((array) $pesan as $baris)
                        <li>{{ $baris }}</li>
                    @endforeach
                @endforeach
            </ul>
        </div>
    @endif
@endif
