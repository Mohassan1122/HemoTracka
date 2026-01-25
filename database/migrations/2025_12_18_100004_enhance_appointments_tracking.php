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
        Schema::table('appointments', function (Blueprint $table) {
            $table->unsignedBigInteger('accepted_by')->nullable()->after('status');
            $table->timestamp('accepted_at')->nullable()->after('accepted_by');
            $table->unsignedBigInteger('rejected_by')->nullable()->after('accepted_at');
            $table->timestamp('rejected_at')->nullable()->after('rejected_by');
            $table->unsignedBigInteger('updated_by')->nullable()->after('rejected_at');

            // Add foreign keys
            $table->foreign('accepted_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('rejected_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropForeign(['accepted_by']);
            $table->dropForeign(['rejected_by']);
            $table->dropForeign(['updated_by']);

            $table->dropColumn(['accepted_by', 'accepted_at', 'rejected_by', 'rejected_at', 'updated_by']);
        });
    }
};
