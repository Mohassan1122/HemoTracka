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
        Schema::table('appointments', function (Blueprint $table) {
            // Link appointment to a specific blood request (for donor responses)
            $table->foreignId('user_request_id')->nullable()->after('organization_id')->constrained('users_requests')->onDelete('set null');

            // Store blood group and genotype on appointment (useful for filtering)
            $table->string('blood_group', 10)->nullable()->after('donation_type');
            $table->string('genotype', 10)->nullable()->after('blood_group');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropForeign(['user_request_id']);
            $table->dropColumn(['user_request_id', 'blood_group', 'genotype']);
        });
    }
};
