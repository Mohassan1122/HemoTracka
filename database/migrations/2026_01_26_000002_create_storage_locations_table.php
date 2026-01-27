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
        Schema::create('storage_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();

            $table->string('name');
            $table->enum('location_type', ['room', 'fridge', 'freezer', 'shelf', 'container']);
            $table->foreignId('parent_location_id')->nullable()->constrained('storage_locations')->nullOnDelete();

            $table->integer('capacity')->nullable(); // max units
            $table->integer('current_load')->default(0);

            // Temperature monitoring
            $table->decimal('min_temperature', 5, 2)->nullable();
            $table->decimal('max_temperature', 5, 2)->nullable();
            $table->decimal('current_temperature', 5, 2)->nullable();

            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['organization_id', 'location_type']);
        });

        // Add location_id to inventory_items if it doesn't exist logically linked yet
        // Note: inventory_items currently has a string 'location' column. 
        // We will keep 'location' for backward compatibility or simple usage, 
        // but add 'storage_location_id' for structured tracking.
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->foreignId('storage_location_id')->nullable()->constrained('storage_locations')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->dropForeign(['storage_location_id']);
            $table->dropColumn('storage_location_id');
        });
        Schema::dropIfExists('storage_locations');
    }
};
