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
            // Add missing fields if they don't exist
            if (!Schema::hasColumn('blood_requests', 'genotype')) {
                $table->string('genotype')->nullable()->after('blood_group');
            }
            if (!Schema::hasColumn('blood_requests', 'min_units_bank_can_send')) {
                $table->integer('min_units_bank_can_send')->nullable()->after('units_needed');
            }
            if (!Schema::hasColumn('blood_requests', 'is_emergency')) {
                $table->boolean('is_emergency')->default(false)->after('needed_by');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('blood_requests', function (Blueprint $table) {
            if (Schema::hasColumn('blood_requests', 'genotype')) {
                $table->dropColumn('genotype');
            }
            if (Schema::hasColumn('blood_requests', 'min_units_bank_can_send')) {
                $table->dropColumn('min_units_bank_can_send');
            }
            if (Schema::hasColumn('blood_requests', 'is_emergency')) {
                $table->dropColumn('is_emergency');
            }
        });
    }
};
