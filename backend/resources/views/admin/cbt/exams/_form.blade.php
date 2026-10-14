{{--
  Form bersama halaman Buat Ujian dan Edit Ujian.

  Sebelumnya keduanya ditulis terpisah dan langsung berbeda: `max_violation_count`
  tidak ada di keduanya padahal `ExamController` mewajibkannya, jadi kedua form
  gagal tersimpan tanpa pesan apa pun — dan Edit Ujian juga menghapus deskripsi
  serta durasi karena kolomnya tidak pernah dikirim. Partial ini menutup kelas
  regresi itu: satu daftar field, satu tempat aturan tampilnya.

  Aturan validasi yang diuji controller ada di
  `ExamController::store()` / `::update()`. Kalau aturan di sana berubah, ubah
  juga kolomnya di sini.
--}}
<div class="row g-3 mb-3">
    <div class="col-12 col-sm-4">
        <div class="form-floating">
            <select name="rombel_id" id="rombel_id" class="form-select @error('rombel_id') is-invalid @enderror" required>
                <option value="">— Rombel —</option>
                @foreach($rombels as $r)
                    <option value="{{ $r->id }}" @selected(old('rombel_id', $exam->rombel_id ?? '') == $r->id)>{{ $r->kelas->nama_kelas }} — {{ $r->tahunAjaran->nama_tahun_ajaran }}</option>
                @endforeach
            </select>
            <label for="rombel_id">Rombel <span class="text-danger">*</span></label>
            @error('rombel_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>

    <div class="col-12 col-sm-4">
        <div class="form-floating">
            <select name="mata_pelajaran_id" id="mata_pelajaran_id" class="form-select @error('mata_pelajaran_id') is-invalid @enderror" required>
                <option value="">— Mata Pelajaran —</option>
                @foreach($mapels as $m)
                    <option value="{{ $m->id }}" @selected(old('mata_pelajaran_id', $exam->mata_pelajaran_id ?? '') == $m->id)>{{ $m->nama }} ({{ $m->kode }})</option>
                @endforeach
            </select>
            <label for="mata_pelajaran_id">Mata Pelajaran <span class="text-danger">*</span></label>
            @error('mata_pelajaran_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>

    <div class="col-12 col-sm-4">
        <div class="form-floating">
            <input type="text" name="name" id="name" value="{{ old('name', $exam->name ?? '') }}" class="form-control @error('name') is-invalid @enderror" maxlength="150" required />
            <label for="name">Nama Ujian <span class="text-danger">*</span></label>
            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>

    <div class="col-12">
        <div class="form-floating">
            <textarea name="description" id="description" class="form-control @error('description') is-invalid @enderror" style="height:80px">{{ old('description', $exam->description ?? '') }}</textarea>
            <label for="description">Deskripsi</label>
            @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>

    <div class="col-12 col-sm-3">
        <div class="form-floating">
            {{-- min/max di sini harus sama dengan aturan `min:1','max:300` — bukan
                 angka tetap: validasi server yang menolak, dan pesan errornya
                 yang muncul di bawah kolom ini. --}}
            <input type="number" name="duration_minutes" id="duration_minutes" value="{{ old('duration_minutes', $exam->duration_minutes ?? 60) }}" class="form-control @error('duration_minutes') is-invalid @enderror" min="1" max="300" required />
            <label for="duration_minutes">Durasi (menit) <span class="text-danger">*</span></label>
            @error('duration_minutes')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>

    <div class="col-12 col-sm-3">
        <div class="form-floating">
            {{-- Field ini tidak pernah ada di form lama, padahal controller mewajibkannya.
                 Itulah sebabnya "Buat Ujian" selalu gagal tersimpan. --}}
            <input type="number" name="max_violation_count" id="max_violation_count" value="{{ old('max_violation_count', $exam->max_violation_count ?? 3) }}" class="form-control @error('max_violation_count') is-invalid @enderror" min="1" max="100" required />
            <label for="max_violation_count">Batas Pelanggaran <span class="text-danger">*</span></label>
            @error('max_violation_count')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>

    <div class="col-12 col-sm-3">
        <div class="form-floating">
            <input type="datetime-local" name="available_from" id="available_from" value="{{ old('available_from', isset($exam->available_from) ? $exam->available_from->format('Y-m-d\TH:i') : '') }}" class="form-control @error('available_from') is-invalid @enderror" />
            <label for="available_from">Mulai</label>
            @error('available_from')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>

    <div class="col-12 col-sm-3">
        <div class="form-floating">
            <input type="datetime-local" name="available_until" id="available_until" value="{{ old('available_until', isset($exam->available_until) ? $exam->available_until->format('Y-m-d\TH:i') : '') }}" class="form-control @error('available_until') is-invalid @enderror" />
            <label for="available_until">Selesai</label>
            @error('available_until')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>

    <div class="col-12 col-sm-4">
        <div class="form-floating">
            <select name="status" id="status" class="form-select @error('status') is-invalid @enderror" required>
                @foreach(['draft' => 'Draft', 'published' => 'Published', 'archived' => 'Archived'] as $value => $label)
                    <option value="{{ $value }}" @selected(old('status', $exam->status ?? 'draft') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <label for="status">Status <span class="text-danger">*</span></label>
            @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>

    {{-- Dua sakelar di bawah ikut dikembalikan ke form. Sebelumnya keduanya tidak
         dikirim sama sekali, dan `update()` memakai
         `$request->boolean('shuffle_questions')` — nilai kosong diartikan false,
         jadi setiap Edit Ujian diam-diam mematikan pengacakan yang sebelumnya aktif.
         Hidden "0" di depan checkbox yang tidak dicentang agar nilainya tetap terkirim. --}}
    <div class="col-12 col-sm-4">
        <div class="form-check form-switch mt-2">
            <input type="hidden" name="shuffle_questions" value="0" />
            <input type="checkbox" name="shuffle_questions" value="1" class="form-check-input @error('shuffle_questions') is-invalid @enderror" id="shuffle_questions" @checked(old('shuffle_questions', $exam->shuffle_questions ?? true)) />
            <label class="form-check-label" for="shuffle_questions">Acak urutan soal</label>
            @error('shuffle_questions')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>

    <div class="col-12 col-sm-4">
        <div class="form-check form-switch mt-2">
            <input type="hidden" name="shuffle_options" value="0" />
            <input type="checkbox" name="shuffle_options" value="1" class="form-check-input @error('shuffle_options') is-invalid @enderror" id="shuffle_options" @checked(old('shuffle_options', $exam->shuffle_options ?? true)) />
            <label class="form-check-label" for="shuffle_options">Acak opsi jawaban</label>
            @error('shuffle_options')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
</div>

{{-- Remedial: menandai ujian ini sebagai pengganti nilai ujian lain untuk mapel
     dan rombel yang sama. Tanpa penanda ini, rekap rapor tidak bisa membedakan
     UH-1 dari UH-2 (dua penilaian biasa) dari ujian remedial.

     `remedial_of_id` di-DISABLE saat checkbox tidak dicentang, bukan disembunyikan:
     yang disembunyikan tetap terkirim dan `required` akan menolaknya
     (lihat .ai/rules/controllers.md). --}}
<div class="form-check mb-3">
    <input type="hidden" name="is_remedial" value="0" />
    <input class="form-check-input" type="checkbox" name="is_remedial" value="1" id="is_remedial"
           @checked(old('is_remedial', $exam->is_remedial ?? false))
           onchange="document.getElementById('remedial_of_id').disabled = !this.checked;" />
    <label class="form-check-label" for="is_remedial">Ujian remedial</label>
    @error('is_remedial')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
</div>

<div class="mb-3">
    <div class="form-floating">
        <select name="remedial_of_id" id="remedial_of_id" class="form-select @error('remedial_of_id') is-invalid @enderror" @disabled(! old('is_remedial', $exam->is_remedial ?? false))>
            <option value="">— Ujian asal —</option>
            @foreach($kandidatRemedial as $k)
                <option value="{{ $k->id }}" @selected((int) old('remedial_of_id') === $k->id)>
                    {{ $k->name }} ({{ $k->mataPelajaran?->nama ?? '—' }} — {{ $k->rombel?->kelas?->nama_kelas ?? '—' }})
                </option>
            @endforeach
        </select>
        <label>Remedial dari ujian</label>
        @error('remedial_of_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="form-text">Hanya ujian dengan rombel dan mata pelajaran yang sama. Nilai rapor memakai skor terbaik antara keduanya.</div>
</div>