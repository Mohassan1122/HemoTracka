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
        Schema::table('donors', function (Blueprint $table) {
            $table->string('other_names')->nullable()->after('last_name');
            $table->string('instagram_handle')->nullable()->after('notes');
            $table->string('twitter_handle')->nullable()->after('instagram_handle');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('donors', function (Blueprint $table) {
            $table->dropColumn(['other_names', 'instagram_handle', 'twitter_handle']);
        });
    }
};
