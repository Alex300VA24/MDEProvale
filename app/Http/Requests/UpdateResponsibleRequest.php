<?php

namespace App\Http\Requests;

use App\Models\Racion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class UpdateResponsibleRequest extends FormRequest
{
    public function authorize(): bool
    {
        // No hay un modelo Responsible dedicado con Policy propia; el CRUD de
        // Mantenimiento comparte el mismo nivel de acceso que Racion (módulo
        // 'mantenimiento', sin restricción adicional de admin para editar).
        return Gate::allows('create', Racion::class);
    }

    public function rules(): array
    {
        return [
            'person_id' => 'required|exists:people,id',
        ];
    }
}
