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
            $table->decimal('latitude', 10, 8)->nullable()->after('logo');
            $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
            $table->json('operating_hours')->nullable()->after('longitude');
            $table->text('description')->nullable()->after('operating_hours');
            $table->json('services')->nullable()->after('description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude', 'operating_hours', 'description', 'services']);
        });
    }
};
