<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use App\Models\Pecosa;

class StorePecosaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('create', Pecosa::class);
    }

    public function rules(): array
    {
        return [
            'pecosa_number' => 'required|string|max:50|unique:pecosas,pecosa_number',
            'observation' => 'nullable|string',
            'delivery_date' => 'required|date',
            'chief_id' => 'nullable|exists:responsibles,id',
            'storekeeper_id' => 'nullable|exists:responsibles,id',
            'managing_partner_id' => 'required|exists:partners,id',
            'state_id' => 'required|exists:states,id',
            'association_id' => 'required|exists:associations,id',
            'details' => 'required|array|min:1',
            'details.*.detail_product_id' => 'required|exists:detail_products,id',
            'details.*.quantity' => 'required|numeric|min:0.01',
        ];
    }

    public function messages(): array
    {
        return [
            'pecosa_number.unique' => 'El número de PECOSA ya está registrado.',
            'details.required' => 'Debe agregar al menos un producto.',
            'details.*.quantity.min' => 'La cantidad debe ser mayor a 0.',
        ];
    }
}