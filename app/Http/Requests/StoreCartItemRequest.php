<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCartItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'product_id' => [
                'required',
                'integer',
                Rule::exists('products', 'id')->where('is_active', true),
            ],
            'quantity' => ['required', 'integer', 'min:1', 'max:20'],
            'note' => ['nullable', 'string', 'max:255'],
            'options' => ['nullable', 'array'],
            'options.*' => ['integer', 'distinct'],
            'addons' => ['nullable', 'array'],
            'addons.*' => ['integer', 'distinct'],
            'quick_add' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'note' => $this->filled('note') ? trim((string) $this->input('note')) : null,
            'options' => array_values(array_filter($this->input('options', []), fn ($value) => $value !== null && $value !== '')),
            'addons' => array_values(array_filter($this->input('addons', []), fn ($value) => $value !== null && $value !== '')),
            'quick_add' => $this->boolean('quick_add'),
        ]);
    }
}
