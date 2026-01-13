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
        Schema::create('compliance_monitoring', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('regulatory_body_id');
            $table->unsignedBigInteger('organization_id');
            $table->integer('compliance_score')->default(100);
            $table->string('inspection_id')->unique();
            $table->string('facility_type');
            $table->string('compliance_status');
            $table->timestamp('last_inspection_date')->nullable();
            $table->timestamp('next_inspection_date')->nullable();
            $table->integer('violations_found')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('regulatory_body_id')->references('id')->on('regulatory_bodies')->onDelete('cascade');
            $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('cascade');

            // Unique constraint
            $table->unique(['regulatory_body_id', 'organization_id']);

            // Indexes
            $table->index('compliance_score');
            $table->index('organization_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('compliance_monitoring');
    }
};
