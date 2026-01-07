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
        Schema::create('users_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('blood_request_id')->constrained('blood_requests')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('request_source', ['donors', 'blood_banks', 'both'])->comment('Source of the request: donors, blood_banks, or both');
            $table->boolean('is_read')->default(false)->comment('Whether the user has read this request');
            $table->timestamps();

            // Composite unique constraint to prevent duplicate requests for the same user
            $table->unique(['blood_request_id', 'user_id']);
            $table->index(['user_id', 'is_read']);
            $table->index(['blood_request_id', 'request_source']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users_requests');
    }
};
