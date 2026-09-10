<?php

namespace App\Exports;

use App\Models\PembayaranLainnya;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class PembayaranLainnyaExport implements FromCollection, WithHeadings, WithMapping
{
    /**
     * @param  Collection<int, PembayaranLainnya>  $pembayarans
     */
    public function __construct(protected Collection $pembayarans) {}

    /**
     * @return Collection<int, PembayaranLainnya>
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
            'Nama Biaya',
            'Jumlah',
            'Metode',
            'Status',
            'Tanggal Bayar',
            'Dibuat',
        ];
    }

    /**
     * @param  PembayaranLainnya  $pembayaran
     * @return array<int, mixed>
     */
    public function map($pembayaran): array
    {
        return [
            $pembayaran->kode_pembayaran,
            $pembayaran->calonSiswa->no_pendaftaran ?? '-',
            $pembayaran->calonSiswa->nama_lengkap ?? '-',
            $pembayaran->nama_biaya,
            (float) $pembayaran->jumlah,
            $pembayaran->metode_pembayaran,
            $pembayaran->status,
            $pembayaran->tanggal_pembayaran,
            $pembayaran->created_at?->format('Y-m-d H:i'),
        ];
    }
}
