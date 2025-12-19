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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('blood_request_id')->nullable()->constrained()->onDelete('cascade');
            $table->decimal('amount', 12, 2);
            $table->string('payment_method'); // Card, Bank Transfer, POD
            $table->string('status')->default('Pending'); // Pending, Completed, Failed
            $table->string('transaction_reference')->unique()->nullable();
            $table->json('payment_details')->nullable(); // Store simulated card info or provider response
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
