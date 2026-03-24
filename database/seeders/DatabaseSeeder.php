<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
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
        $this->call(UserSeeder::class);
        $this->call(PeopleSeeder::class);
        $this->call(ResolutionSeeder::class);
        $this->call(AssociationSeeder::class);
        $this->call(ResolutionAssociationSeeder::class);
        $this->call(PartnerSeeder::class);
        $this->call(BeneficiarieSeeder::class);
        $this->call(ProductSeeder::class);
        $this->call(ResponsibleSeeder::class);
        $this->call(BeneficiarieHistorySeeder::class);
        $this->call(DirectiveSeeder::class);
        //$this->call(TransactionSeeder::class);
        //$this->call(PecosaSeeder::class);
    }
}
