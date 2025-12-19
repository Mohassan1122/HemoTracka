<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     * 
     * User Roles:
     * - admin: System administrators
     * - donor: Blood donors (individuals)
     * - rider: Delivery riders
     * - facilities: Regulatory bodies/Hospitals (entities that REQUEST blood)
     * - blood_banks: Blood Banks (entities that PROVIDE/store blood)
     */
    public function up(): void
    {
        // First expand to include all possible values
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'staff', 'donor', 'rider', 'regulator', 'blood_bank_staff', 'hospital_staff', 'facilities', 'blood_banks') DEFAULT 'donor'");

        // Convert existing roles to new ones based on organization type
        DB::statement("
            UPDATE users u
            LEFT JOIN organizations o ON u.organization_id = o.id
            SET u.role = CASE 
                WHEN u.role IN ('blood_bank_staff') OR (u.role = 'staff' AND o.type = 'Blood Bank') THEN 'blood_banks'
                WHEN u.role IN ('hospital_staff', 'regulator') OR (u.role = 'staff' AND o.type IN ('Hospital', 'Regulatory Body')) THEN 'facilities'
                WHEN u.role = 'staff' THEN 'admin'
                ELSE u.role
            END
            WHERE u.role IN ('staff', 'regulator', 'blood_bank_staff', 'hospital_staff')
        ");

        // Now set the final enum with only the 5 roles
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'donor', 'rider', 'facilities', 'blood_banks') DEFAULT 'donor'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'staff', 'donor', 'rider', 'regulator', 'blood_bank_staff', 'hospital_staff') DEFAULT 'donor'");
    }
};
