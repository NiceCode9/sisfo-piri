<?php

namespace App\Exports;

use App\Models\Pembayaran;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class PembayaransExport implements FromCollection, WithHeadings, WithMapping
{
    /**
     * @param  Collection<int, Pembayaran>  $pembayarans
     */
    public function __construct(protected Collection $pembayarans) {}

    /**
     * @return Collection<int, Pembayaran>
     */
    public function collection(): Collection
    {
        return $this->pembayarans;
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return [
            'Kode',
            'No Pendaftaran',
            'Nama Calon',
            'Biaya',
            'Jumlah',
            'Metode',
            'Jenis',
            'Status',
            'Tanggal Bayar',
            'Dibuat',
        ];
    }

    /**
     * @param  Pembayaran  $pembayaran
     * @return array<int, mixed>
     */
    public function map($pembayaran): array
    {
        return [
            $pembayaran->kode_pembayaran,
            $pembayaran->calonSiswa->no_pendaftaran ?? '-',
            $pembayaran->calonSiswa->nama_lengkap ?? '-',
            $pembayaran->biayaPendaftaran->jenis_biaya ?? '-',
            (float) $pembayaran->jumlah,
            $pembayaran->metode_pembayaran,
            $pembayaran->jenis_pembayaran,
            $pembayaran->status,
            $pembayaran->tanggal_pembayaran,
            $pembayaran->created_at?->format('Y-m-d H:i'),
        ];
    }
}
