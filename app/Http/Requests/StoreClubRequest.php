<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use App\Models\Association;

class StoreClubRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('create', Association::class);
    }

    public function rules(): array
    {
        return [
            'code' => 'required|string|max:20|unique:associations,code',
            'name' => 'required|string|max:100',
            'company_name' => 'required|string|max:150',
            'address' => 'required|string|max:150',
            'phone' => 'nullable|string|max:20',
            'observation' => 'nullable|string',
            'resolution_id' => 'required|exists:resolutions,id',
            'place_sector_id' => 'required|exists:place_sectors,id',
            'type_premises_id' => 'required|exists:type_premises,id',
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

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $code = $this->input('code');
            if ($code !== null && $code !== '') {
                $trimmed = trim((string) $code);
                if (is_numeric($trimmed)) {
                    $num = (int) $trimmed;
                    $exists = Association::where(function ($q) use ($trimmed, $num) {
                        $q->where('code', $trimmed)
                          ->orWhereRaw('CAST(code AS UNSIGNED) = ?', [$num]);
                    })->exists();

                    if ($exists && !$validator->errors()->has('code')) {
                        $validator->errors()->add('code', 'El código del comité ya está registrado.');
                    }
                }
            }
        });
    }
}
