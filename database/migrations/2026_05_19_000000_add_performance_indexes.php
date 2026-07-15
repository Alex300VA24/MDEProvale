<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Performance indexes for foreign keys and frequently-queried columns.
 * Without these, SQL Server performs full table scans on every JOIN and WHERE clause.
 *
 * Uses a safe "if not exists" helper to avoid errors on databases where
 * some indexes may already exist (e.g., created manually or by a prior run).
 */
return new class extends Migration
{
    /**
     * Safely add an index only if it doesn't exist yet.
     */
    private function addIndexIfNotExists(string $table, $columns, ?string $indexName = null): void
    {
        $columns = (array) $columns;
        $indexName = $indexName ?: $table . '_' . implode('_', $columns) . '_index';

        $exists = DB::select(
            "SELECT 1 FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ?",
            [$table, $indexName]
        );

        if (empty($exists)) {
            Schema::table($table, function (Blueprint $table) use ($columns, $indexName) {
                $table->index($columns, $indexName);
            });
        }
    }

    public function up(): void
    {
        // ── partners ─────────────────────────────────────────────
        $this->addIndexIfNotExists('partners', 'person_id');
        $this->addIndexIfNotExists('partners', 'association_id');
        $this->addIndexIfNotExists('partners', 'state_id');

        // ── beneficiaries ────────────────────────────────────────
        $this->addIndexIfNotExists('beneficiaries', 'partner_id');
        $this->addIndexIfNotExists('beneficiaries', 'person_id');
        $this->addIndexIfNotExists('beneficiaries', 'relationship_id');

        // ── beneficiary_histories ────────────────────────────────
        $this->addIndexIfNotExists('beneficiary_histories', 'beneficiary_id');
        $this->addIndexIfNotExists('beneficiary_histories', 'state_id');
        $this->addIndexIfNotExists('beneficiary_histories', 'type_benefit_id');

        // ── directives ───────────────────────────────────────────
        $this->addIndexIfNotExists('directives', 'partner_id');
        $this->addIndexIfNotExists('directives', 'resolution_id');
        $this->addIndexIfNotExists('directives', 'position_id');
        $this->addIndexIfNotExists('directives', 'state_id');

        // ── associations ─────────────────────────────────────────
        $this->addIndexIfNotExists('associations', 'state_id');
        $this->addIndexIfNotExists('associations', 'place_sector_id');
        $this->addIndexIfNotExists('associations', 'resolution_id');

        // ── pecosas ──────────────────────────────────────────────
        $this->addIndexIfNotExists('pecosas', 'association_id');
        $this->addIndexIfNotExists('pecosas', 'state_id');
        $this->addIndexIfNotExists('pecosas', 'managing_partner_id');
        $this->addIndexIfNotExists('pecosas', 'delivery_date');

        // ── detail_pecosas ───────────────────────────────────────
        $this->addIndexIfNotExists('detail_pecosas', 'pecosa_id');
        $this->addIndexIfNotExists('detail_pecosas', 'detail_product_id');

        // ── detail_products ──────────────────────────────────────
        $this->addIndexIfNotExists('detail_products', 'product_id');
        $this->addIndexIfNotExists('detail_products', ['start_date', 'end_date'], 'detail_products_date_range_index');

        // ── product_stocks ───────────────────────────────────────
        $this->addIndexIfNotExists('product_stocks', 'detail_product_id');
        $this->addIndexIfNotExists('product_stocks', 'pecosa_id');

        // ── products ─────────────────────────────────────────────
        $this->addIndexIfNotExists('products', 'state_id');
        $this->addIndexIfNotExists('products', 'uom_id');

        // ── transactions ─────────────────────────────────────────
        $this->addIndexIfNotExists('transactions', 'detail_product_id');
        $this->addIndexIfNotExists('transactions', 'type_transaction_id');
        $this->addIndexIfNotExists('transactions', 'transaction_date');

        // ── modules ──────────────────────────────────────────────
        $this->addIndexIfNotExists('modules', 'slug');

        // ── module_rol (composite for the permission lookup) ─────
        $this->addIndexIfNotExists('module_rol', ['module_id', 'rol_id'], 'module_rol_module_rol_index');

        // ── notifications ────────────────────────────────────────
        $this->addIndexIfNotExists('notifications', 'is_seen');
        $this->addIndexIfNotExists('notifications', 'requested_by');
        $this->addIndexIfNotExists('notifications', 'user_id');

        // ── people ───────────────────────────────────────────────
        $this->addIndexIfNotExists('people', 'dni');
        $this->addIndexIfNotExists('people', 'place_sector_id');

        // ── users ────────────────────────────────────────────────
        $this->addIndexIfNotExists('users', 'rol_id');
        $this->addIndexIfNotExists('users', 'state_id');

        // ── resolution_associations ──────────────────────────────
        $this->addIndexIfNotExists('resolution_associations', 'resolution_id');
        $this->addIndexIfNotExists('resolution_associations', 'association_id');
    }

    public function down(): void
    {
        // List of all indexes added — drop in reverse order
        $indexes = [
            ['resolution_associations', 'resolution_associations_association_id_index'],
            ['resolution_associations', 'resolution_associations_resolution_id_index'],
            ['users', 'users_state_id_index'],
            ['users', 'users_rol_id_index'],
            ['people', 'people_place_sector_id_index'],
            ['people', 'people_dni_index'],
            ['notifications', 'notifications_user_id_index'],
            ['notifications', 'notifications_requested_by_index'],
            ['notifications', 'notifications_is_seen_index'],
            ['module_rol', 'module_rol_module_rol_index'],
            ['modules', 'modules_slug_index'],
            ['transactions', 'transactions_transaction_date_index'],
            ['transactions', 'transactions_type_transaction_id_index'],
            ['transactions', 'transactions_detail_product_id_index'],
            ['products', 'products_uom_id_index'],
            ['products', 'products_state_id_index'],
            ['product_stocks', 'product_stocks_pecosa_id_index'],
            ['product_stocks', 'product_stocks_detail_product_id_index'],
            ['detail_products', 'detail_products_date_range_index'],
            ['detail_products', 'detail_products_product_id_index'],
            ['detail_pecosas', 'detail_pecosas_detail_product_id_index'],
            ['detail_pecosas', 'detail_pecosas_pecosa_id_index'],
            ['pecosas', 'pecosas_delivery_date_index'],
            ['pecosas', 'pecosas_managing_partner_id_index'],
            ['pecosas', 'pecosas_state_id_index'],
            ['pecosas', 'pecosas_association_id_index'],
            ['associations', 'associations_resolution_id_index'],
            ['associations', 'associations_place_sector_id_index'],
            ['associations', 'associations_state_id_index'],
            ['directives', 'directives_state_id_index'],
            ['directives', 'directives_position_id_index'],
            ['directives', 'directives_resolution_id_index'],
            ['directives', 'directives_partner_id_index'],
            ['beneficiary_histories', 'beneficiary_histories_type_benefit_id_index'],
            ['beneficiary_histories', 'beneficiary_histories_state_id_index'],
            ['beneficiary_histories', 'beneficiary_histories_beneficiary_id_index'],
            ['beneficiaries', 'beneficiaries_relationship_id_index'],
            ['beneficiaries', 'beneficiaries_person_id_index'],
            ['beneficiaries', 'beneficiaries_partner_id_index'],
            ['partners', 'partners_state_id_index'],
            ['partners', 'partners_association_id_index'],
            ['partners', 'partners_person_id_index'],
        ];

        foreach ($indexes as [$table, $indexName]) {
            $exists = DB::select(
                "SELECT 1 FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ?",
                [$table, $indexName]
            );
            if (!empty($exists)) {
                Schema::table($table, function (Blueprint $tbl) use ($indexName) {
                    $tbl->dropIndex($indexName);
                });
            }
        }
    }
};
