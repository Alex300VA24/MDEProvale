<?php

namespace Tests\Feature;

use App\Http\Requests\StorePartnerRequest;
use App\Repositories\PartnerRepository;
use App\Services\PartnerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;
use Tests\Traits\SeedsBaseData;

class PartnerRulesTest extends TestCase
{
    use RefreshDatabase;
    use SeedsBaseData;

    private function beneficiariesErrors(?string $condition, array $beneficiaries = []): bool
    {
        $request = StorePartnerRequest::create('/x', 'POST', array_filter([
            'socioeconomic_condition' => $condition,
            'beneficiaries' => $beneficiaries ?: null,
        ]));
        $request->setContainer(app());

        return Validator::make($request->all(), ['beneficiaries' => $request->rules()['beneficiaries']])
            ->errors()->has('beneficiaries');
    }

    public function test_beneficiary_is_required_for_lactante_and_no_condition_only(): void
    {
        $this->assertTrue($this->beneficiariesErrors('LACTANTE'));
        $this->assertTrue($this->beneficiariesErrors(null));
        $this->assertFalse($this->beneficiariesErrors('GESTANTE'));
        $this->assertFalse($this->beneficiariesErrors('OTROS'));
        $this->assertFalse($this->beneficiariesErrors('LACTANTE', [['person_id' => 1]]));
    }

    public function test_priority_follows_child_age(): void
    {
        $service = app(PartnerService::class);

        $this->assertSame(1, $service->priorityForBirthdate(now()->subYears(6)->subMonths(11)->toDateString()));
        $this->assertSame(2, $service->priorityForBirthdate(now()->subYears(7)->toDateString()));
        $this->assertNull($service->priorityForBirthdate(null));
    }

    public function test_partners_are_listed_alphabetically_by_name(): void
    {
        $this->seedBaseData();
        $now = now();
        Schema::disableForeignKeyConstraints();
        DB::table('associations')->insert([
            'id' => 1, 'code' => 'A1', 'name' => 'Comité', 'company_name' => 'Comité SAC', 'address' => 'x', 'state_id' => 1,
            'resolution_id' => 1, 'place_sector_id' => 1, 'type_premises_id' => 1,
            'created_at' => $now, 'updated_at' => $now,
        ]);

        foreach (['Zoila', 'Ana', 'Marta'] as $i => $name) {
            $personId = DB::table('people')->insertGetId([
                'names' => $name, 'father_lastname' => 'Perez', 'mother_lastname' => 'Lopez',
                'dni' => '1000000'.$i, 'created_at' => $now, 'updated_at' => $now,
            ]);
            DB::table('partners')->insert([
                'person_id' => $personId, 'association_id' => 1, 'state_id' => 1,
                'date_begin' => '2025-01-01', 'created_at' => $now, 'updated_at' => $now,
            ]);
        }

        Schema::enableForeignKeyConstraints();

        $names = app(PartnerRepository::class)->paginateForApi([], 10)
            ->map(fn ($partner) => $partner->people->names)->all();

        $this->assertSame(['Ana', 'Marta', 'Zoila'], $names);
    }
}
