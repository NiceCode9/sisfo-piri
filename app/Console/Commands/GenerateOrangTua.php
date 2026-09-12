<?php

namespace App\Console\Commands;

use App\Models\Siswa;
use App\Observers\SiswaObserver;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('siswa:generate-orangtua')]
#[Description('Buat akun orang-tua yang belum ada untuk seluruh siswa (idempoten)')]
class GenerateOrangTua extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dibuat = 0;

        foreach (Siswa::doesntHave('waliMurids')->cursor() as $siswa) {
            if (SiswaObserver::daftarkanOrangTua($siswa)) {
                $dibuat++;
            }
        }

        $this->info("{$dibuat} akun orang-tua dibuat.");

        return self::SUCCESS;
    }
}
