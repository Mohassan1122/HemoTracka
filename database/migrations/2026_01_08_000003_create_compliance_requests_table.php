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
        Schema::create('compliance_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('regulatory_body_id');
            $table->unsignedBigInteger('organization_id');
            $table->enum('organization_type', ['blood_bank', 'health_facility']);
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->timestamp('requested_at')->useCurrent();
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->unsignedBigInteger('reviewed_by_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('regulatory_body_id')->references('id')->on('regulatory_bodies')->onDelete('cascade');
            $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('cascade');
            $table->foreign('reviewed_by_id')->references('id')->on('users')->onDelete('set null');

            // Indexes
            $table->index('status');
            $table->index('organization_id');
            $table->index('regulatory_body_id');
            $table->index('requested_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('compliance_requests');
    }
};
