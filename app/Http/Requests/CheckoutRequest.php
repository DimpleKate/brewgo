<?php

namespace App\Http\Requests;

use App\Services\OrderSettingsService;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'service_type' => ['required', 'in:dine_in,pickup'],
            'people_count' => ['required_if:service_type,dine_in', 'nullable', 'integer', 'min:1', 'max:20'],
            'pickup_name' => ['required_if:service_type,pickup', 'nullable', 'string', 'max:80'],
            'pickup_phone' => [
                'required_if:service_type,pickup',
                'nullable',
                'string',
                'max:20',
                'regex:/^[0-9+\-\s()]{7,20}$/',
            ],
            'pickup_at' => ['required_if:service_type,pickup', 'nullable', 'date'],
            'payment_method' => ['required', Rule::in(['cash', 'qr'])],
            'payment_reference' => ['nullable', 'string', 'max:80'],
            'payment_proof' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'notes' => ['nullable', 'string', 'max:500'],
            'promotion_code' => ['nullable', 'string', 'max:40'],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $settings = app(OrderSettingsService::class);

                if ($this->input('service_type') === 'pickup') {
                    $this->validatePickupTime($validator, $settings);

                    if ($settings->pickupAdvanceRequired() && $this->input('payment_method') !== 'qr') {
                        $validator->errors()->add(
                            'payment_method',
                            'Pickup orders require a GCash / QR advance payment. Cash is only available for dine-in.'
                        );
                    }

                    if ($settings->pickupAdvanceRequired()
                        && $this->input('payment_method') === 'qr'
                        && blank($this->input('payment_reference'))) {
                        $validator->errors()->add(
                            'payment_reference',
                            'Enter the GCash / QR reference number for your advance payment.'
                        );
                    }

                    if ($settings->pickupAdvanceRequired()
                        && $this->input('payment_method') === 'qr'
                        && ! $this->hasFile('payment_proof')) {
                        $validator->errors()->add(
                            'payment_proof',
                            'Upload a screenshot or photo of your payment confirmation.'
                        );
                    }
                }
            },
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('promotion_code')) {
            $this->merge(['promotion_code' => strtoupper(trim((string) $this->input('promotion_code')))]);
        }

        if (! $this->filled('payment_method')) {
            $this->merge([
                'payment_method' => $this->input('service_type') === 'pickup'
                    ? 'qr'
                    : 'cash',
            ]);
        }
    }

    private function validatePickupTime(Validator $validator, OrderSettingsService $settings): void
    {
        if (blank($this->input('pickup_at'))) {
            return;
        }

        try {
            $pickupAt = Carbon::parse((string) $this->input('pickup_at'));
        } catch (\Throwable) {
            $validator->errors()->add('pickup_at', 'Choose a valid pickup date and time.');

            return;
        }

        $minimum = now()->addMinutes($settings->minNoticeMinutes());

        if ($pickupAt->lt($minimum)) {
            $validator->errors()->add(
                'pickup_at',
                'Choose a pickup time at least '.$settings->minNoticeMinutes().' minutes from now.'
            );
        }

        if ($pickupAt->gt(now()->addDays(7))) {
            $validator->errors()->add('pickup_at', 'Pickup times can be booked up to 7 days ahead.');
        }

        $this->merge(['pickup_at' => $pickupAt->toDateTimeString()]);
    }
}
