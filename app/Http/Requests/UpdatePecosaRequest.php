<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use App\Models\Pecosa;
use App\Models\State;
use Illuminate\Validation\Rule;

class UpdatePecosaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('update', $this->route('pecosa') ?? Pecosa::class);
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
            // La presidenta se resuelve por comité y mes del padrón.
            'managing_partner_id' => 'nullable|exists:partners,id',
            // Estado temporal opcional para clientes antiguos; servidor lo
            // recalcula por fecha y nunca conserva ACT/INA.
            'state_id' => ['sometimes', 'required', Rule::exists('states', 'id')->where(fn ($q) => $q->whereIn('abbreviation', [State::CURRENT, State::EXPIRED]))],
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
