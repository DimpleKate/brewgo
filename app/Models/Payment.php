<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    public const METHOD_CASH = 'cash';

    public const METHOD_QR = 'qr';

    public const STATUS_PENDING = 'pending';

    public const STATUS_VERIFIED = 'verified';

    public const STATUS_REJECTED = 'rejected';

    public const TYPE_DEPOSIT = 'deposit';

    public const TYPE_BALANCE = 'balance';

    public const TYPE_FULL = 'full';

    protected $fillable = [
        'order_id',
        'method',
        'type',
        'amount',
        'status',
        'reference',
        'proof_path',
        'notes',
        'verified_by',
        'verified_at',
        'rejected_by',
        'rejected_at',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'verified_at' => 'datetime',
            'rejected_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function rejector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function proofUrl(): ?string
    {
        if (! $this->proof_path) {
            return null;
        }

        return route('payments.proof', $this);
    }

    public function hasProof(): bool
    {
        return filled($this->proof_path);
    }

    public function isVerified(): bool
    {
        return $this->status === self::STATUS_VERIFIED;
    }

    public function methodLabel(): string
    {
        return $this->method === self::METHOD_CASH ? 'Cash at cashier' : 'GCash / QR';
    }

    public function typeLabel(): string
    {
        return match ($this->type) {
            self::TYPE_DEPOSIT => 'Deposit',
            self::TYPE_BALANCE => 'Balance',
            default => 'Full payment',
        };
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            self::STATUS_VERIFIED => 'text-bg-success',
            self::STATUS_REJECTED => 'text-bg-danger',
            default => 'text-bg-warning',
        };
    }
}
