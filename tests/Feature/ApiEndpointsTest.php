<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Donor;
use App\Models\Organization;
use App\Models\BloodRequest;

class ApiEndpointsTest extends TestCase
{
    use RefreshDatabase; // Use transaction/refresh to keep DB clean

    public function test_auth_register_donor()
    {
        $response = $this->postJson('/api/auth/register', [
            'first_name' => 'Test',
            'last_name' => 'Donor',
            'email' => 'donor@test.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => 'donor',
            'phone' => '08012345678',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['token', 'user']);
    }

    public function test_auth_login()
    {
        $user = User::factory()->create([
            'email' => 'login@test.com',
            'password' => bcrypt('password'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'login@test.com',
            'password' => 'password',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['token', 'user']);
    }

    public function test_donor_profile_and_dashboard()
    {
        $user = User::factory()->create(['role' => 'donor']);
        $donor = Donor::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->getJson('/api/auth/profile');
        $response->assertStatus(200)
            ->assertJsonFragment(['email' => $user->email]);

        $response = $this->actingAs($user)->getJson('/api/donor/dashboard');
        $response->assertStatus(200);
    }

    public function test_create_blood_request()
    {
        $user = User::factory()->create(['role' => 'facilities']);
        $organization = Organization::factory()->create(['type' => 'Hospital']);
        // Assuming strict relationship implementation, user needs to be linked to org or have logic
        // For now, let's assume the user is just authenticated as 'facilities'

        $response = $this->actingAs($user)->postJson('/api/facilities/blood-requests', [
            'blood_group' => 'A+',
            'units_needed' => 2,
            'urgency_level' => 'Normal',
            'type' => 'Routine',
            'needed_by' => now()->addDays(1)->toDateTimeString(),
            'patient_name' => 'John Doe',
            'hospital_unit' => 'ER',
            'organization_id' => $organization->id, // If required in body
        ]);

        // Note: Logic might require user->organization() relationship to be set.
        // We will verify this if it fails.
        $response->assertStatus(201)
            ->assertJsonFragment(['blood_group' => 'A+']);
    }

    public function test_public_endpoints()
    {
        $response = $this->getJson('/api/deliveries/track/TRACK123');
        // Even if not found, it should be 404 or specific error, not 500
        $this->assertTrue(in_array($response->status(), [200, 404]));
    }

    public function test_blood_bank_inventory()
    {
        $user = User::factory()->create(['role' => 'blood_bank']);
        // Need to link user to organization? Assuming yes.
        // If Organization has users...
        // For simplicity in this iteration, we just actAs the user.
        // Assuming the controller queries InventoryItem based on logged in user's organization.

        // Let's manually link them if needed. 
        // User::factory() definition is unknown, but usually has no org_id.
        // Organization::factory() has users() hasMany relationship.

        $organization = Organization::factory()->create(['type' => 'Blood Bank']);

        // Create distinct items to avoid unique constraint 'unique_inventory' (org_id, blood_group, type)
        \App\Models\InventoryItem::factory()->create([
            'organization_id' => $organization->id,
            'blood_group' => 'A+',
            'type' => 'Whole Blood'
        ]);
        \App\Models\InventoryItem::factory()->create([
            'organization_id' => $organization->id,
            'blood_group' => 'B+',
            'type' => 'Whole Blood'
        ]);
        $user->forceFill(['organization_id' => $organization->id])->save(); // Optimistic guess

        $response = $this->actingAs($user)->getJson('/api/blood-bank/inventory');
        $response->assertStatus(200);
    }

    public function test_admin_dashboard()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->getJson('/api/admin/dashboard');
        $response->assertStatus(200);
    }
}

