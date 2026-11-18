<?php

namespace Tests\Feature\Api;

use App\Models\State;
use App\Models\User;
use Database\Seeders\ModuleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * El Asistente PROVALE solo guía, consulta y reporta sobre los módulos a los que
 * el rol del usuario tiene acceso. "Movimientos" ya no existe: solo Registro de Ingreso.
 */
class AssistantPermissionsTest extends TestCase
{
    use RefreshDatabase;

    private const ENDPOINT = '/api/asistente/chat';

    private int $seq = 0;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.ai.provider' => 'none']);

        DB::table('states')->insert(['id' => 1, 'title' => 'Activo', 'abbreviation' => State::ACTIVE]);
        DB::table('rols')->insert(['id' => 1, 'title' => 'Administrador', 'description' => 'Acceso completo']);
        $this->seed(ModuleSeeder::class);
    }

    /** @param  string[]  $slugs */
    private function userWith(array $slugs): User
    {
        $n = ++$this->seq;
        $rolId = DB::table('rols')->insertGetId(['title' => "Rol {$n}", 'description' => 'Test', 'is_active' => true]);

        foreach ($slugs as $slug) {
            DB::table('module_rol')->insert([
                'module_id' => DB::table('modules')->where('slug', $slug)->value('id'),
                'rol_id' => $rolId, 'can_view' => true, 'can_create' => true, 'can_edit' => true, 'can_delete' => true,
            ]);
        }

        $userId = DB::table('users')->insertGetId([
            'names' => "U{$n}", 'father_surname' => 'T', 'mother_surname' => 'T', 'username' => "asist{$n}",
            'email' => "asist{$n}@example.com", 'dni' => sprintf('2000%04d', $n), 'cui' => '0',
            'state_id' => 1, 'rol_id' => $rolId, 'password' => bcrypt('password'),
        ]);

        return User::findOrFail($userId);
    }

    private function ask(User $user, string $question)
    {
        return $this->actingAs($user)->postJson(self::ENDPOINT, [
            'mensajes' => [['role' => 'user', 'content' => $question]],
        ]);
    }

    private function answer(User $user, string $question): string
    {
        return (string) $this->ask($user, $question)->assertOk()->json('respuesta');
    }

    public function test_the_assistant_requires_the_ayuda_module(): void
    {
        $this->ask($this->userWith(['pecosas']), '¿Cómo registro una pecosa?')->assertForbidden();
        $this->ask($this->userWith(['ayuda']), '¿Cómo registro una pecosa?')->assertOk();
    }

    public function test_guidance_is_denied_without_the_module_and_given_with_it(): void
    {
        $this->assertStringContainsString(
            'Tu rol no tiene acceso a Pecosas y Repartición > Pecosas',
            $this->answer($this->userWith(['ayuda']), '¿Cómo registro una pecosa?')
        );

        $this->assertStringContainsString(
            'Abre Pecosas y Repartición > Pecosas',
            $this->answer($this->userWith(['ayuda', 'pecosas']), '¿Cómo registro una pecosa?')
        );
    }

    public function test_each_submodule_is_checked_on_its_own(): void
    {
        // Resoluciones no da acceso a Comités, ni Productos a Registro de Ingreso o Programación.
        $resoluciones = $this->userWith(['ayuda', 'reconocimientos']);
        $this->assertStringContainsString('Tu rol no tiene acceso a Comités y Resoluciones > Comités', $this->answer($resoluciones, '¿Cómo gestiono un comité?'));
        $this->assertStringContainsString('Comités y Resoluciones > Resoluciones', $this->answer($resoluciones, '¿Cómo registro una resolución?'));
        $this->assertStringNotContainsString('no tiene acceso', $this->answer($resoluciones, '¿Cómo registro una resolución?'));

        $productos = $this->userWith(['ayuda', 'productos']);
        $this->assertStringContainsString('Tu rol no tiene acceso a Productos y Programación de Entrega > Registro de Ingreso', $this->answer($productos, '¿Cómo registro un ingreso de productos?'));
        $this->assertStringContainsString('Tu rol no tiene acceso a Productos y Programación de Entrega > Programación de Entrega', $this->answer($productos, '¿Cómo veo la programación de entrega?'));
        $this->assertStringContainsString('Tu rol no tiene acceso a Pecosas y Repartición > Repartición', $this->answer($productos, '¿Cómo reviso la repartición?'));
    }

    public function test_movimientos_no_longer_exists_only_registro_de_ingreso(): void
    {
        $user = $this->userWith(['ayuda', 'registro-ingreso']);

        foreach (['¿Cómo registro un ingreso?', '¿Dónde veo los movimientos?', '¿Cómo uso el kardex?'] as $question) {
            $answer = $this->answer($user, $question);

            $this->assertStringContainsString('Productos y Programación de Entrega > Registro de Ingreso', $answer, $question);
            $this->assertStringNotContainsString('Abre Movimientos', $answer, $question);
            $this->assertStringNotContainsString('entradas y salidas', $answer, $question);
        }
    }

    public function test_repartition_guidance_points_to_pecosas_y_reparticion(): void
    {
        $answer = $this->answer($this->userWith(['ayuda', 'reparticion']), '¿Cómo reviso la repartición?');

        $this->assertStringContainsString('Abre Pecosas y Repartición > Repartición', $answer);
    }

    public function test_data_answers_respect_the_role(): void
    {
        $this->assertStringContainsString(
            'Tu rol no tiene acceso a Comités y Resoluciones > Comités',
            $this->answer($this->userWith(['ayuda']), '¿Cuántos comités activos hay este mes?')
        );
        $this->assertStringContainsString(
            'Comités activos',
            $this->answer($this->userWith(['ayuda', 'club-madres']), '¿Cuántos comités activos hay este mes?')
        );

        $this->assertStringContainsString(
            'Tu rol no tiene acceso a Socios y Beneficiarios',
            $this->answer($this->userWith(['ayuda']), '¿Cuántos beneficiarios hay en total?')
        );
        $this->assertStringContainsString(
            'Tu rol no tiene acceso a Pecosas y Repartición > Pecosas',
            $this->answer($this->userWith(['ayuda', 'socios-beneficiarios']), '¿Cuántas pecosas se generaron este mes?')
        );
    }

    public function test_reports_need_the_reportes_module_and_the_entity_module(): void
    {
        $question = 'Crea un reporte de pecosas de este mes';

        $sinReportes = $this->answer($this->userWith(['ayuda', 'pecosas']), $question);
        $this->assertStringContainsString('Tu rol no tiene acceso a Reportes', $sinReportes);

        $sinPecosas = $this->answer($this->userWith(['ayuda', 'reportes', 'productos']), $question);
        $this->assertStringContainsString('Tu rol no tiene acceso a Pecosas y Repartición > Pecosas', $sinPecosas);

        $ok = $this->ask($this->userWith(['ayuda', 'reportes', 'pecosas']), $question)->assertOk();
        $ok->assertJsonPath('accion.tipo', 'reporte');
    }

    public function test_the_ingreso_report_replaces_the_movimientos_report(): void
    {
        $user = $this->userWith(['ayuda', 'reportes', 'registro-ingreso']);

        foreach (['Crea un reporte de ingresos del año 2026', 'Crea un reporte de movimientos del año 2026'] as $question) {
            $response = $this->ask($user, $question)->assertOk();

            $this->assertStringContainsString('registro de ingreso', $response->json('respuesta'));
            $this->assertStringNotContainsString('movimientos / kardex', $response->json('respuesta'));
            // Solo ingresos (tipo de transacción 1).
            $this->assertStringContainsString('type_transaction_id', urldecode($response->json('accion.url')));
        }

        $this->assertStringContainsString(
            'Tu rol no tiene acceso a Productos y Programación de Entrega > Registro de Ingreso',
            $this->answer($this->userWith(['ayuda', 'reportes', 'pecosas']), 'Crea un reporte de ingresos del año 2026')
        );
    }

    public function test_report_clarification_only_offers_permitted_entities(): void
    {
        $response = $this->ask($this->userWith(['ayuda', 'reportes', 'pecosas']), 'Crea un reporte')->assertOk();

        $labels = collect($response->json('sugerencias'))->pluck('label')->implode(' | ');
        $this->assertStringContainsString('pecosas', $labels);
        $this->assertStringNotContainsString('productos', $labels);
        $this->assertStringNotContainsString('beneficiarios', $labels);
    }

    public function test_the_model_prompt_lists_only_the_modules_the_user_can_access(): void
    {
        config([
            'services.ai.provider' => 'groq',
            'services.groq.key' => 'test-key',
            'services.groq.model' => 'openai/gpt-oss-120b',
            'services.groq.url' => 'https://api.groq.test/openai/v1/chat/completions',
        ]);
        Http::fake(['api.groq.test/*' => Http::response(['choices' => [['message' => ['content' => 'Respuesta.']]]])]);

        $this->ask($this->userWith(['ayuda', 'pecosas', 'registro-ingreso']), 'Hola, necesito ayuda general')->assertOk();

        Http::assertSent(function ($request) {
            $system = $request['messages'][0]['content'];

            return $request['messages'][0]['role'] === 'system'
                && str_contains($system, "Módulos con acceso:\n- Productos y Programación de Entrega > Registro de Ingreso")
                && str_contains($system, '- Pecosas y Repartición > Pecosas')
                && ! str_contains($system, '- Pecosas y Repartición > Repartición')
                && ! str_contains($system, '- Sistema')
                && ! str_contains($system, 'Supervisión de Comités:')
                && str_contains($system, 'su rol no tiene acceso');
        });
    }

    public function test_a_user_without_modules_gets_no_flows_in_the_prompt(): void
    {
        config([
            'services.ai.provider' => 'groq',
            'services.groq.key' => 'test-key',
            'services.groq.model' => 'openai/gpt-oss-120b',
            'services.groq.url' => 'https://api.groq.test/openai/v1/chat/completions',
        ]);
        Http::fake(['api.groq.test/*' => Http::response(['choices' => [['message' => ['content' => 'Respuesta.']]]])]);

        $this->ask($this->userWith(['ayuda']), 'Hola, necesito ayuda general')->assertOk();

        Http::assertSent(fn ($request) => ! str_contains($request['messages'][0]['content'], 'Flujos conocidos')
            || str_contains($request['messages'][0]['content'], '- Asistente PROVALE'));
    }
}
