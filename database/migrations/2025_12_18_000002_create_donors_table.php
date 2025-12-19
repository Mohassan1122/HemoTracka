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
        Schema::create('donors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->string('first_name');
            $table->string('last_name');
            $table->enum('blood_group', ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-']);
            $table->string('genotype', 10)->nullable();
            $table->date('date_of_birth');
            $table->date('last_donation_date')->nullable();
            $table->text('address')->nullable();
            $table->string('phone', 20);
            $table->text('notes')->nullable();
            $table->enum('status', ['Eligible', 'Permanently Deferral', 'Temporary Deferral'])->default('Eligible');
            $table->timestamps();
            $table->softDeletes();

            $table->index('blood_group');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('donors');
    }
};
