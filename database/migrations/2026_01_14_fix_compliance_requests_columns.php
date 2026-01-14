<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     * Fix all missing columns in compliance_requests table.
     */
    public function up(): void
    {
        Schema::table('compliance_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('compliance_requests', 'request_type')) {
                $table->string('request_type')->nullable()->after('organization_type');
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

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Don't drop columns on rollback - too risky
    }
};
