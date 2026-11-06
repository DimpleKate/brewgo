<?php

namespace App\Http\Requests\Admin;

use App\Models\ProductOption;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductOptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    public function rules(): array
    {
        return [
            'group_name' => ['required', Rule::in(array_keys(ProductOption::GROUPS))],
            'name' => ['required', 'string', 'max:80'],
            'price_adjustment' => ['nullable', 'numeric', 'min:-10000', 'max:10000'],
            'price' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'is_default' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'price_adjustment' => $this->input('price_adjustment') === null || $this->input('price_adjustment') === '' ? 0 : $this->input('price_adjustment'),
            'price' => $this->filled('price') ? $this->input('price') : null,
            'is_default' => $this->boolean('is_default'),
            'is_active' => $this->boolean('is_active'),
            'sort_order' => (int) ($this->input('sort_order') ?? 0),
        ]);
    }
}
