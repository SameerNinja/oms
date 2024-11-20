<?php

namespace App\Http\Requests\API;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'customer_id' => 'required|exists:customers,id',
            'products' => 'required|array',
            'products.*.id' => 'required|exists:products,id',
            'products.*.qty' => 'required|integer|min:1',
            'payment_type' => 'nullable|in:HandCash,Cheque,Due'
        ];
    }

    /**
     * Get the custom error messages for validation failures.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'customer_id.exists' => 'The selected customer does not exist.',
            'products.*.id.exists' => 'One or more selected products do not exist.',
            'products.*.qty.min' => 'The quantity of each product must be at least 1.',
            'payment_type.in' => 'The payment type must be one of the following: HandCash, Cheque, or Due.',
        ];
    }
}
