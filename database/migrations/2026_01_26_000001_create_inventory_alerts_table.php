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
        Schema::create('inventory_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inventory_item_id')->nullable()->constrained('inventory_items')->cascadeOnDelete();

            // Alert details
            $table->enum('alert_type', [
                'low_stock',
                'expiring_soon',
                'expired',
                'critical_shortage',
                'out_of_stock'
            ]);
            $table->enum('severity', ['info', 'warning', 'critical'])->default('info');

            $table->string('title');
            $table->text('message');

            // Status tracking
            $table->boolean('is_read')->default(false);
            $table->boolean('is_acknowledged')->default(false);
            $table->foreignId('acknowledged_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('acknowledged_at')->nullable();
            $table->text('acknowledgment_notes')->nullable();

            // Email notification tracking
            $table->boolean('email_sent')->default(false);
            $table->timestamp('email_sent_at')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['organization_id', 'is_read']);
            $table->index(['alert_type', 'severity']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_alerts');
    }
};
