<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class UpdateRacionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('update', $this->route('racion'));
    }

    public function rules(): array
    {
        return [
            'racion_hojuelas_gramos' => 'required|numeric|min:0',
            'racion_leche_militros' => 'required|numeric|min:0',
        ];
    }
}
