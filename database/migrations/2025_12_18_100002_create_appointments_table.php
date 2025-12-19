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
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('donor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->date('appointment_date');
            $table->time('appointment_time');
            $table->enum('status', ['Scheduled', 'Confirmed', 'Completed', 'Cancelled', 'No-Show'])->default('Scheduled');
            $table->enum('donation_type', ['Whole Blood', 'Plasma', 'Platelets', 'Double Red Cells'])->default('Whole Blood');
            $table->text('notes')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['donor_id', 'appointment_date']);
            $table->index(['organization_id', 'appointment_date']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
