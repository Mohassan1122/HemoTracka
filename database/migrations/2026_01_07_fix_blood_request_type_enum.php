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
        // For PostgreSQL, we need to drop and recreate the enum type
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE blood_requests DROP CONSTRAINT IF EXISTS blood_requests_type_check");
            DB::statement("ALTER TABLE blood_requests ALTER COLUMN type TYPE varchar(50)");
            DB::statement("ALTER TABLE blood_requests ADD CONSTRAINT blood_requests_type_check CHECK (type IN ('Blood', 'Platelets', 'Bone Marrow'))");
        } else {
            // For MySQL, modify the column
            Schema::table('blood_requests', function (Blueprint $table) {
                // Change the enum type to accept new values
                // Note: In MySQL, we need to use raw SQL for this
            });
            DB::statement("ALTER TABLE blood_requests MODIFY COLUMN type ENUM('Blood', 'Platelets', 'Bone Marrow') NOT NULL DEFAULT 'Blood'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE blood_requests DROP CONSTRAINT IF EXISTS blood_requests_type_check");
            DB::statement("ALTER TABLE blood_requests ALTER COLUMN type TYPE varchar(50)");
            DB::statement("ALTER TABLE blood_requests ADD CONSTRAINT blood_requests_type_check CHECK (type IN ('Emergent', 'Bulk', 'Routine'))");
        } else {
            DB::statement("ALTER TABLE blood_requests MODIFY COLUMN type ENUM('Emergent', 'Bulk', 'Routine') NOT NULL DEFAULT 'Routine'");
        }
    }
};
