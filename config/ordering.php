<?php

return [
    'pickup' => [
        'advance_payment_required' => true,
        'deposit_enabled' => true,
        'deposit_type' => 'percent',
        'deposit_value' => 100,
        'prep_minutes' => 20,
        'deadline_minutes' => 30,
        'min_notice_minutes' => 30,
        'unpaid_expiry_minutes' => 60,
        'payment_instructions' => 'Scan the QR code with your GCash app and pay the amount shown. Upload a screenshot or photo of your payment confirmation and enter the reference number. Our team verifies every payment before preparation starts.',
    ],

    'currency' => '₱',

    'reports' => [
        'audit_retention_days' => 365,
        'export_row_limit' => 5000,
    ],

    'policies' => [
        'payment' => 'Dine-in orders are paid at the cashier before preparation. Pickup orders require a verified advance payment or the required deposit before we start preparing. A payment is never treated as paid until our team verifies it.',
        'cancellation' => 'You may request cancellation before preparation begins. Once preparation has started, cancellation may be declined, and a verified deposit may be forfeited.',
        'refund' => 'Verified payments for cancelled or unfulfilled orders are reviewed by staff. Approved refunds are returned through the original payment method and recorded against the order.',
        'unclaimed' => 'Pickup orders that are not collected within the pickup deadline are flagged Overdue. Overdue orders may be cancelled, the prepared items recorded as waste, and any deposit forfeited.',
    ],
];
