<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     * Fix all missing columns in compliance_monitoring table.
     */
    public function up(): void
    {
        Schema::table('compliance_monitoring', function (Blueprint $table) {
            if (!Schema::hasColumn('compliance_monitoring', 'inspection_id')) {
                $table->string('inspection_id')->nullable()->after('organization_id');
            }
            if (!Schema::hasColumn('compliance_monitoring', 'compliance_score')) {
                $table->integer('compliance_score')->default(100)->after('id');
            }
            if (!Schema::hasColumn('compliance_monitoring', 'facility_type')) {
                $table->string('facility_type')->nullable()->after('inspection_id');
            }
            if (!Schema::hasColumn('compliance_monitoring', 'compliance_status')) {
                $table->string('compliance_status')->nullable()->after('facility_type');
            }
            if (!Schema::hasColumn('compliance_monitoring', 'last_inspection_date')) {
                $table->timestamp('last_inspection_date')->nullable();
            }
            if (!Schema::hasColumn('compliance_monitoring', 'next_inspection_date')) {
                $table->timestamp('next_inspection_date')->nullable();
            }
            if (!Schema::hasColumn('compliance_monitoring', 'violations_found')) {
                $table->integer('violations_found')->default(0);
            }
            if (!Schema::hasColumn('compliance_monitoring', 'notes')) {
                $table->text('notes')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Don't drop columns on rollback - too risky
    }
};
