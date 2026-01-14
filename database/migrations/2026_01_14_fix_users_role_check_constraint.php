<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     * Fix the users_role_check constraint to include regulatory_body role.
     */
    public function up(): void
    {
        // Drop existing constraint first
        try {
            DB::statement("ALTER TABLE users DROP CONSTRAINT IF EXISTS users_role_check");
        } catch (\Throwable $e) {
            // Ignore if constraint doesn't exist
        }

        // Recreate with all valid roles including regulatory_body
        try {
            DB::statement("ALTER TABLE users ADD CONSTRAINT users_role_check CHECK (role IN ('admin','donor','rider','facilities','blood_banks','regulatory_body'))");
        } catch (\Throwable $e) {
            // Ignore if DB doesn't support this
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Just drop the constraint on rollback
        try {
            DB::statement("ALTER TABLE users DROP CONSTRAINT IF EXISTS users_role_check");
        } catch (\Throwable $e) {
            // Ignore
        }
    }
};
