<?php

namespace Tests\Unit;

use App\Models\Partner;
use App\Models\People;
use App\Models\Rol;
use App\Models\State;
use App\Models\User;
use App\Services\PresidentAccountService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PresidentAccountServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        State::create(['title' => 'Activo', 'abbreviation' => State::ACTIVE]);
        Rol::firstOrCreate([
            'title' => Rol::PRESIDENT,
        ], [
            'description' => 'Acceso exclusivo al portal de consultas del comité asignado',
            'is_active'   => true,
        ]);
    }

    private function makePresidenta(string $dni): Partner
    {
        $people = People::create([
            'names'            => 'María',
            'father_lastname'  => 'Apellido',
            'mother_lastname'  => 'Materno',
            'dni'              => $dni,
            'gender'           => 'F',
        ]);

        $partner = new Partner();
        $partner->setAttribute('id', $people->id);
        $partner->setRelation('people', $people);

        return $partner;
    }

    public function test_sync_creates_account_with_dni_as_username_and_password(): void
    {
        $partner = $this->makePresidenta('12345678');

        $user = app(PresidentAccountService::class)->sync($partner);

        $this->assertNotNull($user);
        $this->assertSame('12345678', $user->username);
        $this->assertSame('12345678', $user->dni);
        $this->assertTrue(Hash::check('12345678', $user->password));
        $this->assertTrue($user->must_change_password);
        $this->assertTrue($user->rol->is_active);
        $this->assertSame(Rol::PRESIDENT, $user->rol->title);
        $this->assertSame(State::ACTIVE, $user->state->abbreviation);
    }

    public function test_sync_does_not_reset_password_of_existing_account(): void
    {
        $partner = $this->makePresidenta('87654321');

        app(PresidentAccountService::class)->sync($partner);

        $user = User::where('dni', '87654321')->firstOrFail();
        $user->update([
            'password'             => Hash::make('nueva-clave-segura'),
            'must_change_password' => false,
        ]);

        app(PresidentAccountService::class)->sync($partner);
        $user->refresh();

        $this->assertTrue(Hash::check('nueva-clave-segura', $user->password));
        $this->assertFalse((bool) $user->must_change_password);
    }

    public function test_sync_updates_personal_data_of_existing_account(): void
    {
        $partner = $this->makePresidenta('11223344');

        app(PresidentAccountService::class)->sync($partner);

        $partner->people->update(['names' => 'Rosa', 'father_lastname' => 'Nuevo']);
        $user = app(PresidentAccountService::class)->sync($partner);

        $this->assertSame('Rosa', $user->names);
        $this->assertSame('Nuevo', $user->father_surname);
    }

    public function test_sync_returns_null_when_partner_has_no_dni(): void
    {
        $people = People::create([
            'names'           => 'Sin',
            'father_lastname' => 'Documento',
            'mother_lastname' => 'Identidad',
            'dni'             => '',
            'gender'          => 'F',
        ]);

        $partner = new Partner();
        $partner->setAttribute('id', $people->id);
        $partner->setRelation('people', $people);

        $this->assertNull(app(PresidentAccountService::class)->sync($partner));
    }
}