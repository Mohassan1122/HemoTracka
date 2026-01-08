<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('states', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('code', 10)->unique(); // e.g., "LA", "CA"
            $table->string('region')->nullable(); // e.g., "South", "North"
            $table->timestamps();
        });

        // Seed basic Nigerian states
        Schema::table('states', function (Blueprint $table) {
            // We'll seed these after migration
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('states');
    }
};
