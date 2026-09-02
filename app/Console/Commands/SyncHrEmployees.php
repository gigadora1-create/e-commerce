<?php

namespace App\Console\Commands;

use App\Services\HrEmployeeSyncService;
use Illuminate\Console\Command;
use Throwable;

class SyncHrEmployees extends Command
{
    protected $signature = 'users:sync-hr';

    protected $description = 'Synchronize users from the Human Resources employees API';

    public function handle(HrEmployeeSyncService $syncService): int
    {
        try {
            $result = $syncService->sync();
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            report($exception);

            return self::FAILURE;
        }

        $this->info(sprintf(
            'Synchronization complete. Created: %d, updated: %d, skipped: %d.',
            $result['created'],
            $result['updated'],
            $result['skipped'],
        ));

        return self::SUCCESS;
    }
}
