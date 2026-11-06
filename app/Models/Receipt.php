<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Receipt extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'payment_id',
        'receipt_number',
        'amount',
        'balance_due',
        'payment_method',
        'snapshot',
        'issued_by',
        'issued_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'balance_due' => 'decimal:2',
            'snapshot' => 'array',
            'issued_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function methodLabel(): string
    {
        return $this->payment_method === Payment::METHOD_CASH ? 'Cash at cashier' : 'GCash / QR';
    }

    public static function generateReceiptNumber(): string
    {
        return 'RCPT-'.now()->format('ymd').'-'.strtoupper(str_pad((string) random_int(0, 99999), 5, '0', STR_PAD_LEFT));
    }
}
