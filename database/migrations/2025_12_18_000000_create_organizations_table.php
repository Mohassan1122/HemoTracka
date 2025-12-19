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
        Schema::create('organizations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['Hospital', 'Blood Bank', 'Regulatory Body', 'Logistics']);
            $table->string('license_number', 100)->unique();
            $table->text('address');
            $table->string('contact_email');
            $table->string('phone', 20);
            $table->string('logo')->nullable();
            $table->enum('status', ['Pending', 'Active', 'Suspended'])->default('Pending');
            $table->timestamps();
            $table->softDeletes();

            $table->index('type');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('organizations');
    }
};
