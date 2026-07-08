<?php

namespace App\DTOs;

class PecosaSnapshotDTO extends BaseDTO
{
    public $chief_name;
    public $chief_dni;
    public $storekeeper_name;
    public $storekeeper_dni;
    public $managing_partner_name;
    public $managing_partner_dni;
    public $president_name;
    public $president_dni;
    public $association_name;
    public $association_code;
    public $association_address;
    public $association_zone_code;
    public $association_zone_name;
    public $association_sector_name;
    public $beneficiaries_count;

    public function __construct(
        $chief_name = null,
        $chief_dni = null,
        $storekeeper_name = null,
        $storekeeper_dni = null,
        $managing_partner_name = null,
        $managing_partner_dni = null,
        $president_name = null,
        $president_dni = null,
        $association_name = null,
        $association_code = null,
        $association_address = null,
        $association_zone_code = null,
        $association_zone_name = null,
        $association_sector_name = null,
        $beneficiaries_count = 0
    ) {
        $this->chief_name = $chief_name;
        $this->chief_dni = $chief_dni;
        $this->storekeeper_name = $storekeeper_name;
        $this->storekeeper_dni = $storekeeper_dni;
        $this->managing_partner_name = $managing_partner_name;
        $this->managing_partner_dni = $managing_partner_dni;
        $this->president_name = $president_name;
        $this->president_dni = $president_dni;
        $this->association_name = $association_name;
        $this->association_code = $association_code;
        $this->association_address = $association_address;
        $this->association_zone_code = $association_zone_code;
        $this->association_zone_name = $association_zone_name;
        $this->association_sector_name = $association_sector_name;
        $this->beneficiaries_count = $beneficiaries_count;
    }
}