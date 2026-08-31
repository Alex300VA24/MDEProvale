<?php

namespace Tests\Feature\Auth;

use App\Models\Rol;
use App\Models\State;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PresidentPortalAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    private const DNI = '12345678';
    private const PASSWORD = 'clave-segura-123';

    protected function setUp(): void
    {
        parent::setUp();

        $activeState = State::create(['title' => 'Activo', 'abbreviation' => State::ACTIVE]);
        Rol::create([
            'title'       => 'Administrador',
            'description' => 'Acceso completo al sistema',
            'is_active'   => true,
        ]);
        $presidentRol = Rol::firstOrCreate(['title' => Rol::PRESIDENT], [
            'description' => 'Acceso exclusivo al portal de consultas del comité asignado',
            'is_active'   => true,
        ]);

        $this->stateId = $activeState->id;
        $this->presidentRolId = $presidentRol->id;
    }

    private function createPresidentUser(bool $mustChangePassword = true): User
    {
        return User::create([
            'names'                => 'María',
            'father_surname'       => 'Apellido',
            'mother_surname'       => 'Materno',
            'username'             => self::DNI,
            'email'                => self::DNI . '@presidentas.provale.local',
            'dni'                  => self::DNI,
            'cui'                  => '0',
            'state_id'             => $this->stateId,
            'rol_id'               => $this->presidentRolId,
            'password'             => Hash::make(self::DNI),
            'must_change_password' => $mustChangePassword,
        ]);
    }

    public function test_login_redirects_to_password_change_when_required(): void
    {
        $this->createPresidentUser(mustChangePassword: true);

        $response = $this->post('/portal-presidentas/login', [
            'username' => self::DNI,
            'password' => self::DNI,
        ]);

        $response->assertRedirect(route('president.password.change'));
        $this->assertAuthenticated();
    }

    public function test_login_redirects_to_portal_when_password_already_changed(): void
    {
        $this->createPresidentUser(mustChangePassword: false);

        $response = $this->post('/portal-presidentas/login', [
            'username' => self::DNI,
            'password' => self::DNI,
        ]);

        $response->assertRedirect(route('president-portal.index'));
    }

    public function test_portal_index_forces_password_change_when_required(): void
    {
        $user = $this->createPresidentUser(mustChangePassword: true);

        $this->actingAs($user)
            ->get(route('president-portal.index'))
            ->assertRedirect(route('president.password.change'));
    }

    public function test_password_change_screen_can_be_rendered(): void
    {
        $user = $this->createPresidentUser(mustChangePassword: true);

        $this->actingAs($user)
            ->get(route('president.password.change'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Portal/Password')
                ->where('passwordChangeRequired', true)
            );
    }

    public function test_president_cannot_enter_administrative_dashboard(): void
    {
        $user = $this->createPresidentUser(mustChangePassword: false);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('president-portal.index'));
    }

    public function test_standard_login_never_sends_president_to_dashboard_after_expiration(): void
    {
        $this->createPresidentUser(mustChangePassword: false);

        $this->post('/login?expired=1', [
            'username' => self::DNI,
            'password' => self::DNI,
        ])->assertRedirect(route('president-portal.index'));
    }

    public function test_authenticated_president_opening_standard_login_returns_to_portal(): void
    {
        $user = $this->createPresidentUser(mustChangePassword: false);

        $this->actingAs($user)
            ->get(route('login'))
            ->assertRedirect(route('president-portal.index'));
    }

    public function test_cached_dashboard_api_redirects_president_instead_of_returning_403(): void
    {
        $user = $this->createPresidentUser(mustChangePassword: false);

        $this->actingAs($user)
            ->getJson(route('api.inicio.panel'))
            ->assertStatus(409)
            ->assertJsonPath('wrong_portal', true)
            ->assertJsonPath('redirect', route('president-portal.index'));
    }

    public function test_password_change_updates_account_and_redirects_to_portal(): void
    {
        $user = $this->createPresidentUser(mustChangePassword: true);

        $this->actingAs($user)
            ->post(route('president.password.update'), [
                'current_password'      => self::DNI,
                'password'              => self::PASSWORD,
                'password_confirmation' => self::PASSWORD,
            ])
            ->assertRedirect(route('president-portal.index'));

        $user->refresh();

        $this->assertTrue(Hash::check(self::PASSWORD, $user->password));
        $this->assertFalse((bool) $user->must_change_password);
    }

    public function test_password_change_rejects_dni_as_new_password(): void
    {
        $user = $this->createPresidentUser(mustChangePassword: true);

        $response = $this->actingAs($user)
            ->from(route('president.password.change'))
            ->post(route('president.password.update'), [
                'current_password'      => self::DNI,
                'password'              => self::DNI,
                'password_confirmation' => self::DNI,
            ]);

        $response->assertSessionHasErrors('password');
        $user->refresh();

        $this->assertTrue(Hash::check(self::DNI, $user->password));
        $this->assertTrue((bool) $user->must_change_password);
    }
}
