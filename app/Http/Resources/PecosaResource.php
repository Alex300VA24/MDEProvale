<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PecosaResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'pecosa_number' => $this->pecosa_number,
            'delivery_date' => $this->delivery_date,
            'observation' => $this->observation,
            'association_id' => $this->association_id,
            'state_id' => $this->state_id,
            'managing_partner_id' => $this->managing_partner_name ? $this->managing_partner_id : null,
            'chief_id' => $this->chief_name ? $this->chief_id : null,
            'storekeeper_id' => $this->storekeeper_name ? $this->storekeeper_id : null,
            'association' => $this->whenLoaded('association', fn () => $this->association ? [
                'id' => $this->association->id,
                'name' => $this->association_name ?: $this->association->name,
                'code' => $this->association_code ?: $this->association->code,
            ] : null),
            'state' => $this->whenLoaded('state', fn () => $this->state ? [
                'id' => $this->state->id,
                'title' => $this->state->title,
                'abbreviation' => $this->state->abbreviation,
            ] : null),
            'managing_partner' => $this->responsibleSnapshot(
                $this->managing_partner_id,
                $this->managing_partner_name,
                $this->managing_partner_dni
            ),
            'chief' => $this->responsibleSnapshot($this->chief_id, $this->chief_name, $this->chief_dni),
            'storekeeper' => $this->responsibleSnapshot(
                $this->storekeeper_id,
                $this->storekeeper_name,
                $this->storekeeper_dni
            ),
            'president_name' => $this->president_name,
            'president_dni' => $this->president_dni,
            'chief_name' => $this->chief_name,
            'chief_dni' => $this->chief_dni,
            'storekeeper_name' => $this->storekeeper_name,
            'storekeeper_dni' => $this->storekeeper_dni,
            'managing_partner_name' => $this->managing_partner_name,
            'managing_partner_dni' => $this->managing_partner_dni,
            'association_name' => $this->association_name,
            'association_code' => $this->association_code,
            'association_address' => $this->association_address,
            'association_zone_code' => $this->association_zone_code,
            'association_zone_name' => $this->association_zone_name,
            'association_sector_name' => $this->association_sector_name,
            'beneficiaries_count' => $this->beneficiaries_count,
            'detail_pecosas' => DetailPecosaResource::collection($this->whenLoaded('detailPecosas')),
            'created_at' => $this->created_at,
        ];
    }

    private function responsibleSnapshot($id, ?string $name, ?string $dni): ?array
    {
        $name = trim((string) $name);

        if ($name === '') {
            return null;
        }

        return [
            'id' => $id,
            'person' => [
                'id' => null,
                'full_name' => $name,
                'names' => $name,
                'father_lastname' => null,
                'mother_lastname' => null,
                'dni' => $dni,
            ],
        ];
    }
}
