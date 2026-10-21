<?php

namespace App\Providers;

use App\Repositories\AssociationRepository;
use App\Repositories\BeneficiaryRepository;
use App\Repositories\CatalogRepository;
use App\Repositories\Contracts\AssociationRepositoryInterface;
use App\Repositories\Contracts\BeneficiaryRepositoryInterface;
use App\Repositories\Contracts\CatalogRepositoryInterface;
use App\Repositories\Contracts\PartnerRepositoryInterface;
use App\Repositories\Contracts\PecosaRepositoryInterface;
use App\Repositories\Contracts\PersonRepositoryInterface;
use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Repositories\Contracts\StockRepositoryInterface;
use App\Repositories\Contracts\TransactionRepositoryInterface;
use App\Repositories\PartnerRepository;
use App\Repositories\PecosaRepository;
use App\Repositories\PersonRepository;
use App\Repositories\ProductRepository;
use App\Repositories\StockRepository;
use App\Repositories\TransactionRepository;
use Illuminate\Support\ServiceProvider;

/**
 * Enlaza cada contrato de la capa de acceso a datos con su implementación.
 * La capa de negocio solo debe depender de las interfaces.
 */
class RepositoryServiceProvider extends ServiceProvider
{
    public array $bindings = [
        AssociationRepositoryInterface::class => AssociationRepository::class,
        BeneficiaryRepositoryInterface::class => BeneficiaryRepository::class,
        CatalogRepositoryInterface::class => CatalogRepository::class,
        PartnerRepositoryInterface::class => PartnerRepository::class,
        PecosaRepositoryInterface::class => PecosaRepository::class,
        PersonRepositoryInterface::class => PersonRepository::class,
        ProductRepositoryInterface::class => ProductRepository::class,
        StockRepositoryInterface::class => StockRepository::class,
        TransactionRepositoryInterface::class => TransactionRepository::class,
    ];
}
