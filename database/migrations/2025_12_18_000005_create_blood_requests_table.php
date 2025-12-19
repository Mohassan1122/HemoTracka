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
        Schema::create('blood_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('blood_group', 5);
            $table->integer('units_needed');
            $table->enum('type', ['Emergent', 'Bulk', 'Routine'])->default('Routine');
            $table->enum('urgency_level', ['Critical', 'High', 'Normal'])->default('Normal');
            $table->dateTime('needed_by');
            $table->enum('status', ['Pending', 'Approved', 'Sourcing', 'In Transit', 'Completed', 'Cancelled'])->default('Pending');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('blood_requests');
    }
};
