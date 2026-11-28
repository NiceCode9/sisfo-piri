@extends('layouts.app')

@section('title', 'Detail Ujian')
@section('breadcrumb', 'Detail Ujian')

@push('styles')
    <style>
        /* Halaman ini dipakai untuk mengecek soal: teks panjang harus bisa
           dibaca, dan daftar bank tidak boleh mendorong tombol aksi. */
        .cbt-soal-text { max-width: 520px; overflow-wrap: anywhere; }
        .cbt-daftar-bank { max-height: 220px; overflow-y: auto; }
        .cbt-unduh summary { cursor: pointer; }
        .cbt-paket { max-width: 340px; }
    </style>
@endpush

@section('content')
@php
    $tipeLabel = [
        'single_choice' => 'Pilihan tunggal',
        'multiple_choice' => 'Pilihan jamak',
        'essay' => 'Uraian',
    ];
    $soalUjian = $exam->questions->sortBy('order');
    $sudahDisalin = $exam->questions->pluck('question_bank_id')->filter()->all();
    $totalPoin = $soalUjian->sum('score');
@endphp

<div class="page-header d-flex justify-content-between gap-2 flex-wrap">
    <div>
        <h1 class="page-title">{{ $exam->name }}</h1>
        <p class="page-subtitle mb-0">{{ $exam->rombel->kelas->nama_kelas ?? '-' }} · {{ $exam->mataPelajaran->nama ?? '-' }} · {{ $exam->duration_minutes }} menit</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        @can('update', $exam)<a href="{{ route('admin.cbt.exams.edit', $exam) }}" class="btn btn-primary btn-sm"><i class="fa-solid fa-pencil" aria-hidden="true"></i> Edit Ujian</a>@endcan
        <a href="{{ route('admin.cbt.results.index', $exam) }}" class="btn btn-nexus-outline btn-sm">Hasil</a>
        <a href="{{ route('admin.cbt.monitoring.index', $exam) }}" class="btn btn-nexus-outline btn-sm">Monitoring</a>
    </div>
</div>
@include('layouts.partials.alert', ['errorDitampilkan' => ['active_from', 'active_until', 'max_usage', 'paket']])

{{--
  Ringkasan ini menjawab satu pertanyaan yang selalu muncul di halaman ini:
  apakah ujiannya sudah siap dijalankan. Status, durasi, dan jendela waktu
  menentukan itu, jadi ditampilkan di atas soal dan token.
--}}
<div class="card-nexus mb-3">
    <div class="card-header-nexus"><h5 class="card-title">Kesiapan ujian</h5></div>
    <div class="card-body-nexus">
        <div class="row g-3">
            <div class="col-6 col-md-4 col-lg-2">
                <span class="small text-secondary d-block">Status</span>
                <span class="badge-nexus {{ $exam->statusBadge() }}">{{ $exam->statusLabel() }}</span>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <span class="small text-secondary d-block">Durasi</span>
                <span class="fw-semibold">{{ $exam->duration_minutes }} menit</span>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <span class="small text-secondary d-block">Batas pelanggaran</span>
                <span class="fw-semibold">{{ $exam->max_violation_count }} kali</span>
            </div>
            <div class="col-12 col-md-6 col-lg-4">
                <span class="small text-secondary d-block">Jendela waktu</span>
                <span class="fw-semibold">
                    @if($exam->available_from && $exam->available_until)
                        {{ $exam->available_from->format('d M Y H:i') }} sampai {{ $exam->available_until->format('d M Y H:i') }}
                    @elseif($exam->available_from)
                        Mulai {{ $exam->available_from->format('d M Y H:i') }}, tanpa batas akhir
                    @elseif($exam->available_until)
                        Sampai {{ $exam->available_until->format('d M Y H:i') }}
                    @else
                        Tanpa batas waktu
                    @endif
                </span>
            </div>
            <div class="col-12 col-md-6 col-lg-2">
                <span class="small text-secondary d-block">Pengacakan</span>
                <span class="fw-semibold">Soal {{ $exam->shuffle_questions ? 'acak' : 'berurutan' }}, opsi {{ $exam->shuffle_options ? 'acak' : 'berurutan' }}</span>
            </div>
            @if($exam->is_remedial)
                <div class="col-12">
                    <span class="small text-secondary d-block">Remedial dari</span>
                    <span class="fw-semibold">{{ $exam->remedialOf->name ?? 'ujian asal tidak ditemukan' }}</span>
                </div>
            @endif
        </div>
    </div>
</div>

<div class="card-nexus mb-3">
    <div class="card-body-nexus">
        <p class="small text-secondary mb-0">{{ $exam->description ?: 'Belum ada deskripsi.' }}</p>
    </div>
</div>

{{-- Daftar soal dibuat meja bernomor: guru memeriksa urutan, kunci, dan poin
     dalam satu baris, tanpa membuka tiap soal satu per satu. --}}
<div class="card-nexus mb-3">
    <div class="card-header-nexus">
        <h5 class="card-title">Soal ujian</h5>
        <span class="badge-nexus badge-neutral">{{ $soalUjian->count() }} soal · {{ $totalPoin }} poin</span>
    </div>
    <div class="card-body-nexus p-0">
        @if($soalUjian->isNotEmpty())
            <div class="table-responsive">
                <table class="table-nexus w-100">
                    <thead>
                        <tr><th scope="col">No</th><th scope="col">Soal</th><th scope="col">Tipe</th><th scope="col">Kunci</th><th scope="col">Poin</th><th scope="col">Sumber</th><th scope="col">Aksi</th></tr>
                    </thead>
                    <tbody>
                        @foreach($soalUjian as $q)
                            <tr>
                                <td class="font-mono">{{ $loop->iteration }}</td>
                                <td>
                                    <div class="cbt-soal-text">{{ $q->question_text }}</div>
                                    @if($q->question_image)<a href="{{ route('admin.cbt.questions.image', $q) }}" target="_blank" class="badge-nexus badge-info" aria-label="Lihat gambar soal nomor {{ $loop->iteration }}">Gambar</a>@endif
                                </td>
                                <td><span class="badge-nexus badge-neutral">{{ $tipeLabel[$q->type] ?? $q->type }}</span></td>
                                <td class="font-mono">{{ $q->type === 'essay' ? 'Uraian' : (collect($q->correct_answer ?? [])->map(fn ($k) => strtoupper($k))->implode(', ') ?: '-') }}</td>
                                <td class="font-mono">{{ $q->score }}</td>
                                <td>@if($q->question_bank_id)<span class="badge-nexus badge-info">Bank #{{ $q->question_bank_id }}</span>@else<span class="text-secondary">Manual atau impor</span>@endif</td>
                                <td>
                                    @can('update', $exam)
                                        <form method="POST" action="{{ route('admin.cbt.questions.destroy', $q) }}" onsubmit="return confirm('Hapus soal nomor {{ $loop->iteration }}?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-sm text-danger">Hapus</button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="empty-state">
                <p class="empty-title">Belum ada soal di ujian ini.</p>
                <p class="empty-text">Tambah soal manual di bawah, salin dari bank soal, atau unggah paket Excel.</p>
            </div>
        @endif
    </div>
</div>

<details class="card-nexus mb-3 cbt-unduh">
    <summary class="card-header-nexus"><h5 class="card-title">Tambah satu soal manual</h5></summary>
    <div class="card-body-nexus">
        <form method="POST" action="{{ route('admin.cbt.exams.questions.store', $exam) }}" enctype="multipart/form-data">
            @csrf
            @include('admin.cbt.questions._form')
            <div class="mt-2"><button type="submit" class="btn btn-primary btn-sm">Simpan soal baru</button></div>
        </form>
    </div>
</details>

<div class="card-nexus mb-3">
    <div class="card-header-nexus"><h5 class="card-title">Token ujian</h5></div>
    <div class="card-body-nexus">
        @forelse($exam->tokens as $t)
            @php
                $tokenStatus = ! $t->is_active ? 'Nonaktif' : ($t->active_until?->isPast() ? 'Kedaluwarsa' : ($t->active_from?->isFuture() ? 'Belum mulai' : 'Aktif'));
                $tokenBadge = match ($tokenStatus) {
                    'Aktif' => 'success',
                    'Belum mulai' => 'info',
                    'Kedaluwarsa' => 'warning',
                    default => 'neutral',
                };
            @endphp
            <div class="d-flex flex-wrap gap-2 align-items-center border-bottom py-2">
                <span class="font-mono fw-semibold">{{ $t->token }}</span>
                <span class="badge-nexus badge-{{ $tokenBadge }}">{{ $tokenStatus }}</span>
                <span class="small text-secondary">{{ $t->active_from?->format('d M Y H:i') ?? 'mulai kapan saja' }} · {{ $t->active_until?->format('d M Y H:i') ?? 'tanpa akhir' }}</span>
                <span class="small text-secondary ms-auto">Terpakai {{ $t->used_count }}{{ $t->max_usage ? ' / '.$t->max_usage : '' }}</span>
            </div>
        @empty
            <p class="small text-secondary mb-3">Belum ada token. Buat token dulu sebelum ujian dibuka.</p>
        @endforelse

        <form method="POST" action="{{ route('admin.cbt.exams.tokens.store', $exam) }}" class="row g-2 mt-3">
            @csrf
            <div class="col-md-4">
                <label for="active_from" class="form-label small">Mulai</label>
                <input type="datetime-local" id="active_from" name="active_from" value="{{ old('active_from') }}" autocomplete="off" class="form-control form-control-sm @error('active_from') is-invalid @enderror" required />
                @error('active_from')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
                <label for="active_until" class="form-label small">Sampai</label>
                <input type="datetime-local" id="active_until" name="active_until" value="{{ old('active_until') }}" autocomplete="off" class="form-control form-control-sm @error('active_until') is-invalid @enderror" required />
                @error('active_until')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-2">
                {{-- `max_usage` divalidasi controller tapi tidak pernah punya kolom.
                     Tanpa ini kuota token selalu kosong. --}}
                <label for="max_usage" class="form-label small">Kuota pakai</label>
                <input type="number" id="max_usage" name="max_usage" value="{{ old('max_usage') }}" min="1" autocomplete="off" class="form-control form-control-sm @error('max_usage') is-invalid @enderror" />
                @error('max_usage')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-nexus-outline btn-sm w-100">Buat token</button>
            </div>
        </form>
    </div>
</div>

@if($banks->isNotEmpty())
    <div class="card-nexus mb-3">
        <div class="card-header-nexus"><h5 class="card-title">Salin dari bank soal</h5></div>
        <div class="card-body-nexus">
            <p class="small text-secondary">Centang soal yang akan disalin. Soal yang sudah ada di ujian ini tidak bisa dipilih ulang.</p>
            @foreach($banks as $bank)
                @php $bankQuestions = \App\Models\ExamQuestion::where('question_bank_id', $bank->id)->orderBy('order')->get(); @endphp
                <div class="border rounded p-3 mb-3">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <strong class="small">{{ $bank->nama }}</strong>
                        <span class="small text-secondary">{{ $bank->questions_count }} soal di bank</span>
                    </div>
                    @if($bankQuestions->isEmpty())
                        <p class="small text-secondary mt-2 mb-0">Bank ini masih kosong.</p>
                    @else
                        <form method="POST" action="{{ route('admin.cbt.exams.banks.import', [$exam, $bank]) }}" data-bank-form>
                            @csrf
                            <div class="cbt-daftar-bank mt-2">
                                @foreach($bankQuestions as $bq)
                                    @php $sudahAda = in_array($bq->id, $sudahDisalin, true); @endphp
                                    <label class="d-flex gap-2 align-items-start border-bottom py-2 small @if($sudahAda) text-secondary @endif">
                                        <input type="checkbox" name="question_ids[]" value="{{ $bq->id }}" class="mt-1" @disabled($sudahAda) />
                                        <span>{{ Str::limit($bq->question_text, 90) }} ({{ $tipeLabel[$bq->type] ?? $bq->type }}, {{ $bq->score }} poin)
                                            @if($sudahAda)<span class="badge-nexus badge-neutral">Sudah disalin</span>@endif
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                            <div class="d-flex flex-wrap gap-2 align-items-center mt-3">
                                <button type="submit" class="btn btn-primary btn-sm" data-bank-submit>Salin soal terpilih</button>
                                <span class="small text-secondary" data-bank-count aria-live="polite">0 soal dipilih</span>
                            </div>
                        </form>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
@endif

<div class="card-nexus">
    <div class="card-header-nexus"><h5 class="card-title">Impor massal dari Excel</h5></div>
    <div class="card-body-nexus">
        <p class="small text-secondary">Unggah paket <code>.zip</code> berisi <code>soal.xlsx</code> dan folder <code>gambar/</code>. Soal ditampilkan dulu di halaman review sebelum disimpan.</p>
        <a href="{{ route('admin.cbt.import.template') }}" class="btn btn-nexus-outline btn-sm">Unduh template Excel</a>
        <form method="POST" action="{{ route('admin.cbt.import.unggah', $exam) }}" enctype="multipart/form-data" class="d-flex gap-2 flex-wrap align-items-center mt-3">
            @csrf
            <label for="paket" class="visually-hidden">Paket .zip berisi soal.xlsx dan folder gambar</label>
            <input type="file" id="paket" name="paket" accept=".zip,application/zip" class="form-control form-control-sm cbt-paket @error('paket') is-invalid @enderror" required />
            @error('paket')<div class="invalid-feedback">{{ $message }}</div>@enderror
            <button type="submit" class="btn btn-primary btn-sm">Unggah &amp; review</button>
        </form>
    </div>
</div>
@endsection

@push('scripts')
    <script>
        (function () {
            document.querySelectorAll('[data-bank-form]').forEach(function (form) {
                var pilihan = form.querySelectorAll('input[name="question_ids[]"]:not(:disabled)');
                var hitung = form.querySelector('[data-bank-count]');
                var tombol = form.querySelector('[data-bank-submit]');

                function perbarui() {
                    var jumlah = form.querySelectorAll('input[name="question_ids[]"]:checked').length;
                    hitung.textContent = jumlah + ' soal dipilih';
                    tombol.disabled = jumlah === 0;
                    tombol.textContent = 'Salin ' + jumlah + ' soal terpilih';
                }

                pilihan.forEach(function (box) { box.addEventListener('change', perbarui); });
                perbarui();
            });
        })();
    </script>
@endpush
