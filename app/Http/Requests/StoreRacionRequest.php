<?php

namespace App\Http\Requests;

use App\Models\Racion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class StoreRacionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('create', Racion::class);
    }

    public function rules(): array
    {
        return [
            'year' => 'required|integer|min:2000|max:2100',
            'month_start' => 'required|integer|min:1|max:12',
            'month_end' => 'required|integer|min:1|max:12|gte:month_start',
            'racion_hojuelas_gramos' => 'required|numeric|min:0',
            'racion_leche_militros' => 'required|numeric|min:0',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->any()) {
                return;
            }

            $racion = new Racion([
                'year' => $this->year,
                'month_start' => $this->month_start,
                'month_end' => $this->month_end,
            ]);

            if ($racion->hasOverlap()) {
                $meses = [
                    1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
                    5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
                    9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
                ];

                $periodoSolicitado = $meses[$this->month_start] . ' - ' . $meses[$this->month_end];

                // Buscar el período que genera el conflicto
                $conflicto = Racion::where('year', $this->year)
                    ->where('month_start', '<=', $this->month_end)
                    ->where('month_end', '>=', $this->month_start)
                    ->first();

                $periodoExistente = $conflicto
                    ? $meses[$conflicto->month_start] . ' - ' . $meses[$conflicto->month_end]
                    : '';

                throw ValidationException::withMessages([
                    'month_start' => "El período \"{$periodoSolicitado}\" se superpone con la ración existente \"{$periodoExistente}\" del año {$this->year}. Edite o elimine la ración existente primero.",
                ]);
            }
        });
    }

    public function messages(): array
    {
        return [
            'month_start.required' => 'El mes de inicio es obligatorio.',
            'month_start.min' => 'El mes de inicio debe ser entre 1 y 12.',
            'month_start.max' => 'El mes de inicio debe ser entre 1 y 12.',
            'month_end.required' => 'El mes de fin es obligatorio.',
            'month_end.min' => 'El mes de fin debe ser entre 1 y 12.',
            'month_end.max' => 'El mes de fin debe ser entre 1 y 12.',
            'month_end.gte' => 'El mes de fin debe ser igual o mayor al mes de inicio.',
        ];
    }
}
