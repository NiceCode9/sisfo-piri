<?php

namespace App\Console\Commands;

use App\Models\BeritaView;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('berita:prune-views {--days=90 : Hapus view lebih lama dari N hari}')]
#[Description('Hapus data tracking kunjungan berita yang lebih lama dari N hari')]
class PruneBeritaViews extends Command
{
    public function handle(): int
    {
        $days = (int) $this->option('days');
        if ($days < 1) {
            $days = 90;
        }

        $deleted = BeritaView::where('created_at', '<', now()->subDays($days))->delete();

        $this->info("{$deleted} baris berita_views (lebih lama dari {$days} hari) dihapus.");

        return self::SUCCESS;
    }
}
