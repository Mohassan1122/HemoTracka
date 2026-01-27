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
        // Add quality control and traceability fields to inventory_items
        Schema::table('inventory_items', function (Blueprint $table) {
            // Quality control
            $table->enum('quality_status', ['pending', 'passed', 'failed', 'quarantine'])->default('pending');
            $table->timestamp('quality_checked_at')->nullable();
            $table->foreignId('quality_checked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('quality_notes')->nullable();

            // Donor traceability
            $table->foreignId('donor_id')->nullable()->constrained('donors')->nullOnDelete();
            $table->foreignId('donation_id')->nullable()->constrained('donations')->nullOnDelete();

            // Blood component separation tracking
            $table->foreignId('parent_item_id')->nullable()->constrained('inventory_items')->nullOnDelete();
            $table->boolean('is_component')->default(false);
            $table->string('component_type')->nullable(); // RBC, Plasma, Platelets, etc.
            $table->timestamp('separated_at')->nullable();

            // Temperature monitoring
            $table->decimal('last_recorded_temp', 5, 2)->nullable();
            $table->timestamp('temp_recorded_at')->nullable();
            $table->boolean('temp_breach')->default(false);
        });

        // Create batch_components table for tracking component relationships
        Schema::create('batch_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_inventory_id')->constrained('inventory_items')->cascadeOnDelete();
            $table->foreignId('child_inventory_id')->constrained('inventory_items')->cascadeOnDelete();
            $table->string('separation_method')->nullable();
            $table->decimal('volume_ml', 8, 2)->nullable();
            $table->timestamp('separated_at')->nullable();
            $table->foreignId('separated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['parent_inventory_id', 'child_inventory_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('batch_components');

        Schema::table('inventory_items', function (Blueprint $table) {
            $table->dropForeign(['quality_checked_by']);
            $table->dropForeign(['donor_id']);
            $table->dropForeign(['donation_id']);
            $table->dropForeign(['parent_item_id']);

            $table->dropColumn([
                'quality_status',
                'quality_checked_at',
                'quality_checked_by',
                'quality_notes',
                'donor_id',
                'donation_id',
                'parent_item_id',
                'is_component',
                'component_type',
                'separated_at',
                'last_recorded_temp',
                'temp_recorded_at',
                'temp_breach',
            ]);
        });
    }
};
