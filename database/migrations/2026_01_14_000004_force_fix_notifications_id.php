<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     * Convert notifications id column from bigint to varchar for UUIDs.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            // Drop existing data first (seeder will repopulate)
            DB::table('notifications')->truncate();

            // Drop the primary key constraint
            try {
                DB::statement("ALTER TABLE notifications DROP CONSTRAINT IF EXISTS notifications_pkey");
            } catch (\Throwable $e) {
                // Constraint might not exist
            }

            // Change id column to VARCHAR type
            try {
                DB::statement("ALTER TABLE notifications ALTER COLUMN id TYPE VARCHAR(36)");
            } catch (\Throwable $e) {
                // If that fails, try dropping and recreating the column
                try {
                    DB::statement("ALTER TABLE notifications DROP COLUMN id");
                    DB::statement("ALTER TABLE notifications ADD COLUMN id VARCHAR(36) PRIMARY KEY");
                } catch (\Throwable $e2) {
                    // Last resort - ignore
                }
            }

            // Recreate primary key if we just changed the type
            try {
                DB::statement("ALTER TABLE notifications ADD PRIMARY KEY (id)");
            } catch (\Throwable $e) {
                // Primary key might already exist
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No rollback
    }
};
