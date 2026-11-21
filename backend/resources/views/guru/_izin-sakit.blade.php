{{-- Kartu Izin/Sakit: penandaan + bukti satu-satu (spek 2026-10-23). --}}
<div class="card-nexus mt-3" id="kartu-izin">
    <div class="card-header-nexus flex-wrap gap-2">
        <div>
            <h5 class="card-title">Catatan Izin/Sakit</h5>
            <p class="card-subtitle mb-0">Tandai siswa yang tidak memindai kartu karena bawa surat</p>
        </div>
        @if ($rombelsIzin->count() > 1)
            <form method="GET" action="{{ route('guru.absensi') }}" class="d-flex gap-1">
                <select name="rombel_id" class="form-select form-select-sm" style="max-width:160px;font-size:12px;" aria-label="Kelas untuk catatan izin/sakit">
                    @foreach ($rombelsIzin as $r)
                        <option value="{{ $r->id }}" @selected($r->is($rombelIzinTerpilih))>{{ $r->kelas?->nama_kelas ?? $r->id }}</option>
                    @endforeach
                </select>
                <button class="btn btn-nexus-outline btn-sm" style="font-size:12px;">Tampilkan</button>
            </form>
        @endif
    </div>
    <div class="card-body-nexus" style="font-size:13px;">
        @if ($rombelsIzin->isEmpty())
            <p class="mb-0 text-center py-3" style="color:var(--text-muted);">Tidak ada rombel yang diampu.</p>
        @elseif ($siswaKartu->isEmpty())
            <p class="mb-0 text-center py-3" style="color:var(--text-muted);">Tidak ada siswa pada rombel ini.</p>
        @else
            <div class="table-responsive">
                <table class="table-nexus w-100">
                    <thead>
                        <tr><th>Siswa</th><th style="width:92px;">Status</th><th style="width:300px;">Tindakan</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($siswaKartu as $siswa)
                            @php $barisKartu = $absensiHariIni->get($siswa->id); @endphp
                            <tr>
                                <td>{{ $siswa->user?->name }} <span class="text-muted" style="font-size:11px;">{{ $siswa->nis }}</span></td>
                                <td>
                                    <span class="badge-nexus {{ in_array($barisKartu?->status, ['izin', 'sakit'], true) ? 'badge-info' : 'badge-neutral' }}">{{ $barisKartu?->status ? ucfirst($barisKartu->status) : '-' }}</span>
                                </td>
                                <td>
                                    @if ($barisKartu === null)
                                        {{-- Belum tercatat: satu form kecil, status + bukti opsional --}}
                                        <form method="POST" action="{{ route('guru.absensi.izin') }}" enctype="multipart/form-data" class="d-flex gap-1">
                                            @csrf
                                            <input type="hidden" name="rombel_id" value="{{ $rombelIzinTerpilih->id }}">
                                            <input type="hidden" name="siswa_id" value="{{ $siswa->id }}">
                                            <select name="status" class="form-select form-select-sm" style="max-width:92px;font-size:12px;" aria-label="Status {{ $siswa->nis }}">
                                                <option value="izin">Izin</option>
                                                <option value="sakit">Sakit</option>
                                            </select>
                                            <input type="file" name="berkas" accept=".jpg,.jpeg,.png,.webp" class="form-control form-control-sm @error('berkas') is-invalid @enderror" style="font-size:11px;padding:2px 4px;" aria-label="Bukti {{ $siswa->nis }}" />
                                            <button type="submit" class="btn btn-nexus-outline btn-sm" style="font-size:11px;padding:2px 8px;">Simpan</button>
                                        </form>
                                        @error('berkas')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                    @elseif (in_array($barisKartu->status, ['izin', 'sakit'], true) && ! $barisKartu->berkas_path)
                                        <form method="POST" action="{{ route('guru.absensi.izin.bukti', $barisKartu) }}" enctype="multipart/form-data" class="d-flex gap-1">
                                            @csrf
                                            <input type="file" name="berkas" accept=".jpg,.jpeg,.png,.webp" required class="form-control form-control-sm @error('berkas') is-invalid @enderror" style="font-size:11px;padding:2px 4px;" aria-label="Unggah bukti {{ $siswa->nis }}" />
                                            <button type="submit" class="btn btn-nexus-outline btn-sm" style="font-size:11px;padding:2px 8px;">Unggah</button>
                                        </form>
                                        @error('berkas')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                    @elseif ($barisKartu->berkas_path)
                                        <a href="{{ route('dokumen.absensi', $barisKartu) }}" target="_blank" rel="noopener" style="font-size:11px;">Lihat bukti</a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p class="mb-0 mt-2" style="font-size:12px;color:var(--text-muted);">
                Koreksi status yang sudah tercatat lewat grid absensi; kartu ini hanya menandai siswa tanpa catatan.
            </p>
        @endif
    </div>
</div>
