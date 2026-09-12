<?php

namespace Tests\Unit;

use App\Models\Association;
use App\Models\Beneficiarie;
use App\Models\Partner;
use App\Models\People;
use App\Models\Relationship;
use App\Services\AssistantDataService;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

class AssistantDataServiceConversationTest extends TestCase
{
    public function test_it_preserves_words_that_are_part_of_a_committee_name(): void
    {
        $service = new TestableAssistantDataService([
            'jesus me guia' => new Association(['code' => '375', 'name' => 'JESÚS ME GUÍA']),
        ]);

        $answer = $service->resolveConversation([
            ['role' => 'user', 'content' => '¿Quién es la presidenta del comité JESÚS ME GUÍA?'],
        ]);

        $this->assertSame('Presidenta: JESÚS ME GUÍA', $answer);
        $this->assertContains('jesus me guia', $service->searchedTerms);
    }

    public function test_it_uses_a_committee_code_from_a_follow_up_message(): void
    {
        $service = new TestableAssistantDataService([
            '375' => new Association(['code' => '375', 'name' => 'JESÚS ME GUÍA']),
        ]);

        $answer = $service->resolveConversation([
            ['role' => 'user', 'content' => '¿Quién es la presidenta del comité Jesús Me Guía?'],
            ['role' => 'assistant', 'content' => '¿Cuál es el código del comité?'],
            ['role' => 'user', 'content' => 'El 375'],
        ]);

        $this->assertSame('Presidenta: JESÚS ME GUÍA', $answer);
        $this->assertSame('375', $service->searchedTerms[0]);
    }

    public function test_it_keeps_the_committee_for_a_new_question_in_the_same_conversation(): void
    {
        $service = new TestableAssistantDataService([
            '375' => new Association(['code' => '375', 'name' => 'JESÚS ME GUÍA']),
        ]);

        $answer = $service->resolveConversation([
            ['role' => 'user', 'content' => 'Hablemos del comité 375'],
            ['role' => 'assistant', 'content' => 'De acuerdo.'],
            ['role' => 'user', 'content' => '¿Y cuántos beneficiarios tiene?'],
        ]);

        $this->assertSame('Beneficiarios: JESÚS ME GUÍA', $answer);
    }

    public function test_it_asks_one_concrete_question_when_committee_is_missing(): void
    {
        $service = new TestableAssistantDataService();

        $answer = $service->resolveConversation([
            ['role' => 'user', 'content' => '¿Quién es la presidenta?'],
        ]);

        $this->assertStringContainsString('¿Cuál es su nombre completo o código?', $answer);
    }

    public function test_it_lists_beneficiaries_using_the_partner_full_name(): void
    {
        $owner = (new People())->forceFill([
            'id' => 10,
            'names' => 'PAMELA YENNIFER',
            'father_lastname' => 'ALARCON',
            'mother_lastname' => 'GALLARDO',
        ]);
        $child = (new People())->forceFill([
            'id' => 11,
            'names' => 'MATEO',
            'father_lastname' => 'RAMIREZ',
            'mother_lastname' => 'ALARCON',
        ]);
        $relationship = (new Relationship())->forceFill(['id' => 1, 'title' => 'HIJO']);
        $committee = (new Association())->forceFill(['id' => 20, 'name' => 'JESÚS ME GUÍA', 'code' => '375']);
        $beneficiary = (new Beneficiarie())->forceFill(['id' => 30, 'person_id' => 11, 'partner_id' => 40]);
        $beneficiary->setRelation('person', $child);
        $beneficiary->setRelation('relationship', $relationship);

        $partner = (new Partner())->forceFill(['id' => 40, 'person_id' => 10, 'association_id' => 20]);
        $partner->setRelation('people', $owner);
        $partner->setRelation('association', $committee);
        $partner->setRelation('beneficiaries', collect([$beneficiary]));

        $service = new TestableAssistantDataService([], collect([$partner]));
        $answer = $service->resolveConversation([
            ['role' => 'user', 'content' => 'Indícame los beneficiarios de PAMELA YENNIFER ALARCON GALLARDO'],
        ]);

        $this->assertSame('pamela yennifer alarcon gallardo', $service->searchedPartnerIdentity);
        $this->assertStringContainsString('Beneficiarios de PAMELA YENNIFER ALARCON GALLARDO:', $answer);
        $this->assertStringContainsString('1. MATEO RAMIREZ ALARCON — HIJO · JESÚS ME GUÍA', $answer);
        $this->assertStringContainsString('- Total: 1', $answer);
    }
}

class TestableAssistantDataService extends AssistantDataService
{
    /** @var array<string, Association> */
    private array $committees;

    /** @var list<string> */
    public array $searchedTerms = [];

    public ?string $searchedPartnerIdentity = null;

    /**
     * @param array<string, Association> $committees
     * @param Collection<int, Partner>|null $partners
     */
    public function __construct(array $committees = [], private ?Collection $partners = null)
    {
        $this->committees = $committees;
    }

    protected function matchingPartners(string $identity): Collection
    {
        $this->searchedPartnerIdentity = $identity;

        return $this->partners ?? collect();
    }

    protected function coincidenciasComite(string $termino): Collection
    {
        $this->searchedTerms[] = $termino;
        $committee = $this->committees[$termino] ?? null;

        return $committee ? collect([$committee]) : collect();
    }

    protected function presidentAnswer(Association $committee): string
    {
        return "Presidenta: {$committee->name}";
    }

    protected function beneficiaryAnswer(Association $committee): string
    {
        return "Beneficiarios: {$committee->name}";
    }
}
