<?php

namespace App\Console\Commands;

use App\Services\Arminareka\ArminarekaPriceSyncService;
use App\Support\PriceSyncSchedule;
use Illuminate\Console\Command;
use Throwable;

class RunScheduledPriceSync extends Command
{
    protected $signature = 'price-sync:run-scheduled';

    protected $description = 'Jalankan sync harga Arminareka sesuai jadwal admin';

    public function handle(ArminarekaPriceSyncService $sync): int
    {
        if (! PriceSyncSchedule::isDue()) {
            return self::SUCCESS;
        }

        try {
            $run = $sync->runScheduled();
            PriceSyncSchedule::markRan();
            $this->info('Sync #'.$run->id.' selesai ('.$run->status.').');
        } catch (Throwable $exception) {
            $this->error('Sync gagal: '.$exception->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
