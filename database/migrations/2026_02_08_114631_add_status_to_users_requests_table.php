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
        Schema::table('users_requests', function (Blueprint $table) {
            $table->enum('status', ['Pending', 'Responded', 'Fulfilled'])
                ->default('Pending')
                ->after('is_read')
                ->comment('Status of the user request: Pending, Responded, or Fulfilled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users_requests', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
