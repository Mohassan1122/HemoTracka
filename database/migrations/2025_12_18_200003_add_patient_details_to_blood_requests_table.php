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
            $table->string('patient_name')->nullable()->after('units_needed');
            $table->string('hospital_unit')->nullable()->after('patient_name'); // e.g. Ward, ICU, ER
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('blood_requests', function (Blueprint $table) {
            $table->dropColumn(['patient_name', 'hospital_unit']);
        });
    }
};
