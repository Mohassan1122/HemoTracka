<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('donor')->change();
        });

        // Optional: add a PostgreSQL CHECK constraint to restrict role values without ENUMs
        // This is safe to run in PostgreSQL; on other DBs it will be ignored if not supported
        try {
            DB::statement("ALTER TABLE users ADD CONSTRAINT users_role_check CHECK (role IN ('admin','donor','rider','facilities','blood_banks','regulatory_body'))");
        } catch (\Throwable $e) {
            // Ignore if the DB doesn't support adding this constraint this way
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the optional CHECK constraint if it exists (PostgreSQL-safe)
        try {
            DB::statement("ALTER TABLE users DROP CONSTRAINT IF EXISTS users_role_check");
        } catch (\Throwable $e) {
            // ignore
        }

        // Revert to a generic string without ENUM to keep rollback portable
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('donor')->change();
        });
    }
};
