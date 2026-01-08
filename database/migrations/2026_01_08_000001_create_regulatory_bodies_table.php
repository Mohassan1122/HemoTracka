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
        Schema::create('regulatory_bodies', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->unique();
            $table->string('institution_name');
            $table->string('license_number')->unique();
            $table->enum('level', ['federal', 'state'])->default('state');
            $table->unsignedBigInteger('state_id')->nullable();
            $table->string('email');
            $table->string('phone_number')->nullable();
            $table->text('address')->nullable();
            $table->string('work_days')->nullable(); // e.g., "Mon-Sat"
            $table->string('work_hours')->nullable(); // e.g., "8am-6pm"
            $table->string('company_website')->nullable();
            $table->string('profile_picture_url')->nullable();
            $table->string('cover_picture_url')->nullable();
            $table->json('notification_preferences')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Foreign keys
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('state_id')->references('id')->on('states')->onDelete('set null');

            // Indexes
            $table->index('level');
            $table->index('state_id');
            $table->index('license_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('regulatory_bodies');
    }
};
