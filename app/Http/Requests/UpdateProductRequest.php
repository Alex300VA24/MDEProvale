<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use App\Models\Product;
use App\Models\State;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('update', $this->route('product') ?? Product::class);
    }

    public function rules(): array
    {
        $productId = $this->route('product')->id ?? $this->route('id');
        return [
            'title' => 'sometimes|required|string|max:100',
            'abbreviation' => 'sometimes|required|string|max:20',
            'code' => 'sometimes|required|string|max:50|unique:products,code,' . $productId,
            'state_id' => ['sometimes', 'required', Rule::exists('states', 'id')->where(fn ($q) => $q->whereIn('abbreviation', [State::CURRENT, State::EXPIRED]))],
            'uom_id' => 'sometimes|required|exists:uoms,id',
        ];
    }

    public function messages(): array
    {
        return [
            'code.unique' => 'El código del producto ya está registrado.',
        ];
    }
}
