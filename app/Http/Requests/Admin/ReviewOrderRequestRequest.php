<?php

namespace App\Http\Requests\Admin;

use App\Models\OrderRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReviewOrderRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    public function rules(): array
    {
        return [
            'decision' => ['required', Rule::in([OrderRequest::STATUS_APPROVED, OrderRequest::STATUS_REJECTED])],
            'admin_note' => ['nullable', 'string', 'max:1000', 'required_if:decision,rejected'],
        ];
    }
}
