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
        Schema::table('blood_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('blood_requests', 'units_fulfilled')) {
                $table->integer('units_fulfilled')->default(0)->after('units_needed');
            }
            if (!Schema::hasColumn('blood_requests', 'min_units_bank_can_send')) {
                $table->integer('min_units_bank_can_send')->nullable()->after('units_fulfilled');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('blood_requests', function (Blueprint $table) {
            $table->dropColumn(['units_fulfilled', 'min_units_bank_can_send']);
        });
    }
};
