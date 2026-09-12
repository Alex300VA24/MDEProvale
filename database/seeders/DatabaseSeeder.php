<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        // 1. Catalogos
        $this->call(StateSeeder::class);
        $this->call(RolSeeder::class);
        $this->call(ModuleSeeder::class);
        $this->call(ReasonDisqualificationSeeder::class);
        $this->call(PositionSeeder::class);
        $this->call(SectorSeeder::class);
        $this->call(PlaceSeeder::class);
        $this->call(PlaceSectorSeeder::class);
        $this->call(TypePremisesSeeder::class);
        $this->call(RelationshipSeeder::class);
        $this->call(TypeBenefitSeeder::class);
        $this->call(UomSeeder::class);
        $this->call(TypeTransactionSeeder::class);

        // 2. Usuarios
        $this->call(UserSeeder::class);

        // 3. Resoluciones y comites
        $this->call(ResolutionSeeder::class);
        $this->call(AssociationSeeder::class);
        $this->call(ResolutionAssociationSeeder::class);

        // El padrón mensual es la fuente de verdad para socios,
        // beneficiarios y directivas; las resoluciones se conservan.
        $this->call(RosterCleanupSeeder::class);

        // 4. Personas consolidadas de marzo a septiembre
        $this->call(PeopleSeeder::class);

        // 5. Socios y beneficiarios con historial mensual
        $this->call(TemporalPartnerSeeder::class);
        $this->call(TemporalBeneficiarieSeeder::class);
        $this->call(TemporalBeneficiarieHistorySeeder::class);
        $this->call(AssociationRosterPeriodSeeder::class);
        $this->call(DirectiveSeeder::class);
        $this->call(ResponsibleSeeder::class);

        // 6. Sincronizar estados (antes de PresidentUserSeeder)
        app(\App\Services\ResolutionStateService::class)->syncAll();
        app(\App\Services\AssociationStateService::class)->syncAll();

        // 7. Usuarios presidentas (depende de directivas + estados)
        $this->call(PresidentUserSeeder::class);

        // 8. Productos y movimientos (filtrado >= 2026-02)
        $this->call(ProductSeeder::class);
        $this->call(DetailProductSeeder::class);
        $this->call(RacionesSeeder::class);
        $this->call(PecosaSeeder::class);
        $this->call(PecosaResponsableSeeder::class);
        $this->call(TransactionSeeder::class);
        $this->call(ProductStockSeeder::class);
        $this->call(DetailPecosaSeeder::class);
    }
}
