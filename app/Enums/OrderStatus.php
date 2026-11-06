<?php

namespace App\Enums;

enum OrderStatus: string
{
    case PendingPayment = 'Pending Payment';
    case Pending = 'Pending';
    case Paid = 'Paid';
    case Confirmed = 'Confirmed';
    case Preparing = 'Preparing';
    case Ready = 'Ready';
    case Overdue = 'Overdue';
    case Completed = 'Completed';
    case Rejected = 'Rejected';
    case Cancelled = 'Cancelled';

    public function label(): string
    {
        return $this->value;
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::PendingPayment, self::Pending => 'text-bg-warning',
            self::Paid => 'text-bg-info',
            self::Confirmed => 'text-bg-info',
            self::Preparing => 'text-bg-primary',
            self::Ready => 'text-bg-success',
            self::Overdue => 'text-bg-danger',
            self::Completed => 'text-bg-dark border border-success',
            self::Rejected => 'text-bg-danger',
            self::Cancelled => 'text-bg-secondary',
        };
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::Completed, self::Rejected, self::Cancelled], true);
    }

    public function canTransitionTo(self $next): bool
    {
        if ($this === $next) {
            return true;
        }

        return in_array($next, match ($this) {
            self::PendingPayment => [self::Paid, self::Rejected, self::Cancelled],
            self::Pending => [self::Paid, self::Confirmed, self::Rejected, self::Cancelled],
            self::Paid => [self::Preparing, self::Cancelled],
            self::Confirmed => [self::Paid, self::Preparing, self::Rejected, self::Cancelled],
            self::Preparing => [self::Ready, self::Cancelled],
            self::Ready => [self::Completed, self::Overdue, self::Cancelled],
            self::Overdue => [self::Completed, self::Cancelled],
            self::Completed, self::Rejected, self::Cancelled => [],
        }, true);
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
