<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProductSizePricesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'sizes' => ['required', 'array', 'min:1', 'max:10'],
            'sizes.*.id' => ['nullable', 'integer', 'distinct'],
            'sizes.*.name' => ['required', 'string', 'max:80', 'distinct'],
            'sizes.*.price' => ['required', 'numeric', 'min:0', 'max:100000'],
            'sizes.*.is_active' => ['nullable', 'boolean'],
            'default_size' => [
                'required',
                'integer',
                'min:0',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! array_key_exists((int) $value, (array) $this->input('sizes', []))) {
                        $fail('Choose a valid default size.');
                    }
                },
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        $sizes = collect($this->input('sizes', []))
            ->map(function (mixed $size): mixed {
                if (! is_array($size)) {
                    return $size;
                }

                $size['id'] = filled($size['id'] ?? null) ? (int) $size['id'] : null;
                $size['is_active'] = (bool) ($size['is_active'] ?? true);

                return $size;
            })
            ->all();

        $this->merge(['sizes' => $sizes]);
    }
}
