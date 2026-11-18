<?php

namespace Tests\Traits;

use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/**
 * Fixtures compartidos por las pruebas del flujo completo:
 * Resolución -> Comité -> Socios/Beneficiarios -> Cargos -> Padrones.
 *
 * Usa SeedsBaseData (estado ACT, rol 1 Administrador, usuario 1, módulo socios-beneficiarios).
 * Las fechas son relativas a "hoy" para que las pruebas no caduquen.
 */
trait FlujoComiteFixtures
{
    use SeedsBaseData;

    protected const COMITES = '/api/dashboard/club-madres';
    protected const SOCIOS = '/api/dashboard/socios-beneficiarios';

    protected int $vig;
    protected int $pen;
    protected int $ven;
    protected int $ina;
    protected int $placeSectorId;
    protected int $premisesId;
    /** @var array<string,int> abreviatura => id */
    protected array $benefits = [];
    protected int $relHijo;
    protected int $relNieto;
    protected int $relApoderado;

    protected function seedFlujo(): void
    {
        $this->seedBaseData();
        $now = now();

        $this->vig = DB::table('states')->insertGetId(['title' => 'Vigente', 'abbreviation' => 'VIG']);
        $this->pen = DB::table('states')->insertGetId(['title' => 'Pendiente', 'abbreviation' => 'PEN']);
        $this->ven = DB::table('states')->insertGetId(['title' => 'Vencido', 'abbreviation' => 'VEN']);
        $this->ina = DB::table('states')->insertGetId(['title' => 'Inactivo', 'abbreviation' => 'INA']);

        $clubModule = DB::table('modules')->insertGetId([
            'name' => 'Comités', 'slug' => 'club-madres', 'description' => 'Comités',
            'icon' => 'fa-people-group', 'route' => 'club-madres', 'order' => 3, 'is_active' => true,
        ]);
        // Resoluciones (Reconocimientos) es un submódulo con permiso propio, distinto de Comités.
        $resolucionesModule = DB::table('modules')->insertGetId([
            'name' => 'Resoluciones', 'slug' => 'reconocimientos', 'description' => 'Resoluciones',
            'icon' => 'fa-award', 'route' => 'club-reconocimientos', 'order' => 4, 'is_active' => true,
        ]);
        $sociosModule = DB::table('modules')->where('slug', 'socios-beneficiarios')->value('id');

        foreach ([$clubModule, $resolucionesModule] as $moduleId) {
            DB::table('module_rol')->insert([
                'module_id' => $moduleId, 'rol_id' => 1,
                'can_view' => true, 'can_create' => true, 'can_edit' => true, 'can_delete' => true,
            ]);
        }

        // Rol 2: solo lectura en Comités, Resoluciones y Socios. Rol 3: sin ningún permiso.
        DB::table('rols')->insert([
            ['id' => 2, 'title' => 'Lector', 'description' => 'Solo lectura'],
            ['id' => 3, 'title' => 'Sin acceso', 'description' => 'Sin módulos'],
        ]);
        foreach ([$clubModule, $resolucionesModule, $sociosModule] as $moduleId) {
            DB::table('module_rol')->insert([
                'module_id' => $moduleId, 'rol_id' => 2,
                'can_view' => true, 'can_create' => false, 'can_edit' => false, 'can_delete' => false,
            ]);
        }
        foreach ([2 => 'lector', 3 => 'sinacceso'] as $id => $username) {
            DB::table('users')->insert([
                'id' => $id + 1, 'names' => ucfirst($username), 'father_surname' => 'Test', 'mother_surname' => 'Test',
                'username' => $username, 'email' => "$username@example.com", 'dni' => '0000000'.($id + 1),
                'cui' => '0', 'state_id' => 1, 'rol_id' => $id, 'password' => bcrypt('password'),
            ]);
        }

        DB::table('places')->insert(['id' => 1, 'code' => 'Z001', 'title' => 'Zona Central', 'created_at' => $now, 'updated_at' => $now]);
        DB::table('sectors')->insert(['id' => 1, 'title' => 'Sector 1', 'created_at' => $now, 'updated_at' => $now]);
        $this->placeSectorId = DB::table('place_sectors')->insertGetId(['place_id' => 1, 'sector_id' => 1, 'created_at' => $now, 'updated_at' => $now]);
        $this->premisesId = DB::table('type_premises')->insertGetId(['title' => 'Local Comunal', 'created_at' => $now, 'updated_at' => $now]);

        foreach ([
            'NI0' => ['Niño 0-6', 0, 6, 1], 'NI7' => ['Niño 7-13', 7, 13, 2],
            'GES' => ['Madre gestante', 12, null, 1], 'LAC' => ['Madre lactante', 12, null, 1],
            'ADU' => ['Adulto mayor', 65, null, 2], 'DIS' => ['Discapacitado', 0, null, 2],
            'TBC' => ['Tuberculoso', 0, null, 2],
        ] as $abbr => [$title, $min, $max, $priority]) {
            $this->benefits[$abbr] = DB::table('type_benefits')->insertGetId([
                'title' => $title, 'abbreviation' => $abbr, 'min_age' => $min, 'max_age' => $max,
                'priority' => $priority, 'created_at' => $now, 'updated_at' => $now,
            ]);
        }

        DB::table('reason_disqualifications')->insert([
            ['id' => 1, 'title' => 'SIN BAJA', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 4, 'title' => 'BAJA POR EDAD/PERIODO', 'created_at' => $now, 'updated_at' => $now],
        ]);

        foreach (['Hijo', 'Nieto', 'Apoderado'] as $title) {
            if (! DB::table('relationships')->whereRaw('LOWER(title)=?', [strtolower($title)])->exists()) {
                DB::table('relationships')->insert(['title' => $title, 'created_at' => $now, 'updated_at' => $now]);
            }
        }
        $this->relHijo = DB::table('relationships')->where('title', 'Hijo')->value('id');
        $this->relNieto = DB::table('relationships')->where('title', 'Nieto')->value('id');
        $this->relApoderado = DB::table('relationships')->where('title', 'Apoderado')->value('id');
    }

    // ------------------------------------------------------------------ usuarios

    protected function admin(): User
    {
        return User::findOrFail(1);
    }

    protected function lector(): User
    {
        return User::findOrFail(3);
    }

    protected function sinAcceso(): User
    {
        return User::findOrFail(4);
    }

    // ------------------------------------------------------------------ portal municipal

    /** Simula el portal: solo estos números (NNNN-AAAA) "existen". */
    protected function portalPublica(array $documents): void
    {
        $rows = '';
        foreach ($documents as $i => $doc) {
            $rows .= '<tr class="od"><td>'.($i + 1).'</td><td>x</td><td>RESOLUCION DE ALCALDIA '.$doc.'</td><td>01/01/2026</td>'
                ."<td><a onclick=\"window.open('../files/{$doc}.pdf')\">pdf</a></td></tr>";
        }
        $html = '<table>'.$rows.'</table>';

        Http::fake(function ($request) use ($html) {
            if (str_ends_with($request->url(), '.pdf')) {
                return Http::response('%PDF-1.4 fake', 200);
            }

            return Http::response($html, 200);
        });
        Cache::flush();
    }

    protected function portalCaido(): void
    {
        Http::fake(fn () => throw new ConnectionException('cURL error 28: timeout'));
        Cache::flush();
    }

    protected function portalConError(int $status = 500): void
    {
        Http::fake(['*' => Http::response('error', $status)]);
        Cache::flush();
    }

    // ------------------------------------------------------------------ resoluciones

    protected function dias(int $offset): string
    {
        return now()->addDays($offset)->toDateString();
    }

    /** Resolución insertada directo en BD (no pasa por el portal). */
    protected function resolucion(string $estado = 'VIG', ?string $document = null, ?string $inicio = null, ?string $fin = null): int
    {
        static $seq = 0;
        $seq++;
        $stateId = ['VIG' => $this->vig, 'PEN' => $this->pen, 'VEN' => $this->ven][$estado];
        $inicio ??= $this->dias(-60);
        $fin ??= $estado === 'VEN' ? $this->dias(-1) : $this->dias(300);

        return DB::table('resolutions')->insertGetId([
            'document' => $document ?? sprintf('%04d-%d', 100 + $seq, now()->year),
            'date_document' => $inicio.' 10:00:00',
            'date_start' => $inicio,
            'date_end' => $fin,
            'state_id' => $stateId,
            'observation' => $estado === 'PEN' ? 'Pendiente de publicar' : null,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    // ------------------------------------------------------------------ comités

    protected function clubPayload(int $resolutionId, string $code = 'C001', array $over = []): array
    {
        return array_merge([
            'code' => $code,
            'name' => 'Comité '.$code,
            'company_name' => 'Comité '.$code.' SAC',
            'address' => 'Av. Test 123',
            'phone' => '999999999',
            'resolution_id' => $resolutionId,
            'place_sector_id' => $this->placeSectorId,
            'type_premises_id' => $this->premisesId,
        ], $over);
    }

    /** Comité creado por la API. Devuelve su id. */
    protected function comite(int $resolutionId, string $code = 'C001', array $over = []): int
    {
        return (int) $this->actingAs($this->admin())
            ->postJson(self::COMITES.'/clubs', $this->clubPayload($resolutionId, $code, $over))
            ->assertCreated()
            ->json('data.id');
    }

    // ------------------------------------------------------------------ personas

    protected function persona(array $over = []): int
    {
        static $dni = 40000000;
        $dni++;

        return DB::table('people')->insertGetId(array_merge([
            'names' => 'Persona'.$dni, 'father_lastname' => 'Paterno', 'mother_lastname' => 'Materno',
            'dni' => (string) $dni, 'gender' => 'F', 'birthdate' => now()->subYears(30)->toDateString(),
            'address' => 'Calle 1', 'place_sector_id' => $this->placeSectorId,
            'created_at' => now(), 'updated_at' => now(),
        ], $over));
    }

    protected function nino(int $edadAnios = 3, string $gender = 'M', array $over = []): int
    {
        return $this->persona(array_merge([
            'gender' => $gender,
            'birthdate' => now()->subYears($edadAnios)->subMonths(2)->toDateString(),
        ], $over));
    }

    // ------------------------------------------------------------------ socios

    /** Ficha mínima válida (sin condición => exige al menos un beneficiario). */
    protected function socioPayload(int $personId, int $associationId, array $beneficiaries, array $over = []): array
    {
        return array_merge([
            'person_id' => $personId,
            'association_id' => $associationId,
            'state_id' => $this->vig,
            'date_begin' => $this->dias(-30),
            'beneficiaries' => $beneficiaries,
        ], $over);
    }

    protected function benef(int $personId, ?string $tipo = 'NI0', array $over = []): array
    {
        return array_filter(array_merge([
            'person_id' => $personId,
            'relationship_id' => $this->relHijo,
            'type_benefit_id' => $tipo ? $this->benefits[$tipo] : null,
            'history_state_id' => $tipo ? $this->vig : null,
            'date_begin' => $tipo ? $this->dias(-30) : null,
        ], $over), fn ($v) => $v !== null);
    }

    /** Registra un socio por la API y devuelve su id. */
    protected function socio(int $associationId, ?int $personId = null, ?array $beneficiaries = null, array $over = []): int
    {
        $personId ??= $this->persona();
        $beneficiaries ??= [$this->benef($this->nino())];

        return (int) $this->actingAs($this->admin())
            ->postJson(self::SOCIOS.'/partners', $this->socioPayload($personId, $associationId, $beneficiaries, $over))
            ->assertCreated()
            ->json('data.id');
    }

    protected function pdf(string $name = 'doc.pdf', int $kb = 10): \Illuminate\Http\UploadedFile
    {
        return \Illuminate\Http\UploadedFile::fake()->create($name, $kb, 'application/pdf');
    }
}
