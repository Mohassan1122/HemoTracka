<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     * Convert compliance_requests enum columns to string to fix constraints.
     */
    public function up(): void
    {
        // Only run on PostgreSQL
        if (DB::getDriverName() === 'pgsql') {
            // Drop all constraints on compliance_requests
            $constraints = DB::select("
                SELECT con.conname
                FROM pg_catalog.pg_constraint con
                INNER JOIN pg_catalog.pg_class rel ON rel.oid = con.conrelid
                WHERE rel.relname = 'compliance_requests'
                AND con.contype = 'c'
            ");

            foreach ($constraints as $constraint) {
                try {
                    DB::statement("ALTER TABLE compliance_requests DROP CONSTRAINT IF EXISTS {$constraint->conname}");
                } catch (\Throwable $e) {
                    // Continue even if one fails
                }
            }

            // Convert status column to VARCHAR
            try {
                DB::statement("ALTER TABLE compliance_requests ALTER COLUMN status TYPE VARCHAR(50) USING status::VARCHAR");
            } catch (\Throwable $e) {
                // Column might already be varchar
            }

            // Convert organization_type column to VARCHAR
            try {
                DB::statement("ALTER TABLE compliance_requests ALTER COLUMN organization_type TYPE VARCHAR(50) USING organization_type::VARCHAR");
            } catch (\Throwable $e) {
                // Column might already be varchar
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No rollback needed
    }
};
