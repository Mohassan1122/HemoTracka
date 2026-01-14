<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     * Add missing columns to notifications table.
     */
    public function up(): void
    {
        if (Schema::hasTable('notifications')) {
            Schema::table('notifications', function (Blueprint $table) {
                if (!Schema::hasColumn('notifications', 'notifiable_type')) {
                    $table->string('notifiable_type')->nullable()->after('type');
                }
                if (!Schema::hasColumn('notifications', 'notifiable_id')) {
                    $table->unsignedBigInteger('notifiable_id')->nullable()->after('notifiable_type');
                }
                if (!Schema::hasColumn('notifications', 'data')) {
                    $table->text('data')->nullable();
                }
                if (!Schema::hasColumn('notifications', 'read_at')) {
                    $table->timestamp('read_at')->nullable();
                }
            });

            // Add index for notifiable morph
            try {
                Schema::table('notifications', function (Blueprint $table) {
                    $table->index(['notifiable_type', 'notifiable_id'], 'notifications_notifiable_index');
                });
            } catch (\Throwable $e) {
                // Index might already exist
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No rollback
    }
};
