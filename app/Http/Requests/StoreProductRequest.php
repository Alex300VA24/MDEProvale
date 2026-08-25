<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use App\Models\Product;
use App\Models\State;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('create', Product::class);
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:100',
            'abbreviation' => 'required|string|max:20',
            'code' => 'required|string|max:50|unique:products,code',
            'state_id' => ['required', Rule::exists('states', 'id')->where(fn ($q) => $q->whereIn('abbreviation', [State::CURRENT, State::EXPIRED]))],
            'uom_id' => 'required|exists:uoms,id',
        ];
    }

    public function messages(): array
    {
        return [
            'code.unique' => 'El código del producto ya está registrado.',
            'title.required' => 'El título es obligatorio.',
        ];
    }
}
