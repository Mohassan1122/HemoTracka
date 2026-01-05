<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     * 
     * Updates the role column to differentiate between:
     * - blood_bank_staff: Staff working at Blood Banks (provide/sell blood)
     * - hospital_staff: Staff working at Hospitals/Healthcare facilities (request blood)
     * - regulator: Staff at Regulatory Bodies (oversight/compliance)
     */
    public function up(): void
    {
        // For MySQL, we need to alter the enum
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'staff', 'donor', 'rider', 'regulator', 'blood_bank_staff', 'hospital_staff')");

        // Migrate existing 'staff' users based on their organization type
        // staff at Blood Bank organizations -> blood_bank_staff
        // staff at Hospital organizations -> hospital_staff
        // staff at Regulatory Body -> regulator
        DB::statement("
            UPDATE users u
            INNER JOIN organizations o ON u.organization_id = o.id
            SET u.role = CASE 
                WHEN o.type = 'Blood Bank' THEN 'blood_bank_staff'
                WHEN o.type = 'Hospital' THEN 'hospital_staff'
                WHEN o.type = 'Regulatory Body' THEN 'regulator'
                ELSE u.role
            END
            WHERE u.role = 'staff'
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Convert back to original roles
        DB::statement("
            UPDATE users 
            SET role = 'staff' 
            WHERE role IN ('blood_bank_staff', 'hospital_staff')
        ");

        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'staff', 'donor', 'rider', 'regulator') DEFAULT 'donor'");
    }
};
