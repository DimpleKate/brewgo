<?php

namespace App\Http\Requests;

use App\Models\OrderRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrderRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in([OrderRequest::TYPE_CANCELLATION, OrderRequest::TYPE_REFUND])],
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
        ];
    }
}
