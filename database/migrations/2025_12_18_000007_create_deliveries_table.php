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
        Schema::create('deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('blood_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('rider_id')->nullable()->constrained()->nullOnDelete();
            $table->text('pickup_location');
            $table->text('dropoff_location');
            $table->dateTime('pickup_time')->nullable();
            $table->dateTime('delivery_time')->nullable();
            $table->enum('status', ['Pending', 'Assigned', 'Picked Up', 'In Transit', 'Delivered', 'Returned', 'Failed'])->default('Pending');
            $table->string('tracking_code', 50)->unique()->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deliveries');
    }
};
