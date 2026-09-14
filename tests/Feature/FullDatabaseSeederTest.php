<?php

namespace Tests\Feature;

use App\Models\Pecosa;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FullDatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_is_rebuilt_completely_from_seeders(): void
    {
        $this->seed(DatabaseSeeder::class);

        foreach ([
            'states',
            'modules',
            'rols',
            'module_rol',
            'users',
            'reason_disqualifications',
            'positions',
            'sectors',
            'places',
            'place_sectors',
            'type_premises',
            'relationships',
            'type_benefits',
            'uoms',
            'type_transactions',
            'resolutions',
            'associations',
            'resolution_associations',
            'people',
            'partners',
            'partner_roster_periods',
            'beneficiaries',
            'beneficiary_histories',
            'association_roster_periods',
            'directives',
            'responsibles',
            'raciones',
            'products',
            'detail_products',
            'pecosas',
            'detail_pecosas',
            'product_stocks',
            'transactions',
        ] as $table) {
            $this->assertGreaterThan(0, DB::table($table)->count(), "La tabla {$table} quedo vacia.");
        }

        $this->assertSame(2, DB::table('products')->count());
        $this->assertSame(12, DB::table('detail_products')->count());
        $this->assertSame(539, DB::table('pecosas')->count());
        $expiredStateId = DB::table('states')->where('abbreviation', 'VEN')->value('id');
        $this->assertSame(539, DB::table('pecosas')->where('state_id', $expiredStateId)->count());
        $this->assertSame(924, DB::table('detail_pecosas')->count());
        $this->assertSame(924, DB::table('product_stocks')->count());
        $this->assertSame(936, DB::table('transactions')->count());
        $this->assertSame(0, DB::table('product_stocks')->whereNull('transaction_id')->count());

        $ingresoId = DB::table('type_transactions')->where('title', 'Ingreso')->value('id');
        $salidaId = DB::table('type_transactions')->where('title', 'Salida')->value('id');
        $this->assertSame(12, DB::table('transactions')->where('type_transaction_id', $ingresoId)->count());
        $this->assertSame(924, DB::table('transactions')->where('type_transaction_id', $salidaId)->count());
        $this->assertSame(0, DB::table('detail_products')->whereRaw(
            'detail_products.quantity <> '
            . '(SELECT COALESCE(SUM(product_stocks.quantity), 0) '
            . 'FROM product_stocks WHERE product_stocks.detail_product_id = detail_products.id)'
        )->count());

        $admin = User::whereHas('rol', fn ($query) => $query->where('title', 'Administrador'))->firstOrFail();
        $this->actingAs($admin)
            ->getJson('/api/dashboard/productos-pecosas/pecosas?per_page=10')
            ->assertOk()
            ->assertJsonPath('meta.total', 539)
            ->assertJsonPath('period.year', null)
            ->assertJsonPath('period.month', null);
        $this->actingAs($admin)
            ->getJson('/api/dashboard/movimientos/transactions?per_page=15')
            ->assertOk()
            ->assertJsonPath('meta.total', 936);

        $relations = [
            ['partners', 'people', 'person_id'],
            ['partners', 'associations', 'association_id'],
            ['beneficiaries', 'people', 'person_id'],
            ['beneficiaries', 'partners', 'partner_id'],
            ['beneficiary_histories', 'beneficiaries', 'beneficiary_id'],
            ['directives', 'partners', 'partner_id'],
            ['responsibles', 'people', 'person_id'],
            ['pecosas', 'associations', 'association_id'],
            ['detail_products', 'products', 'product_id'],
            ['detail_pecosas', 'pecosas', 'pecosa_id'],
            ['detail_pecosas', 'detail_products', 'detail_product_id'],
            ['product_stocks', 'pecosas', 'pecosa_id'],
            ['product_stocks', 'detail_products', 'detail_product_id'],
            ['product_stocks', 'transactions', 'transaction_id'],
            ['transactions', 'detail_products', 'detail_product_id'],
            ['transactions', 'type_transactions', 'type_transaction_id'],
        ];

        foreach ($relations as [$child, $parent, $foreignKey]) {
            $orphans = DB::table("{$child} as child")
                ->leftJoin("{$parent} as parent", "parent.id", '=', "child.{$foreignKey}")
                ->whereNotNull("child.{$foreignKey}")
                ->whereNull('parent.id')
                ->count();

            $this->assertSame(0, $orphans, "Hay referencias huerfanas: {$child}.{$foreignKey}");
        }

        $periods = DB::table('association_roster_periods')->get();
        foreach ($periods as $period) {
            $partnerCount = DB::table('partner_roster_periods as roster')
                ->join('partners', 'partners.id', '=', 'roster.partner_id')
                ->where('partners.association_id', $period->association_id)
                ->whereDate('roster.period', $period->period)
                ->count();

            $periodStart = Carbon::parse($period->period)->startOfMonth()->toDateString();
            $periodEnd = Carbon::parse($period->period)->endOfMonth()->toDateString();
            $beneficiaryCount = DB::table('beneficiary_histories as history')
                ->join('beneficiaries', 'beneficiaries.id', '=', 'history.beneficiary_id')
                ->join('partners', 'partners.id', '=', 'beneficiaries.partner_id')
                ->where('partners.association_id', $period->association_id)
                ->whereDate('history.date_begin', '<=', $periodEnd)
                ->where(function ($query) use ($periodStart) {
                    $query->whereNull('history.date_end')
                        ->orWhereDate('history.date_end', '>=', $periodStart);
                })
                ->count();

            $this->assertSame((int) $period->partner_count, $partnerCount, "Socias fuera de sincronia en {$period->period}.");
            $this->assertSame((int) $period->beneficiary_count, $beneficiaryCount, "Beneficiarios fuera de sincronia en {$period->period}.");
        }

        $presidentsByPeriod = $periods->keyBy(
            fn ($period) => $period->association_id . '|' . substr((string) $period->period, 0, 10)
        );
        $coveredPecosas = 0;
        $presidentMismatches = [];

        foreach (DB::table('pecosas')->get([
            'pecosa_number',
            'association_id',
            'delivery_date',
            'president_id',
            'president_name',
        ]) as $pecosa) {
            $period = Pecosa::effectiveDeliveryDate($pecosa->delivery_date)
                ->startOfMonth()
                ->toDateString();
            $roster = $presidentsByPeriod->get($pecosa->association_id . '|' . $period);

            if (! $roster) {
                continue;
            }

            $coveredPecosas++;
            if ($pecosa->president_name !== $roster->president_name
                || (int) $pecosa->president_id !== (int) $roster->president_partner_id) {
                $presidentMismatches[] = $pecosa->pecosa_number;
            }
        }

        $this->assertGreaterThan(0, $coveredPecosas, 'Ninguna PECOSA coincidió con el padrón mensual.');
        $this->assertSame([], $presidentMismatches, 'PECOSAs con presidenta distinta al padrón mensual.');
    }
}
