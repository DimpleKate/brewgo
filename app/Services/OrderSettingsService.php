<?php

namespace App\Services;

use App\Models\SiteSetting;
use Illuminate\Database\QueryException;

class OrderSettingsService
{
    /**
     * @return array<string, string>
     */
    public function all(): array
    {
        $settings = [];

        foreach ($this->defaults() as $key => $default) {
            $settings[$key] = $this->stored($key) ?? (string) $default;
        }

        return $settings;
    }

    /**
     * @return array<string, string>
     */
    public function policies(): array
    {
        $settings = $this->all();

        return [
            'payment' => $settings['order_policy_payment'],
            'cancellation' => $settings['order_policy_cancellation'],
            'refund' => $settings['order_policy_refund'],
            'unclaimed' => $settings['order_policy_unclaimed'],
        ];
    }

    public function pickupAdvanceRequired(): bool
    {
        return $this->boolean('pickup_advance_payment_required');
    }

    public function depositEnabled(): bool
    {
        return $this->boolean('pickup_deposit_enabled');
    }

    public function depositType(): string
    {
        return $this->all()['pickup_deposit_type'] === 'fixed' ? 'fixed' : 'percent';
    }

    public function depositValue(): float
    {
        return max(0, (float) $this->all()['pickup_deposit_value']);
    }

    public function prepMinutes(): int
    {
        return max(0, (int) $this->all()['pickup_prep_minutes']);
    }

    public function deadlineMinutes(): int
    {
        return max(1, (int) $this->all()['pickup_deadline_minutes']);
    }

    public function minNoticeMinutes(): int
    {
        return max(0, (int) $this->all()['pickup_min_notice_minutes']);
    }

    public function unpaidExpiryMinutes(): int
    {
        return max(5, (int) $this->all()['pickup_unpaid_expiry_minutes']);
    }

    public function requiredAdvanceFor(float $total): float
    {
        $total = round(max(0, $total), 2);

        if (! $this->pickupAdvanceRequired()) {
            return 0.0;
        }

        if (! $this->depositEnabled()) {
            return $total;
        }

        if ($this->depositType() === 'fixed') {
            return round(min($total, $this->depositValue()), 2);
        }

        return round(min($total, $total * ($this->depositValue() / 100)), 2);
    }

    public function requiredPaymentFor(string $serviceType, float $total): float
    {
        $total = round(max(0, $total), 2);

        return $serviceType === 'pickup' ? $this->requiredAdvanceFor($total) : $total;
    }

    public function qrCodePath(): ?string
    {
        $path = trim((string) ($this->all()['payment_qr_code'] ?? ''));

        return $path === '' ? null : $path;
    }

    public function qrCodeUrl(): ?string
    {
        $path = $this->qrCodePath();

        return $path === null ? null : asset($path);
    }

    public function paymentInstructions(): string
    {
        return trim((string) ($this->all()['payment_qr_instructions'] ?? ''));
    }

    /**
     * @return array<string, string>
     */
    private function defaults(): array
    {
        return [
            'pickup_advance_payment_required' => (int) config('ordering.pickup.advance_payment_required') ? '1' : '0',
            'pickup_deposit_enabled' => (int) config('ordering.pickup.deposit_enabled') ? '1' : '0',
            'pickup_deposit_type' => (string) config('ordering.pickup.deposit_type', 'percent'),
            'pickup_deposit_value' => (string) config('ordering.pickup.deposit_value', 100),
            'pickup_unpaid_expiry_minutes' => (string) config('ordering.pickup.unpaid_expiry_minutes', 60),
            'pickup_prep_minutes' => (string) config('ordering.pickup.prep_minutes', 20),
            'pickup_deadline_minutes' => (string) config('ordering.pickup.deadline_minutes', 30),
            'pickup_min_notice_minutes' => (string) config('ordering.pickup.min_notice_minutes', 30),
            'payment_qr_code' => '',
            'payment_qr_instructions' => (string) config('ordering.pickup.payment_instructions'),
            'order_policy_payment' => (string) config('ordering.policies.payment'),
            'order_policy_cancellation' => (string) config('ordering.policies.cancellation'),
            'order_policy_refund' => (string) config('ordering.policies.refund'),
            'order_policy_unclaimed' => (string) config('ordering.policies.unclaimed'),
        ];
    }

    private function boolean(string $key): bool
    {
        return $this->all()[$key] === '1';
    }

    private function stored(string $key): ?string
    {
        try {
            return SiteSetting::query()->where('key', $key)->value('value');
        } catch (QueryException) {
            return null;
        }
    }
}
