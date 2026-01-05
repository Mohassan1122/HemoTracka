<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * Convert legacy 'staff' role into specific string roles using portable SQL.
     * Removes any dependency on database ENUM types.
     */
    public function up(): void
    {
        // Update roles using a portable UPDATE with correlated subquery (works on PostgreSQL)
        DB::statement(<<<SQL
            UPDATE users AS u
            SET role = CASE
                WHEN (
                    SELECT o.type FROM organizations o WHERE o.id = u.organization_id
                ) = 'Blood Bank' THEN 'blood_bank_staff'
                WHEN (
                    SELECT o.type FROM organizations o WHERE o.id = u.organization_id
                ) = 'Hospital' THEN 'hospital_staff'
                WHEN (
                    SELECT o.type FROM organizations o WHERE o.id = u.organization_id
                ) = 'Regulatory Body' THEN 'regulator'
                ELSE u.role
            END
            WHERE u.role = 'staff';
        SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Collapse specialized staff roles back to 'staff' using portable SQL.
        DB::statement(<<<SQL
            UPDATE users
            SET role = 'staff'
            WHERE role IN ('blood_bank_staff', 'hospital_staff', 'regulator');
        SQL);
    }
};
