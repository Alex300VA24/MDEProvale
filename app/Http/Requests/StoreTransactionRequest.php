<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use App\Models\Transaction;
use App\Models\TypeTransaction;

class StoreTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('create', Transaction::class);
    }

    public function rules(): array
    {
        return [
            'type_transaction_id' => 'required|exists:type_transactions,id',
            'detail_product_id' => 'nullable|exists:detail_products,id',
            'product_id' => 'nullable|exists:products,id',
            'quantity' => 'required|numeric|min:0.01',
            'unit_price' => 'required|numeric|min:0',
            'document_number' => 'nullable|string|max:50',
            'transaction_date' => 'required|date',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $typeTransaction = TypeTransaction::find($this->input('type_transaction_id'));
            
            if ($typeTransaction && $typeTransaction->isIngreso()) {
                if (!$this->input('product_id')) {
                    $validator->errors()->add('product_id', 'El producto es obligatorio para ingresos.');
                }
                if ($this->input('detail_product_id')) {
                    $validator->errors()->add('detail_product_id', 'No se debe enviar detail_product_id para ingresos.');
                }
            } elseif ($typeTransaction && $typeTransaction->isSalida()) {
                if (!$this->input('detail_product_id')) {
                    $validator->errors()->add('detail_product_id', 'El detalle de producto es obligatorio para salidas.');
                }
                if ($this->input('product_id')) {
                    $validator->errors()->add('product_id', 'No se debe enviar product_id para salidas.');
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'type_transaction_id.required' => 'El tipo de transacción es obligatorio.',
            'quantity.required' => 'La cantidad es obligatoria.',
            'quantity.min' => 'La cantidad debe ser mayor a 0.',
        ];
    }
}