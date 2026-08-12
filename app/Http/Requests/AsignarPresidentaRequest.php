<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use App\Models\Association;

class AsignarPresidentaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('update', $this->route('association') ?? Association::class);
    }

    public function rules(): array
    {
        return [
            'partner_id' => 'required|exists:partners,id',
        ];
    }

    public function messages(): array
    {
        return [
            'partner_id.required' => 'Debe seleccionar una socia.',
            'partner_id.exists' => 'La socia seleccionada no existe.',
        ];
    }
}
