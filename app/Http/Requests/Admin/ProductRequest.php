<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    public function rules(): array
    {
        $product = $this->route('product');

        return [
            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')],
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['required', 'string', 'max:140', 'alpha_dash', Rule::unique('products', 'slug')->ignore($product?->id)],
            'description' => ['required', 'string', 'max:2000'],
            'size_prices' => ['required', 'array'],
            'size_prices.small' => ['required', 'numeric', 'min:0', 'max:100000'],
            'size_prices.medium' => ['required', 'numeric', 'min:0', 'max:100000'],
            'size_prices.large' => ['required', 'numeric', 'min:0', 'max:100000'],
            'image_file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'remove_image' => ['nullable', 'boolean'],
            'is_featured' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'addons' => ['nullable', 'array'],
            'addons.*' => ['integer', Rule::exists('addons', 'id')],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'slug' => Str::slug((string) ($this->input('slug') ?: $this->input('name'))),
            'remove_image' => $this->boolean('remove_image'),
            'is_featured' => $this->boolean('is_featured'),
            'is_active' => $this->has('is_active') ? $this->boolean('is_active') : true,
            'addons' => array_values(array_filter($this->input('addons', []), fn ($value) => $value !== null && $value !== '')),
        ]);
    }
}
