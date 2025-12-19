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
        Schema::table('deliveries', function (Blueprint $table) {
            $table->json('status_history')->nullable()->after('status'); // Timeline: [{status: 'Order Taken', time: '...'}, ...]
            $table->timestamp('receiver_confirmed_at')->nullable()->after('delivery_time');
        });
    }

    public function down(): void
    {
        Schema::table('deliveries', function (Blueprint $table) {
            $table->dropColumn(['status_history', 'receiver_confirmed_at']);
        });
    }
};
