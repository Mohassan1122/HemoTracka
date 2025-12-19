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
        Schema::table('blood_requests', function (Blueprint $table) {
            $table->string('source_type')->default('Hospital')->after('hospital_unit'); // Hospital or Blood Bank
            $table->string('bone_marrow_type')->nullable()->after('type');
            $table->string('platelets_type')->nullable()->after('bone_marrow_type');

            $table->decimal('product_fee', 12, 2)->default(0)->after('status');
            $table->decimal('shipping_fee', 12, 2)->default(0)->after('product_fee');
            $table->decimal('card_charge', 12, 2)->default(0)->after('shipping_fee');
            $table->decimal('total_amount', 12, 2)->default(0)->after('card_charge');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('blood_requests', function (Blueprint $table) {
            $table->dropColumn([
                'source_type',
                'bone_marrow_type',
                'platelets_type',
                'product_fee',
                'shipping_fee',
                'card_charge',
                'total_amount'
            ]);
        });
    }
};
