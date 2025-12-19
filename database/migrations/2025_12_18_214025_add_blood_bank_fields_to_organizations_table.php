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
            $table->string('cover_photo')->nullable()->after('logo');
            $table->string('facebook_link')->nullable()->after('services');
            $table->string('twitter_link')->nullable()->after('facebook_link');
            $table->string('instagram_link')->nullable()->after('twitter_link');
            $table->string('linkedin_link')->nullable()->after('instagram_link');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn(['cover_photo', 'facebook_link', 'twitter_link', 'instagram_link', 'linkedin_link']);
        });
    }
};
