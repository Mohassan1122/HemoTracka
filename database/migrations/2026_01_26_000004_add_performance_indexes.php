<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add indexes for inventory_items table
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->index('organization_id');
            $table->index('blood_group');
            $table->index('type');
            $table->index('quality_status');
            $table->index('expiry_date');
            $table->index(['organization_id', 'blood_group']);
            $table->index(['organization_id', 'type']);
            $table->index(['organization_id', 'quality_status']);
        });

        // Add indexes for inventory_alerts table
        Schema::table('inventory_alerts', function (Blueprint $table) {
            $table->index('organization_id');
            $table->index('severity');
            $table->index('is_read');
            $table->index('is_acknowledged');
            $table->index(['organization_id', 'is_read']);
            $table->index(['organization_id', 'severity']);
        });

        // Add indexes for storage_locations table
        Schema::table('storage_locations', function (Blueprint $table) {
            $table->index('organization_id');
            $table->index('parent_location_id');
            $table->index('location_type');
            $table->index(['organization_id', 'parent_location_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->dropIndex(['organization_id']);
            $table->dropIndex(['blood_group']);
            $table->dropIndex(['type']);
            $table->dropIndex(['quality_status']);
            $table->dropIndex(['expiry_date']);
            $table->dropIndex(['organization_id', 'blood_group']);
            $table->dropIndex(['organization_id', 'type']);
            $table->dropIndex(['organization_id', 'quality_status']);
        });

        Schema::table('inventory_alerts', function (Blueprint $table) {
            $table->dropIndex(['organization_id']);
            $table->dropIndex(['severity']);
            $table->dropIndex(['is_read']);
            $table->dropIndex(['is_acknowledged']);
            $table->dropIndex(['organization_id', 'is_read']);
            $table->dropIndex(['organization_id', 'severity']);
        });

        Schema::table('storage_locations', function (Blueprint $table) {
            $table->dropIndex(['organization_id']);
            $table->dropIndex(['parent_location_id']);
            $table->dropIndex(['location_type']);
            $table->dropIndex(['organization_id', 'parent_location_id']);
        });
    }
};
