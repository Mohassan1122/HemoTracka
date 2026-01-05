<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * Consolidate roles into the final 5 string roles without ENUMs.
     */
    public function up(): void
    {
        DB::statement(<<<SQL
            UPDATE users AS u
            SET role = CASE
                WHEN u.role IN ('blood_bank_staff') OR (
                    (
                        SELECT o.type FROM organizations o WHERE o.id = u.organization_id
                    ) = 'Blood Bank' AND u.role = 'staff'
                ) THEN 'blood_banks'
                WHEN u.role IN ('hospital_staff', 'regulator') OR (
                    (
                        SELECT o.type FROM organizations o WHERE o.id = u.organization_id
                    ) IN ('Hospital', 'Regulatory Body') AND u.role = 'staff'
                ) THEN 'facilities'
                WHEN u.role = 'staff' THEN 'admin'
                ELSE u.role
            END
            WHERE u.role IN ('staff', 'regulator', 'blood_bank_staff', 'hospital_staff');
        SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Best-effort rollback: map back to broader categories (string values)
        DB::statement(<<<SQL
            UPDATE users
            SET role = CASE
                WHEN role = 'blood_banks' THEN 'blood_bank_staff'
                WHEN role = 'facilities' THEN 'hospital_staff'
                ELSE role
            END
            WHERE role IN ('blood_banks', 'facilities');
        SQL);
    }
};
