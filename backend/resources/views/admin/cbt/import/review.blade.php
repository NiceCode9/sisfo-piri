@extends('layouts.app')

@section('title', 'Review Import Soal')
@section('breadcrumb', 'Review Import Soal')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
  <div>
    <h1 class="page-title">Review Import Soal</h1>
    <p class="page-subtitle">{{ $exam->name }}</p>
  </div>
  <a href="{{ route('admin.cbt.exams.show', $exam) }}" class="btn btn-nexus-outline btn-sm">Batal</a>
</div>

@include('layouts.partials.alert')

{{-- Baris yang gagal dibaca dari Excel sengaja TIDAK ditampilkan sebagai baris
     yang bisa disunting: sudah ketahuan salah apa yang salah, dan mengeditnya di
     sini hampir pasti mengulang kesalahan yang sama. Daftar alasannya ada di
     bawah supaya guru tahu apa yang harus diperbaiki di Excel. --}}
@if($gagal !== [])
  <div class="card-nexus mb-3" style="border-left:3px solid var(--error);">
    <div class="card-body-nexus">
      <h6 style="font-size:13px;font-weight:700;">{{ count($gagal) }} baris dilewati</h6>
      <ul style="font-size:12.5px;color:var(--text-secondary);margin:6px 0 0 0;">
        @foreach($gagal as $g)
          <li><strong>Baris {{ $g['baris'] }}</strong> — {{ $g['alasan'] }}</li>
        @endforeach
      </ul>
      <p style="font-size:11.5px;color:var(--text-muted);margin-top:6px;">
        Perbaiki di Excel lalu unggah ulang paketnya.
      </p>
    </div>
  </div>
@endif

<div class="card-nexus">
  <div class="card-body-nexus">
    <form method="POST" action="{{ route('admin.cbt.import.simpan', ['exam' => $exam->id, 'token' => $token]) }}" id="form-review">
      @csrf

      <div class="d-flex justify-content-between align-items-center mb-2">
        <span style="font-size:13px;font-weight:600;" id="ringkasan">0 soal siap disimpan</span>
        <label style="font-size:12.5px;">
          <input type="checkbox" id="pilih-semua" /> Pilih semua
        </label>
      </div>

      <div class="table-responsive">
        <table class="table-nexus w-100" style="font-size:12.5px;">
          <thead>
            <tr>
              <th style="width:34px;"></th>
              <th style="width:44px;">Baris</th>
              <th>Soal</th>
              <th style="width:78px;">Poin</th>
              <th style="width:96px;">Gambar</th>
              <th style="width:56px;"></th>
            </tr>
          </thead>
          <tbody id="isi-draft">
            @foreach($drafts as $i => $d)
              <tr data-draft>
                <td><input type="checkbox" checked data-pakai /></td>
                <td style="color:var(--text-muted);">{{ $d['baris'] }}</td>
                <td>
                  <textarea name="rows[{{ $i }}][question_text]" data-teks rows="2" class="form-control form-control-sm">{{ $d['question_text'] }}</textarea>

                  <input type="hidden" name="rows[{{ $i }}][type]" value="{{ $d['type'] }}" data-tipe />
                  <input type="hidden" name="rows[{{ $i }}][gambar]" value="{{ $d['gambar'] }}" data-gambar />
                  <input type="hidden" name="rows[{{ $i }}][order]" value="{{ $d['order'] }}" data-urutan />

                  @if($d['type'] === 'essay')
                    <div class="mt-1" style="font-size:11.5px;color:var(--text-muted);">
                      Uraian — dinilai guru
                    </div>
                  @else
                    @foreach($d['options'] as $n => $o)
                      {{-- Opsi & kunci dikirim sebagai input tersembunyi. Yang
                           menentukan terkirim atau tidak adalah atribut
                           `disabled` — inilah yang membuat baris yang dibuang
                           atau kuncinya dilepas tidak ikut masuk. --}}
                      <input type="hidden" name="rows[{{ $i }}][options][{{ $n }}][key]" value="{{ $o['key'] }}" />
                      <input type="hidden" name="rows[{{ $i }}][options][{{ $n }}][text]" value="{{ $o['text'] }}" />
                      <input type="hidden" name="rows[{{ $i }}][correct_answer][]" value="{{ $o['key'] }}"
                             data-kunci="{{ $o['key'] }}"
                             @disabled(! in_array($o['key'], $d['correct_answer'])) />
                    @endforeach

                    <div class="mt-1" style="font-size:11.5px;color:var(--text-muted);">
                      @foreach($d['options'] as $o)
                        <span class="badge-nexus mb-1" data-chip="{{ $o['key'] }}" title="{{ $o['text'] }}"
                              style="cursor:pointer;{{ in_array($o['key'], $d['correct_answer']) ? 'outline:2px solid var(--accent-primary);' : '' }}">
                          {{ strtoupper($o['key']) }}
                        </span>
                      @endforeach
                      <span data-teks-kunci> — kunci: {{ empty($d['correct_answer']) ? 'belum' : implode(', ', $d['correct_answer']) }}</span>
                    </div>
                  @endif
                </td>
                <td><input type="number" name="rows[{{ $i }}][score]" data-skor value="{{ $d['score'] }}" min="1" max="100" class="form-control form-control-sm" /></td>
                <td>
                  @if($d['gambar_ada'] && $d['gambar_url'])
                    <img src="{{ $d['gambar_url'] }}" alt="" style="max-width:90px;max-height:60px;" />
                  @elseif($d['gambar'] !== '')
                    <span class="badge-nexus" style="background:var(--error);color:#fff;">Tidak ditemukan</span>
                  @else
                    <span style="color:var(--text-muted);">—</span>
                  @endif
                </td>
                <td><button type="button" class="btn btn-sm text-danger" data-buang title="Buang baris">&times;</button></td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>

      <div class="row g-2 mt-3 align-items-end">
        <div class="col-md-5">
          <label class="form-label" style="font-size:12.5px;">Salin juga ke bank soal</label>
          <select name="bank_id" class="form-select form-select-sm" required>
            <option value="">— pilih bank —</option>
            @foreach($banks as $b)
              <option value="{{ $b->id }}">{{ $b->nama }}</option>
            @endforeach
          </select>
          <div class="form-text" style="font-size:11.5px;">
            Bank soal milik Anda pada mata pelajaran yang sama. Buat bank baru dari menu Bank Soal bila belum ada.
          </div>
        </div>
        <div class="col-md-7 d-flex gap-2 justify-content-end">
          <a href="{{ route('admin.cbt.exams.show', $exam) }}" class="btn btn-nexus-outline btn-sm">Batal</a>
          <button type="submit" class="btn btn-primary btn-sm" id="tombol-simpan">Simpan soal</button>
        </div>
      </div>
    </form>
  </div>
</div>

<script>
(function () {
    var tbody = document.getElementById('isi-draft');
    var ringkasan = document.getElementById('ringkasan');
    var tombol = document.getElementById('tombol-simpan');
    var pilihSemua = document.getElementById('pilih-semua');

    if (!tbody) return;

    function baris() { return Array.prototype.slice.call(tbody.querySelectorAll('[data-draft]')); }

    function segarkan() {
        var hidup = baris().filter(function (b) { return b.querySelector('[data-pakai]').checked; });
        ringkasan.textContent = hidup.length + ' soal siap disimpan';
        tombol.textContent = hidup.length ? 'Simpan ' + hidup.length + ' soal' : 'Simpan soal';
    }

    // Mengklik chip opsi memutar status kuncinya lewat atribut `disabled` pada
    // input tersembunyi — bukan JSON, supaya tidak perlu diserialisasi ulang
    // saat form dikirim.
    tbody.addEventListener('click', function (e) {
        var chip = e.target.closest('[data-chip]');
        if (!chip) return;

        e.preventDefault();

        var barisSoal = chip.closest('[data-draft]');
        var kunci = chip.getAttribute('data-chip');
        var input = barisSoal.querySelector('[data-kunci="' + kunci + '"]');

        if (!input) return;

        input.disabled = !input.disabled;
        chip.style.outline = input.disabled ? '' : '2px solid var(--accent-primary)';

        var aktif = barisSoal.querySelectorAll('[data-kunci]:not([disabled])');
        var daftar = Array.prototype.map.call(aktif, function (el) { return el.getAttribute('data-kunci'); });
        var teks = barisSoal.querySelector('[data-teks-kunci]');

        if (teks) teks.textContent = ' — kunci: ' + (daftar.length ? daftar.join(', ') : 'belum');
    });

    tbody.addEventListener('click', function (e) {
        if (!e.target.closest('[data-buang]')) return;
        var b = e.target.closest('[data-draft]');
        b.remove();
        segarkan();
    });

    tbody.addEventListener('change', function (e) {
        if (e.target.closest('[data-pakai]')) segarkan();
    });

    if (pilihSemua) {
        pilihSemua.addEventListener('change', function () {
            baris().forEach(function (b) { b.querySelector('[data-pakai]').checked = pilihSemua.checked; });
            segarkan();
        });
    }

    document.getElementById('form-review').addEventListener('submit', function () {
        // Baris yang dibuang tidak boleh ikut terkirim.
        baris().forEach(function (b) {
            if (!b.querySelector('[data-pakai]').checked) {
                b.querySelectorAll('input, textarea').forEach(function (el) { el.disabled = true; });
            }
        });
        tombol.disabled = true;
        tombol.textContent = 'Menyimpan...';
    });

    segarkan();
})();
</script>
@endsection