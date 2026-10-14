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
     sudah tampil — bukan sebaliknya. --}}
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
                // Cocok persis, atau `options` menutupi `options.0.key` —
                // kunci bertingkat dari validasi silang biasanya ditulis
                // wildcard di view.
                if ($kunci === $d || str_starts_with($kunci, $d.'.')) {
                    $sudahDitampilkan = true;
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
            <strong>Periksa kembali isian berikut — data belum disimpan:</strong>
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