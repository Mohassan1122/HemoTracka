<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     * Comprehensive fix for all missing columns in PostgreSQL database.
     */
    public function up(): void
    {
        // 1. Fix users_role_check constraint
        try {
            DB::statement("ALTER TABLE users DROP CONSTRAINT IF EXISTS users_role_check");
            DB::statement("ALTER TABLE users ADD CONSTRAINT users_role_check CHECK (role IN ('admin','donor','rider','facilities','blood_banks','regulatory_body'))");
        } catch (\Throwable $e) {
            // Ignore if constraint operations fail
        }

        // 1b. Fix compliance_requests constraints
        try {
            DB::statement("ALTER TABLE compliance_requests DROP CONSTRAINT IF EXISTS compliance_requests_status_check");
            DB::statement("ALTER TABLE compliance_requests DROP CONSTRAINT IF EXISTS compliance_requests_organization_type_check");
        } catch (\Throwable $e) {
            // Ignore if constraint operations fail
        }

        // 2. Fix regulatory_body_social_connections table
        if (Schema::hasTable('regulatory_body_social_connections')) {
            Schema::table('regulatory_body_social_connections', function (Blueprint $table) {
                if (!Schema::hasColumn('regulatory_body_social_connections', 'url')) {
                    $table->string('url')->nullable();
                }
                if (!Schema::hasColumn('regulatory_body_social_connections', 'is_verified')) {
                    $table->boolean('is_verified')->default(false);
                }
            });
        }

        // 3. Fix compliance_monitoring table
        if (Schema::hasTable('compliance_monitoring')) {
            Schema::table('compliance_monitoring', function (Blueprint $table) {
                if (!Schema::hasColumn('compliance_monitoring', 'inspection_id')) {
                    $table->string('inspection_id')->nullable();
                }
                if (!Schema::hasColumn('compliance_monitoring', 'compliance_score')) {
                    $table->integer('compliance_score')->default(100);
                }
                if (!Schema::hasColumn('compliance_monitoring', 'facility_type')) {
                    $table->string('facility_type')->nullable();
                }
                if (!Schema::hasColumn('compliance_monitoring', 'compliance_status')) {
                    $table->string('compliance_status')->nullable();
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

        // 4. Fix compliance_requests table
        if (Schema::hasTable('compliance_requests')) {
            Schema::table('compliance_requests', function (Blueprint $table) {
                if (!Schema::hasColumn('compliance_requests', 'request_type')) {
                    $table->string('request_type')->nullable();
                }
                if (!Schema::hasColumn('compliance_requests', 'description')) {
                    $table->text('description')->nullable();
                }
                if (!Schema::hasColumn('compliance_requests', 'priority')) {
                    $table->string('priority')->default('Medium');
                }
                if (!Schema::hasColumn('compliance_requests', 'submission_date')) {
                    $table->timestamp('submission_date')->nullable();
                }
                if (!Schema::hasColumn('compliance_requests', 'required_documents')) {
                    $table->json('required_documents')->nullable();
                }
                if (!Schema::hasColumn('compliance_requests', 'approved_at')) {
                    $table->timestamp('approved_at')->nullable();
                }
                if (!Schema::hasColumn('compliance_requests', 'rejection_reason')) {
                    $table->text('rejection_reason')->nullable();
                }
                if (!Schema::hasColumn('compliance_requests', 'reviewed_by_id')) {
                    $table->unsignedBigInteger('reviewed_by_id')->nullable();
                }
                if (!Schema::hasColumn('compliance_requests', 'notes')) {
                    $table->text('notes')->nullable();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop constraint on rollback
        try {
            DB::statement("ALTER TABLE users DROP CONSTRAINT IF EXISTS users_role_check");
        } catch (\Throwable $e) {
            // Ignore
        }
    }
};
