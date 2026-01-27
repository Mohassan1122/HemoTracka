<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\InventoryAlertService;
use Illuminate\Support\Facades\Log;

class CheckInventoryAlerts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'inventory:check-alerts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check inventory for low stock, expiring items, and other alerts';

    /**
     * Execute the console command.
     */
    public function handle(InventoryAlertService $alertService)
    {
        $this->info('Starting inventory alert check...');

        try {
            $alerts = $alertService->checkAndGenerateAlerts();

            $count = count($alerts);
            $this->info("Successfully generated {$count} new alerts.");

            Log::info("Inventory alert check completed. Generated {$count} new alerts.");
        } catch (\Exception $e) {
            $this->error('Failed to check alerts: ' . $e->getMessage());
            Log::error('Inventory alert check failed: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
