<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Plan;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed plans for testing
        Plan::create([
            'slug' => 'gold',
            'name' => 'Gold',
            'price' => 2499,
            'period' => '6 months',
            'color' => 'gold-color',
            'popular' => true,
            'features' => ['Feature 1', 'Feature 2'],
        ]);
    }

    public function test_public_plans_endpoint(): void
    {
        $response = $this->getJson('/api/plans');

        $response->assertStatus(200)
                 ->assertJsonFragment(['slug' => 'gold']);
    }

    public function test_member_registration_and_login_flow(): void
    {
        // 1. Register
        $regResponse = $this->postJson('/api/auth/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '+91 9876543210',
            'gender' => 'male',
            'dob' => '1995-05-15',
            'password' => 'password123',
        ]);

        $regResponse->assertStatus(201)
                   ->assertJsonStructure(['message', 'user_id']);

        // 2. Login
        $loginResponse = $this->postJson('/api/auth/login', [
            'login' => 'john@example.com',
            'password' => 'password123',
        ]);

        $loginResponse->assertStatus(200)
                     ->assertJsonStructure(['token', 'user']);
    }

    public function test_auth_protected_profile_endpoint(): void
    {
        $user = User::factory()->create([
            'role' => 'member',
        ]);
        $user->profile()->create([
            'display_id' => 'M9999',
            'country' => 'India',
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/profile');

        $response->assertStatus(200)
                 ->assertJsonFragment(['id' => 'M9999']);
    }
}
