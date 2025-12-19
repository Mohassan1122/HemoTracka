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
        Schema::create('donor_badges', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description');
            $table->string('icon')->nullable();
            $table->string('color')->default('#3B82F6');
            $table->enum('criteria_type', ['donation_count', 'units_donated', 'consecutive_donations', 'first_donation', 'referral_count', 'blood_type_rare']);
            $table->integer('criteria_value')->default(1);
            $table->integer('points')->default(10);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Pivot table for donor-badge relationship
        Schema::create('donor_badge_donor', function (Blueprint $table) {
            $table->id();
            $table->foreignId('donor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('donor_badge_id')->constrained()->cascadeOnDelete();
            $table->timestamp('earned_at');
            $table->timestamps();

            $table->unique(['donor_id', 'donor_badge_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('donor_badge_donor');
        Schema::dropIfExists('donor_badges');
    }
};
