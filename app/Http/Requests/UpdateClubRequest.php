<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use App\Models\Association;

class UpdateClubRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('update', $this->route('association') ?? Association::class);
    }

    public function rules(): array
    {
        $associationId = $this->route('association')->id ?? null;

        return [
            'code' => 'sometimes|required|string|max:20|unique:associations,code,' . $associationId,
            'name' => 'sometimes|required|string|max:100',
            'company_name' => 'sometimes|required|string|max:150',
            'address' => 'sometimes|required|string|max:150',
            'phone' => 'nullable|string|max:20',
            'observation' => 'nullable|string',
            'resolution_id' => 'sometimes|required|exists:resolutions,id',
            'place_sector_id' => 'sometimes|required|exists:place_sectors,id',
            'type_premises_id' => 'sometimes|required|exists:type_premises,id',
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'El código del comité es obligatorio.',
            'code.unique' => 'El código del comité ya está registrado.',
            'company_name.required' => 'La razón social es obligatoria.',
        ];
    }
}
