<?php

namespace Tests\Feature;

use App\Repositories\PecosaRepository;
use Database\Seeders\AssociationSeeder;
use Database\Seeders\DetailPecosaSeeder;
use Database\Seeders\DetailProductSeeder;
use Database\Seeders\PecosaSeeder;
use Database\Seeders\PlaceSeeder;
use Database\Seeders\PlaceSectorSeeder;
use Database\Seeders\ProductSeeder;
use Database\Seeders\ProductStockSeeder;
use Database\Seeders\ResolutionSeeder;
use Database\Seeders\ResponsibleSeeder;
use Database\Seeders\SectorSeeder;
use Database\Seeders\StateSeeder;
use Database\Seeders\TransactionSeeder;
use Database\Seeders\TypeTransactionSeeder;
use Database\Seeders\TypePremisesSeeder;
use Database\Seeders\UomSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProductMigrationSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_current_sql_server_cut_is_seeded_with_complete_relations(): void
    {
        $this->seed([
            StateSeeder::class,
            TypeTransactionSeeder::class,
            UomSeeder::class,
            SectorSeeder::class,
            PlaceSeeder::class,
            PlaceSectorSeeder::class,
            TypePremisesSeeder::class,
            ResolutionSeeder::class,
            AssociationSeeder::class,
            ResponsibleSeeder::class,
            ProductSeeder::class,
            DetailProductSeeder::class,
            PecosaSeeder::class,
            DetailPecosaSeeder::class,
            TransactionSeeder::class,
            ProductStockSeeder::class,
        ]);

        $this->assertDatabaseCount('products', 2);
        $this->assertDatabaseCount('detail_products', 12);
        $this->assertDatabaseCount('pecosas', 539);
        $this->assertDatabaseCount('detail_pecosas', 924);
        $this->assertDatabaseCount('product_stocks', 924);
        $this->assertDatabaseCount('transactions', 936);

        $ingresoId = DB::table('type_transactions')->where('title', 'Ingreso')->value('id');
        $salidaId = DB::table('type_transactions')->where('title', 'Salida')->value('id');
        $this->assertSame(12, DB::table('transactions')->where('type_transaction_id', $ingresoId)->count());
        $this->assertSame(924, DB::table('transactions')->where('type_transaction_id', $salidaId)->count());
        $this->assertSame(0, DB::table('product_stocks')->whereNull('transaction_id')->count());

        $expiredStateId = DB::table('states')->where('abbreviation', 'VEN')->value('id');
        $administrativeStateIds = DB::table('states')->whereIn('abbreviation', ['ACT', 'INA'])->pluck('id');
        $this->assertSame(539, DB::table('pecosas')->where('state_id', $expiredStateId)->count());
        $this->assertSame(0, DB::table('pecosas')->whereIn('state_id', $administrativeStateIds)->count());

        $this->assertSame('2026-03-01', (string) DB::table('detail_products')->min('start_date'));
        $this->assertSame('2026-02-26 00:00:00', (string) DB::table('pecosas')->min('delivery_date'));
        $this->assertSame(0, DB::table('detail_pecosas')->whereNull('detail_product_id')->count());
        $this->assertSame(0, DB::table('detail_pecosas')->whereNull('pecosa_id')->count());

        $months = DB::table('pecosas')
            ->selectRaw('MONTH(delivery_date) AS month, COUNT(*) AS total')
            ->groupByRaw('MONTH(delivery_date)')
            ->orderBy('month')
            ->pluck('total', 'month')
            ->map(fn ($total) => (int) $total)
            ->all();
        $this->assertSame([2 => 77, 3 => 77, 4 => 77, 5 => 154, 6 => 77, 7 => 77], $months);

        $this->assertSame(539, app(PecosaRepository::class)->searchWithFilters([], 10)->total());
        $this->assertSame(539, app(PecosaRepository::class)->searchWithFilters(['year' => 2026], 10)->total());

        $unbalancedLots = DB::table('detail_products')
            ->whereRaw(
                'detail_products.quantity <> '
                . '(SELECT COALESCE(SUM(product_stocks.quantity), 0) '
                . 'FROM product_stocks WHERE product_stocks.detail_product_id = detail_products.id)'
            )
            ->count();
        $this->assertSame(0, $unbalancedLots);

        $brokenMovementLinks = DB::table('product_stocks as stock')
            ->join('transactions as movement', 'movement.id', '=', 'stock.transaction_id')
            ->join('pecosas', 'pecosas.id', '=', 'stock.pecosa_id')
            ->whereColumn('stock.detail_product_id', '<>', 'movement.detail_product_id')
            ->orWhereColumn('stock.quantity', '<>', 'movement.quantity')
            ->orWhereColumn('pecosas.pecosa_number', '<>', 'movement.document_number')
            ->count();
        $this->assertSame(0, $brokenMovementLinks);
    }
}
