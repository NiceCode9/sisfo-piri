<?php

namespace App\Support;

use App\Models\BiayaPendaftaran;
use App\Models\CalonSiswa;
use App\Models\Pembayaran;
use App\Models\RencanaAngsuran;
use Illuminate\Support\Collection;

/**
 * Satu-satunya tempat menghitung status tagihan seorang calon.
 *
 * Sebelumnya ada dua definisi "lunas" yang hidup berdampingan dan bisa
 * berbeda jawabannya untuk data yang sama:
 *
 *  - `CalonSiswa::scopeBelumLunas()` menjumlahkan baris `pembayarans` berstatus
 *    `berhasil` (dikurangi induk rencana yang sudah lunas).
 *  - Halaman detail calon menjumlahkan **semua** biaya, dan untuk biaya
 *    ber-angsur membaca `rencana_angsurans.dp_dibayar` + cicilan di
 *    `detail_angsurans` — bukan tabel `pembayarans`.
 *
 * Akibatnya mengubah status satu baris DP dari `berhasil` ke `gagal` membuat
 * halaman detail menulis "Lunas" sementara kandidat yang sama tetap muncul di
 * pemilih pembayaran sebagai belum lunas.
 *
 * Kalkulator ini menutup keduanya dengan definisi tunggal:
 * **lunas = seluruh biaya wajib sudah lunas**. Pembayaran angsuran tidak perlu
 * penanganan khusus karena DP dan tiap cicilan tercatat sebagai baris
 * `pembayarans` masing-masing; hanya tagihan induk rencana yang sudah `lunas`
 * yang dikecualikan agar tidak terhitung dua kali.
 */
class TagihanCalon
{
    /** @var array<int, float> Total per biaya yang sudah terbayar. */
    private array $terbayarPerBiaya = [];

    /** @param Collection<int, BiayaPendaftaran> $biaya */
    public function __construct(
        private readonly CalonSiswa $calon,
        private readonly Collection $biaya,
    ) {
        $this->hitung();
    }

    public static function untuk(CalonSiswa $calon): self
    {
        return new self(
            $calon,
            $calon->tahunAjaran?->biayaPendaftaran ?? collect(),
        );
    }

    public function totalBiaya(): float
    {
        return (float) $this->biaya->sum('jumlah');
    }

    /**
     * Total biaya yang wajib dibayar (opsi `wajib_bayar = false` tidak ikut).
     */
    public function totalWajib(): float
    {
        return (float) $this->biaya->where('wajib_bayar', true)->sum('jumlah');
    }

    /**
     * Total biaya wajib yang sudah terbayar.
     */
    public function terbayarWajib(): float
    {
        return $this->jumlahTerbayar($this->biaya->where('wajib_bayar', true));
    }

    /**
     * Total seluruh biaya (wajib + opsional) yang sudah terbayar.
     */
    public function terbayar(): float
    {
        return $this->jumlahTerbayar($this->biaya);
    }

    public function sisaWajib(): float
    {
        return max(0.0, $this->totalWajib() - $this->terbayarWajib());
    }

    public function lunasWajib(): bool
    {
        return $this->sisaWajib() <= 0.0;
    }

    /**
     * Rincian per biaya untuk tabel di halaman detail.
     *
     * @return list<array<string, mixed>>
     */
    public function rincian(): array
    {
        $rencanaAktif = $this->calon->rencanaAngsuran
            ->where('status', 'aktif')
            ->pluck('biaya_pendaftaran_id')
            ->all();

        $tagihanMenunggu = $this->calon->pembayaran
            ->where('status', 'menunggu')
            ->pluck('biaya_pendaftaran_id', 'id');

        $baris = [];

        foreach ($this->biaya as $biaya) {
            $terbayar = (float) ($this->terbayarPerBiaya[$biaya->id] ?? 0.0);

            $baris[] = [
                'id' => $biaya->id,
                'jenis' => $biaya->jenis_biaya,
                'jumlah' => (float) $biaya->jumlah,
                'terbayar' => $terbayar,
                'sisa' => max(0.0, (float) $biaya->jumlah - $terbayar),
                'wajib' => (bool) $biaya->wajib_bayar,
                'mata_uang' => $biaya->mata_uang,
                'keterangan' => $biaya->keterangan,
                'dapat_diangsur' => (bool) $biaya->dapat_diangsur,
                'tagihan_id' => $tagihanMenunggu[$biaya->id] ?? null,
                'rencana_aktif' => in_array($biaya->id, $rencanaAktif, true),
                'lunas' => $terbayar >= (float) $biaya->jumlah,
            ];
        }

        return $baris;
    }

    /**
     * @param  Collection<int, BiayaPendaftaran>  $subset
     */
    private function jumlahTerbayar(Collection $subset): float
    {
        $total = 0.0;

        foreach ($subset as $biaya) {
            $total += (float) ($this->terbayarPerBiaya[$biaya->id] ?? 0.0);
        }

        return $total;
    }

    private function hitung(): void
    {
        // Id induk rencana yang sudah lunas: tagihan induknya otomatis berubah
        // jadi `berhasil` saat cicilan terakhir Closing, sementara DP dan
        // cicilannya juga tercatat sendiri. Menjadikannya terhitung sekali
        // benar akan memakai uang yang sama dua kali.
        $indukLunas = RencanaAngsuran::whereIn('calon_siswa_id', [$this->calon->id])
            ->where('status', 'lunas')
            ->whereNotNull('pembayaran_id')
            ->pluck('pembayaran_id')
            ->all();

        Pembayaran::where('calon_siswa_id', $this->calon->id)
            ->where('status', 'berhasil')
            ->when($indukLunas !== [], fn ($q) => $q->whereNotIn('id', $indukLunas))
            ->get(['biaya_pendaftaran_id', 'jumlah'])
            ->each(function (Pembayaran $pembayaran) {
                $id = $pembayaran->biaya_pendaftaran_id;

                $this->terbayarPerBiaya[$id] = ($this->terbayarPerBiaya[$id] ?? 0.0)
                    + (float) $pembayaran->jumlah;
            });
    }
}
