<?php

namespace Tests\Feature\Api\V1\Auth;

use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Models\Gym;
use App\Models\User;
use App\Notifications\PendingStudentRegistrationNotification;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_user_can_register_as_student(): void
    {
        Notification::fake();

        $admin = User::factory()->create(['status' => UserStatus::Active]);
        $admin->assignRole(RoleName::Admin);

        $gym = Gym::query()->create([
            'name' => 'Academia Teste',
            'slug' => 'academia-teste',
            'status' => 'active',
        ]);

        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Aluno Teste',
            'email' => 'aluno@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'phone' => '11999999999',
            'gym_id' => $gym->id,
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.email', 'aluno@test.com')
            ->assertJsonPath('data.user.roles.0', RoleName::Student->value)
            ->assertJsonPath('data.pending_approval', true)
            ->assertJsonPath('data.token', null)
            ->assertJsonStructure(['data' => ['user' => ['id', 'name', 'email']]]);

        $this->assertDatabaseHas('users', [
            'email' => 'aluno@test.com',
            'status' => UserStatus::Inactive->value,
        ]);

        $this->assertDatabaseHas('students', [
            'gym_id' => $gym->id,
            'status' => 'pending',
        ]);

        Notification::assertSentTo($admin, PendingStudentRegistrationNotification::class);
    }

    public function test_pending_student_cannot_login_until_approved(): void
    {
        $gym = Gym::query()->create([
            'name' => 'Academia Login',
            'slug' => 'academia-login',
            'status' => 'active',
        ]);

        $this->postJson('/api/v1/auth/register', [
            'name' => 'Aluno Pendente',
            'email' => 'pendente@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'gym_id' => $gym->id,
        ])->assertCreated();

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'pendente@test.com',
            'password' => 'password123',
        ]);

        $response->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('errors.email.0', 'Seu cadastro está aguardando aprovação da academia.');
    }

    public function test_registration_gyms_are_publicly_listed(): void
    {
        Gym::query()->create([
            'name' => 'Academia Pública',
            'slug' => 'academia-publica',
            'status' => 'active',
        ]);

        $this->getJson('/api/v1/auth/registration-gyms')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data');
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'login@test.com',
            'password' => Hash::make('password123'),
        ]);
        $user->assignRole(RoleName::Student);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'login@test.com',
            'password' => 'password123',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.email', 'login@test.com')
            ->assertJsonStructure(['data' => ['token', 'user']]);
    }

    public function test_user_cannot_login_with_invalid_credentials(): void
    {
        User::factory()->create(['email' => 'login@test.com']);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'login@test.com',
            'password' => 'wrong-password',
        ]);

        $response->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['errors' => ['email']]);
    }

    public function test_inactive_user_cannot_login(): void
    {
        $user = User::factory()->inactive()->create([
            'email' => 'inactive@test.com',
            'password' => Hash::make('password123'),
        ]);
        $user->assignRole(RoleName::Student);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'inactive@test.com',
            'password' => 'password123',
        ]);

        $response->assertUnprocessable()
            ->assertJsonPath('success', false);
    }

    public function test_authenticated_user_can_get_profile(): void
    {
        $user = User::factory()->create();
        $user->assignRole(RoleName::Trainer);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/auth/me');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.email', $user->email)
            ->assertJsonPath('data.roles.0', RoleName::Trainer->value);
    }

    public function test_authenticated_user_can_update_profile(): void
    {
        $user = User::factory()->create(['phone' => null]);
        $user->assignRole(RoleName::Student);

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/auth/profile', [
                'name' => 'Nome Atualizado',
                'phone' => '62999998888',
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Nome Atualizado')
            ->assertJsonPath('data.phone', '62999998888');
    }

    public function test_authenticated_user_can_update_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('password123'),
        ]);
        $user->assignRole(RoleName::Student);

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/auth/password', [
                'current_password' => 'password123',
                'password' => 'new-password123',
                'password_confirmation' => 'new-password123',
            ])
            ->assertOk();

        $user->refresh();
        $this->assertTrue(Hash::check('new-password123', $user->password));
    }

    public function test_authenticated_user_can_logout(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('auth-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/auth/logout');

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_unauthenticated_user_cannot_access_me(): void
    {
        $response = $this->getJson('/api/v1/auth/me');

        $response->assertUnauthorized()
            ->assertJsonPath('success', false);
    }

    public function test_user_can_request_password_reset_link(): void
    {
        User::factory()->create(['email' => 'reset@test.com']);

        $response = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'reset@test.com',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_user_can_reset_password_with_valid_token(): void
    {
        $user = User::factory()->create(['email' => 'reset@test.com']);
        $token = Password::createToken($user);

        $response = $this->postJson('/api/v1/auth/reset-password', [
            'email' => 'reset@test.com',
            'token' => $token,
            'password' => 'new-password123',
            'password_confirmation' => 'new-password123',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true);

        $user->refresh();
        $this->assertTrue(Hash::check('new-password123', $user->password));
    }

    public function test_login_is_rate_limited(): void
    {
        User::factory()->create(['email' => 'rate@test.com']);

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->postJson('/api/v1/auth/login', [
                'email' => 'rate@test.com',
                'password' => 'wrong-password',
            ]);
        }

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'rate@test.com',
            'password' => 'wrong-password',
        ]);

        $response->assertTooManyRequests();
    }

    public function test_student_cannot_login_when_gym_is_inactive(): void
    {
        $gym = Gym::query()->create([
            'name' => 'Academia Inativa',
            'slug' => 'academia-inativa',
            'status' => 'inactive',
        ]);

        $user = User::factory()->create([
            'email' => 'aluno-inativo-gym@test.com',
            'password' => Hash::make('password123'),
            'status' => UserStatus::Active,
        ]);
        $user->assignRole(RoleName::Student);

        \App\Models\Student::query()->create([
            'user_id' => $user->id,
            'gym_id' => $gym->id,
            'status' => 'active',
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'aluno-inativo-gym@test.com',
            'password' => 'password123',
        ])
            ->assertUnprocessable()
            ->assertJsonPath('errors.email.0', 'A academia está inativa ou indisponível. Entre em contato com o administrador.');
    }

    public function test_trainer_cannot_login_when_gym_is_soft_deleted(): void
    {
        $gym = Gym::query()->create([
            'name' => 'Academia Excluída',
            'slug' => 'academia-excluida',
            'status' => 'active',
        ]);
        $gym->delete();

        $user = User::factory()->create([
            'email' => 'trainer-gym-deleted@test.com',
            'password' => Hash::make('password123'),
            'status' => UserStatus::Active,
        ]);
        $user->assignRole(RoleName::Trainer);

        \App\Models\Trainer::query()->create([
            'user_id' => $user->id,
            'gym_id' => $gym->id,
            'status' => 'active',
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'trainer-gym-deleted@test.com',
            'password' => 'password123',
        ])
            ->assertUnprocessable()
            ->assertJsonPath('errors.email.0', 'A academia está inativa ou indisponível. Entre em contato com o administrador.');
    }

    public function test_cannot_register_to_inactive_gym(): void
    {
        $gym = Gym::query()->create([
            'name' => 'Academia Fechada',
            'slug' => 'academia-fechada',
            'status' => 'inactive',
        ]);

        $this->postJson('/api/v1/auth/register', [
            'name' => 'Aluno Teste',
            'email' => 'novo-aluno@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'gym_id' => $gym->id,
        ])
            ->assertUnprocessable()
            ->assertJsonPath('errors.gym_id.0', 'A academia selecionada não está disponível para cadastro.');
    }
}
