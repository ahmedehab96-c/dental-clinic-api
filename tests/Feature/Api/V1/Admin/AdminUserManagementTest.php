<?php

namespace Tests\Feature\Api\V1\Admin;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Layla Hassan',
            'email' => 'layla@example.com',
            'phone' => '0551234567',
            'role' => 'patient',
            'password' => 'secret-pass-1',
            'password_confirmation' => 'secret-pass-1',
        ], $overrides);
    }

    private function appointmentFor(User $user): Appointment
    {
        return Appointment::factory()->create([
            'user_id' => $user->id,
            'doctor_id' => Doctor::factory()->create()->id,
            'service_id' => Service::factory()->create()->id,
        ]);
    }

    public function test_admin_can_list_users_with_pagination(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->count(3)->create();

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/users?per_page=2')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 4);
    }

    public function test_admin_can_search_users_by_name_or_email(): void
    {
        $admin = User::factory()->admin()->create(['name' => 'Admin', 'email' => 'root@example.com']);
        User::factory()->create(['name' => 'Sara Alamri', 'email' => 'sara@example.com']);
        User::factory()->create(['name' => 'Omar Hassan', 'email' => 'omar@clinic.test']);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/users?search=Sara')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.email', 'sara@example.com');

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/users?search=clinic.test')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Omar Hassan');
    }

    public function test_admin_can_filter_users_by_role(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->count(2)->create();
        User::factory()->doctor()->create();

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/users?role=patient')
            ->assertOk()
            ->assertJsonCount(2, 'data');

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/users?role=doctor')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.role', 'doctor');
    }

    public function test_admin_can_sort_users(): void
    {
        $admin = User::factory()->admin()->create(['name' => 'Zed Admin']);
        User::factory()->create(['name' => 'Amal']);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/users?sort=name')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Amal');

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/users?sort=oldest')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Zed Admin');
    }

    public function test_admin_can_view_a_user_with_appointment_summary(): void
    {
        $admin = User::factory()->admin()->create();
        $patient = User::factory()->create();
        $this->appointmentFor($patient);

        $this->actingAs($admin, 'sanctum')
            ->getJson("/api/v1/admin/users/{$patient->id}")
            ->assertOk()
            ->assertJsonPath('data.email', $patient->email)
            ->assertJsonPath('data.appointments_count', 1)
            ->assertJsonCount(1, 'data.recent_appointments');
    }

    public function test_password_and_tokens_are_never_exposed(): void
    {
        $admin = User::factory()->admin()->create();
        $patient = User::factory()->create();
        $patient->createToken('api');

        foreach (["/api/v1/admin/users/{$patient->id}", '/api/v1/admin/users'] as $url) {
            $body = $this->actingAs($admin, 'sanctum')->getJson($url)->assertOk()->getContent();

            $this->assertStringNotContainsString('password', $body);
            $this->assertStringNotContainsString('remember_token', $body);
            $this->assertStringNotContainsString('token', $body);
            $this->assertStringNotContainsString($patient->password, $body);
        }
    }

    public function test_admin_can_create_a_user_who_can_then_log_in(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/admin/users', $this->validPayload(['role' => 'doctor']));

        $response->assertCreated()
            ->assertJsonPath('data.email', 'layla@example.com')
            ->assertJsonPath('data.role', 'doctor')
            ->assertJsonMissingPath('data.password');

        $user = User::where('email', 'layla@example.com')->firstOrFail();
        $this->assertNotSame('secret-pass-1', $user->password);
        $this->assertTrue(Hash::check('secret-pass-1', $user->password));

        $this->app['auth']->forgetGuards();
        $this->postJson('/api/v1/login', ['email' => 'layla@example.com', 'password' => 'secret-pass-1'])
            ->assertOk()
            ->assertJsonPath('data.user.role', 'doctor');
    }

    public function test_create_validates_fields_duplicate_email_password_and_role(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->create(['email' => 'taken@example.com']);

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/users', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'role', 'password']);

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/users', $this->validPayload(['email' => 'taken@example.com']))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/users', $this->validPayload(['password' => 'short', 'password_confirmation' => 'short']))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['password']);

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/users', $this->validPayload(['password_confirmation' => 'mismatch-123']))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['password']);

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/users', $this->validPayload(['role' => 'superadmin']))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['role']);

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/users', $this->validPayload(['phone' => '12345']))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['phone']);
    }

    public function test_admin_can_edit_profile_fields(): void
    {
        $admin = User::factory()->admin()->create();
        $patient = User::factory()->create();

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/v1/admin/users/{$patient->id}", [
                'name' => 'Renamed User',
                'email' => 'renamed@example.com',
                'phone' => '0559876543',
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Renamed User')
            ->assertJsonPath('data.email', 'renamed@example.com')
            ->assertJsonPath('data.phone', '0559876543');
    }

    public function test_edit_rejects_a_duplicate_email(): void
    {
        $admin = User::factory()->admin()->create();
        $patient = User::factory()->create();
        User::factory()->create(['email' => 'taken@example.com']);

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/v1/admin/users/{$patient->id}", ['email' => 'taken@example.com'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_role_change_takes_effect_on_the_existing_authorization(): void
    {
        $admin = User::factory()->admin()->create();
        $patient = User::factory()->create();

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/v1/admin/users/{$patient->id}", ['role' => 'admin'])
            ->assertOk()
            ->assertJsonPath('data.role', 'admin');

        $this->app['auth']->forgetGuards();
        $this->actingAs($patient->fresh(), 'sanctum')->getJson('/api/v1/admin/users')->assertOk();

        $this->app['auth']->forgetGuards();
        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/v1/admin/users/{$patient->id}", ['role' => 'patient'])
            ->assertOk();

        $this->app['auth']->forgetGuards();
        $this->actingAs($patient->fresh(), 'sanctum')->getJson('/api/v1/admin/users')->assertStatus(403);
    }

    public function test_admin_can_reset_a_users_password_with_confirmation(): void
    {
        $admin = User::factory()->admin()->create();
        $patient = User::factory()->create();

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/v1/admin/users/{$patient->id}", ['password' => 'new-pass-123'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['password']);

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/v1/admin/users/{$patient->id}", [
                'password' => 'new-pass-123',
                'password_confirmation' => 'new-pass-123',
            ])
            ->assertOk();

        $this->assertTrue(Hash::check('new-pass-123', $patient->fresh()->password));
    }

    public function test_admin_cannot_remove_their_own_admin_role(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/v1/admin/users/{$admin->id}", ['role' => 'patient'])
            ->assertStatus(422)
            ->assertJsonPath('success', false);

        $this->assertSame('admin', $admin->fresh()->role);

        // Editing their own non-role fields is still fine.
        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/v1/admin/users/{$admin->id}", ['name' => 'Still Admin', 'role' => 'admin'])
            ->assertOk()
            ->assertJsonPath('data.role', 'admin');
    }

    public function test_admin_cannot_delete_their_own_account(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/v1/admin/users/{$admin->id}")
            ->assertStatus(422);

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_admin_cannot_delete_a_user_with_appointment_history(): void
    {
        $admin = User::factory()->admin()->create();
        $patient = User::factory()->create();
        $appointment = $this->appointmentFor($patient);

        $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/v1/admin/users/{$patient->id}")
            ->assertStatus(422);

        $this->assertDatabaseHas('users', ['id' => $patient->id]);
        $this->assertSame($patient->id, $appointment->fresh()->user_id);
    }

    public function test_admin_can_delete_a_user_without_history_and_their_tokens_are_revoked(): void
    {
        $admin = User::factory()->admin()->create();
        $patient = User::factory()->create();
        $patient->createToken('api');

        $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/v1/admin/users/{$patient->id}")
            ->assertOk();

        $this->assertDatabaseMissing('users', ['id' => $patient->id]);
        $this->assertDatabaseMissing('personal_access_tokens', ['tokenable_id' => $patient->id, 'tokenable_type' => User::class]);
    }

    public function test_non_admins_cannot_manage_users(): void
    {
        $patient = User::factory()->create();
        $doctor = User::factory()->doctor()->create();
        $target = User::factory()->create();

        foreach ([$patient, $doctor] as $user) {
            $this->actingAs($user, 'sanctum')->getJson('/api/v1/admin/users')->assertStatus(403);
            $this->actingAs($user, 'sanctum')->getJson("/api/v1/admin/users/{$target->id}")->assertStatus(403);
            $this->actingAs($user, 'sanctum')->postJson('/api/v1/admin/users', $this->validPayload(['role' => 'admin']))->assertStatus(403);
            $this->actingAs($user, 'sanctum')->patchJson("/api/v1/admin/users/{$user->id}", ['role' => 'admin'])->assertStatus(403);
            $this->actingAs($user, 'sanctum')->deleteJson("/api/v1/admin/users/{$target->id}")->assertStatus(403);
        }

        $this->assertSame('patient', $patient->fresh()->role);
        $this->assertDatabaseMissing('users', ['email' => 'layla@example.com']);
    }

    public function test_guests_cannot_access_admin_user_endpoints(): void
    {
        $target = User::factory()->create();

        $this->getJson('/api/v1/admin/users')->assertStatus(401);
        $this->getJson("/api/v1/admin/users/{$target->id}")->assertStatus(401);
        $this->postJson('/api/v1/admin/users', $this->validPayload())->assertStatus(401);
        $this->patchJson("/api/v1/admin/users/{$target->id}", ['role' => 'admin'])->assertStatus(401);
        $this->deleteJson("/api/v1/admin/users/{$target->id}")->assertStatus(401);
    }

    public function test_registration_still_cannot_self_assign_a_role(): void
    {
        $this->postJson('/api/v1/register', $this->validPayload(['role' => 'admin']))
            ->assertCreated()
            ->assertJsonPath('data.user.role', 'patient');
    }
}
