<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCartItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'quantity' => ['required', 'integer', 'min:1', 'max:20'],
            'options' => ['nullable', 'array'],
            'options.*' => ['integer', 'distinct'],
            'addons' => ['nullable', 'array'],
            'addons.*' => ['integer', 'distinct'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'options' => array_values(array_filter($this->input('options', []), fn ($value) => $value !== null && $value !== '')),
            'addons' => array_values(array_filter($this->input('addons', []), fn ($value) => $value !== null && $value !== '')),
        ]);
    }
}
