<?php

namespace App\Http\Requests;

use App\Models\Racion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreRacionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('create', Racion::class);
    }

    public function rules(): array
    {
        return [
            'year' => 'required|integer|min:2000|max:2100|unique:raciones,year',
            'racion_hojuelas_gramos' => 'required|numeric|min:0',
            'racion_leche_militros' => 'required|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'year.unique' => 'Ya existe una ración registrada para ese año.',
        ];
    }
}
