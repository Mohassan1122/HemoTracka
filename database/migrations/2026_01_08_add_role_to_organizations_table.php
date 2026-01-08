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
        Schema::table('organizations', function (Blueprint $table) {
            // Add role field to match User model - helps frontend differentiate entity types
            // Values: 'blood_banks', 'facilities' (hospitals)
            $table->string('role')->nullable()->after('type')->comment('Role for entity differentiation: blood_banks or facilities');
        });

        // Update existing organizations based on their type
        // This ensures backward compatibility
        DB::table('organizations')
            ->where('type', 'Blood Bank')
            ->update(['role' => 'blood_banks']);

        DB::table('organizations')
            ->where('type', 'Hospital')
            ->update(['role' => 'facilities']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }
};
