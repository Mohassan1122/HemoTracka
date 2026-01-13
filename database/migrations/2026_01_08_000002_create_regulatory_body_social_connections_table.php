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
        Schema::create('regulatory_body_social_connections', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('regulatory_body_id');
            $table->string('platform'); // instagram, twitter, facebook, linkedin
            $table->string('handle');
            $table->string('url')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->timestamps();

            // Foreign keys
            $table->foreign('regulatory_body_id', 'reg_body_soc_conn_fk')->references('id')->on('regulatory_bodies')->onDelete('cascade');

            // Unique constraint
            $table->unique(['regulatory_body_id', 'platform'], 'reg_body_soc_conn_unique');

            // Indexes
            $table->index('platform');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('regulatory_body_social_connections');
    }
};
