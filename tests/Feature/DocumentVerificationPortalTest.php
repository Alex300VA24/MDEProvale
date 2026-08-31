<?php

namespace Tests\Feature;

use App\Http\Controllers\PecosaController;
use App\Models\Pecosa;
use App\Models\Rol;
use App\Models\State;
use App\Models\User;
use App\Models\VerifiedDocument;
use App\Services\VerifiedDocumentService;
use App\Services\PecosaService;
use App\Services\PDFService;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DocumentVerificationPortalTest extends TestCase
{
    use RefreshDatabase;

    private User $president;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $state = State::firstOrCreate(['abbreviation' => 'A'], [
            'title' => 'Activo',
        ]);

        $presidentRole = Rol::firstOrCreate(['title' => Rol::PRESIDENT], [
            'description' => 'Portal de presidentas',
            'is_active' => true,
        ]);

        $adminRole = Rol::firstOrCreate(['title' => 'Administrador'], [
            'description' => 'Administración',
            'is_active' => true,
        ]);

        $this->president = User::updateOrCreate(
            ['username' => 'qa_presidenta'],
            $this->userData('qa_presidenta', '79000001', $state->id, $presidentRole->id)
        );
        $this->admin = User::updateOrCreate(
            ['username' => 'qa_admin'],
            $this->userData('qa_admin', '79000002', $state->id, $adminRole->id)
        );
    }

    public function test_verification_page_and_original_pdf_are_public_with_valid_token(): void
    {
        Storage::fake('local');
        $token = str_repeat('a', 64);
        $path = 'documentos-verificados/' . $token . '/padron.pdf';
        Storage::disk('local')->put($path, '%PDF-1.4 test');

        $document = VerifiedDocument::create([
            'token' => $token,
            'type' => VerifiedDocument::TYPE_PECOSA_REGISTER,
            'identifier' => 'PEC-20260828-ABC123',
            'status' => VerifiedDocument::STATUS_VALID,
            'issued_at' => now(),
            'storage_path' => $path,
            'sha256' => hash('sha256', '%PDF-1.4 test'),
        ]);

        $this->get(route('documents.verify', $document->token))
            ->assertOk()
            ->assertSee('Documento auténtico y vigente')
            ->assertSee($document->identifier)
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive');

        $this->get(route('documents.pdf', $document->token))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_invalid_verification_token_returns_not_found(): void
    {
        $this->get('/verificar-documento/no-es-un-token')->assertNotFound();
        $this->get('/verificar-documento/' . str_repeat('b', 64))->assertNotFound();
    }

    public function test_issuing_document_embeds_qr_and_stores_immutable_pdf(): void
    {
        Storage::fake('local');
        $data = [
            'titulo' => 'REPORTE DE PECOSAS',
            'secciones' => [[
                'titulo' => 'PECOSAS',
                'columnas' => [['key' => 'numero', 'label' => 'Número']],
                'rows' => [['numero' => 'P-001']],
                'group_by' => null,
                'group_label' => null,
                'filtros_aplicados' => [],
            ]],
            'resumen' => [['label' => 'Pecosas', 'total' => 1]],
            'total_general' => 1,
            'fecha' => now()->format('d/m/Y'),
            'hora' => now()->format('H:i:s'),
        ];

        [$document, $contents] = app(VerifiedDocumentService::class)->issue(
            VerifiedDocument::TYPE_PECOSA_REGISTER,
            'PEC-QA-' . strtoupper(\Illuminate\Support\Str::random(8)),
            ['registros' => 1],
            'reportes.padron_generico',
            $data,
            'padron-qa.pdf',
            $this->admin->id
        );

        $this->assertStringStartsWith('%PDF-', $contents);
        $this->assertSame(hash('sha256', $contents), $document->sha256);
        Storage::disk('local')->assertExists($document->storage_path);
    }

    public function test_only_president_role_can_open_private_portal(): void
    {
        $this->get(route('president-portal.index'))->assertRedirect(route('president.login'));

        $this->actingAs($this->admin)
            ->get(route('president-portal.index'))
            ->assertRedirect(route('dashboard'));

        $this->actingAs($this->president)
            ->get(route('president-portal.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Portal/Index')
                ->where('association', null)
            );
    }

    public function test_stale_session_marker_does_not_block_public_authenticity_page(): void
    {
        $token = str_repeat('c', 64);
        $document = VerifiedDocument::create([
            'token' => $token,
            'type' => VerifiedDocument::TYPE_PECOSA_RECEIPT,
            'identifier' => 'PEC-PUBLICA-001',
            'status' => VerifiedDocument::STATUS_VALID,
            'issued_at' => now(),
        ]);

        $this->withSession(['user_was_authenticated' => true])
            ->get(route('documents.verify', $document->token))
            ->assertOk()
            ->assertSee('Documento auténtico y vigente');
    }

    public function test_view_pdf_action_redirects_to_authenticity_page(): void
    {
        $document = new VerifiedDocument([
            'token' => str_repeat('d', 64),
            'identifier' => 'PEC-REDIRECT-001',
        ]);

        $this->mock(PecosaService::class)
            ->shouldReceive('generateComprobante')
            ->once()
            ->andReturn([$document, '%PDF-1.4 test', 'pecosa.pdf']);
        $this->mock(PDFService::class);
        $this->mock(StockService::class);

        $this->actingAs($this->admin);
        $response = app(PecosaController::class)
            ->generarComprobante(new Pecosa(['pecosa_number' => 'P-001']));

        $this->assertSame(route('documents.verify', $document->token), $response->getTargetUrl());
    }

    public function test_president_portal_has_its_own_role_restricted_login(): void
    {
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);

        $this->get(route('president.login'))
            ->assertOk()
            ->assertSee('Consulta de comité')
            ->assertSee('Portal de Presidentas');

        $response = $this->post(route('president.login.store'), [
            'username' => $this->admin->username,
            'password' => 'password',
        ]);
        $response->assertRedirect()->assertSessionHasErrors('username');
        $this->assertGuest();

        $this->post(route('president.login.store'), [
            'username' => $this->president->username,
            'password' => 'password',
        ])->assertRedirect(route('president-portal.index'));
        $this->assertAuthenticatedAs($this->president);

        $this->post(route('president.logout'))
            ->assertRedirect(route('president.login'));
        $this->assertGuest();
    }

    private function userData(string $username, string $dni, int $stateId, int $roleId): array
    {
        return [
            'names' => ucfirst($username),
            'father_surname' => 'Prueba',
            'mother_surname' => 'Portal',
            'username' => $username,
            'email' => $username . '@example.com',
            'dni' => $dni,
            'cui' => '0',
            'state_id' => $stateId,
            'rol_id' => $roleId,
            'password' => bcrypt('password'),
        ];
    }
}
