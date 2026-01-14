<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     * Add missing url column to regulatory_body_social_connections table.
     */
    public function up(): void
    {
        Schema::table('regulatory_body_social_connections', function (Blueprint $table) {
            if (!Schema::hasColumn('regulatory_body_social_connections', 'url')) {
                $table->string('url')->nullable()->after('handle');
            }
            if (!Schema::hasColumn('regulatory_body_social_connections', 'is_verified')) {
                $table->boolean('is_verified')->default(false)->after('url');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('regulatory_body_social_connections', function (Blueprint $table) {
            if (Schema::hasColumn('regulatory_body_social_connections', 'url')) {
                $table->dropColumn('url');
            }
            if (Schema::hasColumn('regulatory_body_social_connections', 'is_verified')) {
                $table->dropColumn('is_verified');
            }
        });
    }
};
