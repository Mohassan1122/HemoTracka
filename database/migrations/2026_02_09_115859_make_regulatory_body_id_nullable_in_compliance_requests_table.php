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
        // Make regulatory_body_id NULLABLE
        DB::statement("ALTER TABLE compliance_requests MODIFY regulatory_body_id BIGINT UNSIGNED NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to NOT NULL
        // Note: This might fail if there are NULL values in the table.
        DB::statement("ALTER TABLE compliance_requests MODIFY regulatory_body_id BIGINT UNSIGNED NOT NULL");
    }
};
