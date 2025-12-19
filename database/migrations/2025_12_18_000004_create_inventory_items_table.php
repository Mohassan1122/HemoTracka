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
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->enum('blood_group', ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-']);
            $table->enum('type', ['Whole Blood', 'RBC', 'PLT', 'FFP', 'Cryo'])->default('Whole Blood');
            $table->integer('units_in_stock')->default(0);
            $table->integer('threshold')->default(10);
            $table->string('location', 100)->nullable();
            $table->date('expiry_date')->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'blood_group', 'type'], 'unique_inventory');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_items');
    }
};
