<?php

namespace App\Console\Commands;

use App\Jobs\KirimPengingatWA;
use Illuminate\Console\Command;

class CekPengingatWA extends Command
{
    protected $signature   = 'wa:cek-pengingat';
    protected $description = 'Kirim pengingat WA untuk tugas belum dikonfirmasi atau diapproval > 30 menit';

    public function handle(): void
    {
        $this->info('Mengecek tugas yang perlu pengingat WA...');
        KirimPengingatWA::dispatch();
        $this->info('Done.');
    }
}