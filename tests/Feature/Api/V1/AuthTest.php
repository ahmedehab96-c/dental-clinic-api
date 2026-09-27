<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_can_register_and_receives_a_patient_role(): void
    {
        $response = $this->postJson('/api/v1/register', [
            'name' => 'New Patient',
            'email' => 'new@example.com',
            'phone' => '0551234567',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.user.role', 'patient')
            ->assertJsonStructure(['data' => ['user' => ['id', 'name', 'email', 'role'], 'token']]);

        $this->assertDatabaseHas('users', ['email' => 'new@example.com', 'role' => 'patient']);
    }

    public function test_registration_cannot_set_role_via_mass_assignment(): void
    {
        $this->postJson('/api/v1/register', [
            'name' => 'Sneaky',
            'email' => 'sneaky@example.com',
            'phone' => '0551234567',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'admin',
        ]);

        $this->assertDatabaseHas('users', ['email' => 'sneaky@example.com', 'role' => 'patient']);
    }

    public function test_a_user_can_log_in_with_correct_credentials(): void
    {
        User::factory()->create(['email' => 'user@example.com', 'password' => Hash::make('secret123')]);

        $this->postJson('/api/v1/login', ['email' => 'user@example.com', 'password' => 'secret123'])
            ->assertOk()
            ->assertJsonStructure(['data' => ['user', 'token']]);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        User::factory()->create(['email' => 'user@example.com', 'password' => Hash::make('secret123')]);

        $this->postJson('/api/v1/login', ['email' => 'user@example.com', 'password' => 'wrong'])
            ->assertStatus(422);
    }

    public function test_me_requires_authentication(): void
    {
        $this->getJson('/api/v1/me')
            ->assertStatus(401)
            ->assertJsonPath('success', false);
    }

    public function test_me_returns_the_authenticated_user(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/me')
            ->assertOk()
            ->assertJsonPath('data.id', $user->id);
    }
}
