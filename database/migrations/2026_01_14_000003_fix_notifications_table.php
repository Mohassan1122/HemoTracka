<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     * Add missing columns to notifications table and fix id column type.
     */
    public function up(): void
    {
        if (Schema::hasTable('notifications')) {
            // For PostgreSQL: Convert id column from bigint to UUID if needed
            if (DB::getDriverName() === 'pgsql') {
                try {
                    // Drop the primary key constraint first
                    DB::statement("ALTER TABLE notifications DROP CONSTRAINT IF EXISTS notifications_pkey");
                    // Change id column to UUID type
                    DB::statement("ALTER TABLE notifications ALTER COLUMN id TYPE VARCHAR(36)");
                    // Recreate primary key
                    DB::statement("ALTER TABLE notifications ADD PRIMARY KEY (id)");
                } catch (\Throwable $e) {
                    // Column might already be correct type
                }
            }

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
