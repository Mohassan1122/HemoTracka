<?php

namespace App\Services;

use App\Models\InventoryItem;
use App\Models\InventoryAlert;
use Carbon\Carbon;

class InventoryAlertService
{
    /**
     * Check all inventory items and generate alerts for low stock and expiring items
     */
    public function checkAndGenerateAlerts(): array
    {
        $alerts = [];

        // Check for low stock
        $alerts = array_merge($alerts, $this->checkLowStock());

        // Check for expiring items
        $alerts = array_merge($alerts, $this->checkExpiringItems());

        // Check for expired items
        $alerts = array_merge($alerts, $this->checkExpiredItems());

        // Check for out of stock
        $alerts = array_merge($alerts, $this->checkOutOfStock());

        return $alerts;
    }

    /**
     * Check for low stock items (below threshold)
     */
    protected function checkLowStock(): array
    {
        $alerts = [];

        $lowStockItems = InventoryItem::whereColumn('units_in_stock', '<=', 'threshold')
            ->where('units_in_stock', '>', 0)
            ->get();

        foreach ($lowStockItems as $item) {
            // Check if alert already exists and is unacknowledged
            $existingAlert = InventoryAlert::where('inventory_item_id', $item->id)
                ->where('alert_type', 'low_stock')
                ->unacknowledged()
                ->first();

            if (!$existingAlert) {
                $alert = InventoryAlert::create([
                    'organization_id' => $item->organization_id,
                    'inventory_item_id' => $item->id,
                    'alert_type' => 'low_stock',
                    'severity' => 'warning',
                    'title' => "Low Stock: {$item->blood_group} {$item->type}",
                    'message' => "Only {$item->units_in_stock} units remaining (threshold: {$item->threshold}). Consider restocking soon.",
                ]);

                $alerts[] = $alert;
            }
        }

        return $alerts;
    }

    /**
     * Check for items expiring soon (within 7 or 14 days)
     */
    protected function checkExpiringItems(): array
    {
        $alerts = [];
        $now = Carbon::now();

        $expiringItems = InventoryItem::whereNotNull('expiry_date')
            ->where('units_in_stock', '>', 0)
            ->where('expiry_date', '>', $now)
            ->where('expiry_date', '<=', $now->copy()->addDays(14))
            ->get();

        foreach ($expiringItems as $item) {
            $daysUntilExpiry = $now->diffInDays($item->expiry_date);

            // Determine severity
            $severity = 'info';
            if ($daysUntilExpiry <= 3) {
                $severity = 'critical';
            } elseif ($daysUntilExpiry <= 7) {
                $severity = 'warning';
            }

            // Check if alert already exists
            $existingAlert = InventoryAlert::where('inventory_item_id', $item->id)
                ->where('alert_type', 'expiring_soon')
                ->unacknowledged()
                ->first();

            if (!$existingAlert) {
                $alert = InventoryAlert::create([
                    'organization_id' => $item->organization_id,
                    'inventory_item_id' => $item->id,
                    'alert_type' => 'expiring_soon',
                    'severity' => $severity,
                    'title' => "Expiring Soon: {$item->blood_group} {$item->type}",
                    'message' => "{$item->units_in_stock} units expiring in {$daysUntilExpiry} day(s) on {$item->expiry_date->format('M d, Y')}. Use or discard before expiry.",
                ]);

                $alerts[] = $alert;
            }
        }

        return $alerts;
    }

    /**
     * Check for expired items
     */
    protected function checkExpiredItems(): array
    {
        $alerts = [];

        $expiredItems = InventoryItem::whereNotNull('expiry_date')
            ->where('units_in_stock', '>', 0)
            ->where('expiry_date', '<', Carbon::now())
            ->get();

        foreach ($expiredItems as $item) {
            // Check if alert already exists
            $existingAlert = InventoryAlert::where('inventory_item_id', $item->id)
                ->where('alert_type', 'expired')
                ->unacknowledged()
                ->first();

            if (!$existingAlert) {
                $alert = InventoryAlert::create([
                    'organization_id' => $item->organization_id,
                    'inventory_item_id' => $item->id,
                    'alert_type' => 'expired',
                    'severity' => 'critical',
                    'title' => "EXPIRED: {$item->blood_group} {$item->type}",
                    'message' => "{$item->units_in_stock} units expired on {$item->expiry_date->format('M d, Y')}. Immediate action required: quarantine and discard.",
                ]);

                $alerts[] = $alert;
            }
        }

        return $alerts;
    }

    /**
     * Check for out of stock items
     */
    protected function checkOutOfStock(): array
    {
        $alerts = [];

        $outOfStockItems = InventoryItem::where('units_in_stock', 0)->get();

        foreach ($outOfStockItems as $item) {
            // Check if alert already exists
            $existingAlert = InventoryAlert::where('inventory_item_id', $item->id)
                ->where('alert_type', 'out_of_stock')
                ->unacknowledged()
                ->first();

            if (!$existingAlert) {
                $alert = InventoryAlert::create([
                    'organization_id' => $item->organization_id,
                    'inventory_item_id' => $item->id,
                    'alert_type' => 'out_of_stock',
                    'severity' => 'warning',
                    'title' => "Out of Stock: {$item->blood_group} {$item->type}",
                    'message' => "No units available. Restock as soon as possible to meet potential demand.",
                ]);

                $alerts[] = $alert;
            }
        }

        return $alerts;
    }

    /**
     * Get summary of alerts by severity
     */
    public function getAlertsSummary($organizationId): array
    {
        return [
            'total' => InventoryAlert::forOrganization($organizationId)->unread()->count(),
            'critical' => InventoryAlert::forOrganization($organizationId)->unread()->critical()->count(),
            'warning' => InventoryAlert::forOrganization($organizationId)->unread()->where('severity', 'warning')->count(),
            'info' => InventoryAlert::forOrganization($organizationId)->unread()->where('severity', 'info')->count(),
        ];
    }
}
