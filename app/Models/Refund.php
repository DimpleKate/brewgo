<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Refund extends Model
{
    use HasFactory;

    public const STATUS_APPROVED = 'approved';

    public const STATUS_PROCESSED = 'processed';

    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'order_id',
        'order_request_id',
        'payment_id',
        'amount',
        'method',
        'reference',
        'status',
        'reason',
        'notes',
        'processed_by',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'processed_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(OrderRequest::class, 'order_request_id');
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function methodLabel(): string
    {
        return match ($this->method) {
            Payment::METHOD_CASH => 'Cash',
            Payment::METHOD_QR => 'GCash / QR',
            default => ucfirst((string) $this->method),
        };
    }
}
