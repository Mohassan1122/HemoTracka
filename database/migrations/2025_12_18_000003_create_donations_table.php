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
        Schema::create('donations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('donor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('blood_group', 5);
            $table->integer('units')->default(1);
            $table->date('donation_date');
            $table->text('notes')->nullable();
            $table->enum('status', ['Pending', 'Screened', 'Stored', 'Discarded', 'Used'])->default('Pending');
            $table->timestamps();

            $table->index('donation_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('donations');
    }
};
