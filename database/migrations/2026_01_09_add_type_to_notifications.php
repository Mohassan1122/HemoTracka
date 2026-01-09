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
        // Check if notifications table exists and add missing columns
        if (Schema::hasTable('notifications')) {
            Schema::table('notifications', function (Blueprint $table) {
                if (!Schema::hasColumn('notifications', 'type')) {
                    $table->string('type')->after('id');
                }
                if (!Schema::hasColumn('notifications', 'data')) {
                    $table->text('data')->nullable();
                }
                if (!Schema::hasColumn('notifications', 'read_at')) {
                    $table->timestamp('read_at')->nullable();
                }
                if (!Schema::hasColumn('notifications', 'notifiable_id')) {
                    $table->uuid('notifiable_id');
                }
                if (!Schema::hasColumn('notifications', 'notifiable_type')) {
                    $table->string('notifiable_type');
                }
                if (!Schema::hasColumn('notifications', 'created_at')) {
                    $table->timestamp('created_at')->nullable();
                }
                if (!Schema::hasColumn('notifications', 'updated_at')) {
                    $table->timestamp('updated_at')->nullable();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
