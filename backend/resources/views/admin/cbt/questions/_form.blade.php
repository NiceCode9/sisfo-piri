{{--
  Form tambah soal CBT — dipakai bersama oleh halaman bank soal dan detail
  ujian, supaya keduanya tidak pernah berbeda.

  Dua hal yang dulu jadi sumber bug:

  1. Form bank TIDAK punya field opsi maupun kunci jawaban, padahal validasi
     kunci (M4) mewajibkannya untuk soal pilihan. Guru tidak punya kolom untuk
     memperbaikinya, jadi setiap soal pilihan gagal tersimpan.
  2. Saat tipe diubah ke uraian, baris opsi disembunyikan — tapi tetap terkirim
     sebagai string kosong. Middleware ConvertEmptyStringsToNull mengubahnya
     jadi null, lalu aturan `required_with:options` menolaknya.

  Karena itu input yang tidak aktif harus benar-benar di-DISABLE, bukan hanya
  disembunyikan agar tidak ikut terkirim.
--}}
<div class="row g-2">
  <div class="col-12">
    <textarea name="question_text" class="form-control form-control-sm" rows="2"
              placeholder="Teks soal" required>{{ old('question_text') }}</textarea>
  </div>

  <div class="col-6 col-md-3">
    <div class="form-floating">
      <select name="type" id="soal-tipe" class="form-select form-select-sm">
        <option value="single_choice" @selected(old('type', 'single_choice') === 'single_choice')>Pilihan Tunggal</option>
        <option value="multiple_choice" @selected(old('type') === 'multiple_choice')>Pilihan Jamak</option>
        <option value="essay" @selected(old('type') === 'essay')>Uraian</option>
      </select>
      <label>Tipe</label>
    </div>
  </div>

  <div class="col-6 col-md-3">
    <input type="number" name="score" value="{{ old('score', 10) }}" min="1" max="100"
           class="form-control form-control-sm" placeholder="Poin" required />
  </div>

  <div class="col-6 col-md-2">
    <input type="number" name="order" value="{{ old('order', 0) }}" min="0"
           class="form-control form-control-sm" placeholder="Urutan" />
  </div>

  <div class="col-6 col-md-4">
    <input type="file" name="question_image" class="form-control form-control-sm"
           accept="image/jpeg,image/png,image/webp" />
  </div>

  {{-- Opsi + kunci. disembunyikan sepenuhnya untuk soal uraian. --}}
  <div class="col-12" id="soal-opsi">
    <div class="d-flex justify-content-between align-items-center mb-1">
      <span style="font-size:12px;font-weight:600;">Opsi &amp; kunci jawaban</span>
      <button type="button" class="btn btn-nexus-outline btn-sm" id="soal-tambah-opsi">+ Tambah opsi</button>
    </div>

    <div id="soal-daftar-opsi" class="d-flex flex-column gap-2"></div>

    <p class="mb-0 mt-1" style="font-size:11px;color:var(--text-muted);">
      Pilih satu kunci untuk soal pilihan tunggal; dua atau lebih untuk soal pilihan jamak.
    </p>
  </div>
</div>

{{-- Nama field kunci selalu `correct_answer[]` walau tipenya radio, supaya
     nilainya selalu array. Kalau radio memakai nama polos, PHP mengirim string
     dan aturan validasi `array` akan menolaknya. --}}
<template id="soal-templat-opsi">
  <div class="d-flex gap-2 align-items-center">
    <input type="hidden" data-kunci-nilai name="options[0][key]" value="a" />
    <span class="badge-nexus" data-huruf>A</span>
    <input type="text" data-teks name="options[0][text]" class="form-control form-control-sm" placeholder="Teks opsi" />
    <div class="form-check mb-0 ms-auto">
      <input class="form-check-input" type="checkbox" data-kunci name="correct_answer[]" id="kunci-0" />
      <label class="form-check-label" for="kunci-0" style="font-size:12px;">kunci</label>
    </div>
    <button type="button" class="btn btn-sm text-danger" data-hapus aria-label="Hapus opsi">&times;</button>
  </div>
</template>

<script>
(function () {
    var tipe = document.getElementById('soal-tipe');
    var blokOpsi = document.getElementById('soal-opsi');
    var daftar = document.getElementById('soal-daftar-opsi');
    var tombolTambah = document.getElementById('soal-tambah-opsi');
    var templat = document.getElementById('soal-templat-opsi');

    if (!tipe || !blokOpsi || !daftar || !templat) return;

    var HURUF = 'abcdefghijklmnopqrstuvwxyz';

    function tambahBaris(teks, kunciTercentang) {
        var node = templat.content.cloneNode(true);
        var baris = node.querySelector('.d-flex');

        baris.querySelector('[data-teks]').value = teks || '';
        if (kunciTercentang) baris.querySelector('[data-kunci]').checked = true;

        baris.querySelector('[data-hapus]').addEventListener('click', function () {
            baris.remove();
            nomorUlang();
        });

        daftar.appendChild(node);
    }

    function nomorUlang() {
        var jamak = tipe.value === 'multiple_choice';

        Array.prototype.forEach.call(daftar.children, function (baris, i) {
            var huruf = HURUF[i] || '?';

            baris.querySelector('[data-kunci-nilai]').name = 'options[' + i + '][key]';
            baris.querySelector('[data-kunci-nilai]').value = huruf;
            baris.querySelector('[data-teks]').name = 'options[' + i + '][text]';
            baris.querySelector('[data-huruf]').textContent = huruf.toUpperCase();

            var cek = baris.querySelector('[data-kunci]');
            // Nama tetap `correct_answer[]` untuk keduanya; hanya tipenya yang
            // berubah, supaya nilainya selalu array bagi validasi.
            cek.type = jamak ? 'checkbox' : 'radio';
            cek.value = huruf;
            cek.id = 'kunci-' + i;
            cek.nextElementSibling.setAttribute('for', 'kunci-' + i);
        });
    }

    function samakanTipe() {
        var uraian = tipe.value === 'essay';

        blokOpsi.classList.toggle('d-none', uraian);
        tombolTambah.disabled = uraian;

        // DISABLE, bukan sekadar disembunyikan. Kalau hanya disembunyikan, baris
        // tetap terkirim sebagai string kosong, ConvertEmptyStringsToNull
        // mengubahnya jadi null, lalu `required_with:options` menolaknya.
        Array.prototype.forEach.call(blokOpsi.querySelectorAll('input'), function (input) {
            input.disabled = uraian;
        });
    }

    // Muat ulang dari old() supaya validasi yang gagal tidak menghapus isian guru.
    var opsiLama = @json(old('options'));
    var kunciLama = @json(old('correct_answer', []));

    if (Array.isArray(opsiLama) && opsiLama.length) {
        opsiLama.forEach(function (opsi) {
            if (!opsi) return;
            tambahBaris(opsi.text, kunciLama && kunciLama.indexOf(opsi.key) !== -1);
        });
    }

    if (!daftar.children.length) {
        tambahBaris('', false);
        tambahBaris('', false);
    }

    tombolTambah.addEventListener('click', function () {
        tambahBaris('', false);
        nomorUlang();
    });

    tipe.addEventListener('change', function () {
        nomorUlang();
        samakanTipe();
    });

    nomorUlang();
    samakanTipe();
})();
</script>