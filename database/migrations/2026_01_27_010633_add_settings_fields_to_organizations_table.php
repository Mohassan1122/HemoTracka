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
        Schema::table('organizations', function (Blueprint $table) {
            if (!Schema::hasColumn('organizations', 'receive_notifications')) {
                $table->boolean('receive_notifications')->default(true)->after('status');
            }
            if (!Schema::hasColumn('organizations', 'show_inventory')) {
                $table->boolean('show_inventory')->default(false)->after('receive_notifications');
            }
            if (!Schema::hasColumn('organizations', 'show_contact')) {
                $table->boolean('show_contact')->default(true)->after('show_inventory');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn(['receive_notifications', 'show_inventory', 'show_contact']);
        });
    }
};
