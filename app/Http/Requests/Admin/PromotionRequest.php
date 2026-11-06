<?php

namespace App\Http\Requests\Admin;

use App\Models\Promotion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PromotionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    public function rules(): array
    {
        $promotion = $this->route('promotion');

        return [
            'title' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
            'code' => ['nullable', 'string', 'max:40', 'alpha_num', Rule::unique('promotions', 'code')->ignore($promotion?->id)],
            'discount_type' => ['required', Rule::in(['percent', 'fixed'])],
            'discount_value' => ['required', 'numeric', 'min:0', 'max:100000'],
            'minimum_spend' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'usage_limit' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'per_customer_limit' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'applies_to' => ['required', Rule::in([Promotion::APPLIES_ALL, Promotion::APPLIES_CATEGORY])],
            'category_id' => ['nullable', 'integer', Rule::exists('categories', 'id')],
            'max_discount' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'is_active' => ['nullable', 'boolean'],
            'image' => ['nullable', 'string', 'max:500'],
            'image_file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'remove_image' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $appliesTo = $this->input('applies_to', Promotion::APPLIES_ALL);

        $this->merge([
            'code' => $this->filled('code') ? strtoupper(trim((string) $this->input('code'))) : null,
            'minimum_spend' => $this->filled('minimum_spend') ? $this->input('minimum_spend') : 0,
            'usage_limit' => $this->filled('usage_limit') ? $this->input('usage_limit') : null,
            'per_customer_limit' => $this->filled('per_customer_limit') ? $this->input('per_customer_limit') : null,
            'max_discount' => $this->filled('max_discount') ? $this->input('max_discount') : null,
            'applies_to' => in_array($appliesTo, [Promotion::APPLIES_ALL, Promotion::APPLIES_CATEGORY], true)
                ? $appliesTo
                : Promotion::APPLIES_ALL,
            'category_id' => $appliesTo === Promotion::APPLIES_CATEGORY ? $this->input('category_id') : null,
            'is_active' => $this->boolean('is_active'),
            'remove_image' => $this->boolean('remove_image'),
        ]);
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->input('applies_to') === Promotion::APPLIES_CATEGORY && blank($this->input('category_id'))) {
                $validator->errors()->add('category_id', 'Choose the category this promotion applies to.');
            }

            if ($this->input('discount_type') === 'percent' && (float) $this->input('discount_value') > 100) {
                $validator->errors()->add('discount_value', 'A percentage discount cannot be more than 100%.');
            }
        });
    }
}
