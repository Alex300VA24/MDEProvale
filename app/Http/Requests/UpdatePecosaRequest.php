<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use App\Models\Pecosa;

class UpdatePecosaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('update', Pecosa::class);
    }

    public function rules(): array
    {
        $pecosaId = $this->route('pecosa')->id ?? $this->route('id');
        return [
            'pecosa_number' => 'sometimes|required|string|max:50|unique:pecosas,pecosa_number,' . $pecosaId,
            'observation' => 'nullable|string',
            'delivery_date' => 'sometimes|required|date',
            'chief_id' => 'nullable|exists:responsibles,id',
            'storekeeper_id' => 'nullable|exists:responsibles,id',
            'managing_partner_id' => 'sometimes|required|exists:partners,id',
            'state_id' => 'sometimes|required|exists:states,id',
            'association_id' => 'sometimes|required|exists:associations,id',
            'details' => 'sometimes|required|array|min:1',
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