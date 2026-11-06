<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderRequest;
use App\Models\Payment;
use App\Models\Product;
use App\Models\SiteSetting;
use App\Models\User;
use App\Notifications\OrderOverdue;
use App\Notifications\OrderReadyForPickup;
use App\Notifications\OrderStatusChanged;
use App\Services\OrderStatusNotifier;
use App\Services\PaymentProofService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class OrderProtectionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->seedTestCatalog();
        $this->createCustomer();
        Storage::fake(PaymentProofService::DISK);
    }

    public function test_dine_in_order_stays_pending_payment_until_the_cashier_verifies_payment(): void
    {
        $customer = $this->customer();
        $order = $this->placeOrder($customer);

        $this->assertSame(OrderStatus::PendingPayment, $order->status);
        $this->assertSame(330.0, (float) $order->total);
        $this->assertSame(330.0, (float) $order->required_payment_amount);
        $this->assertSame(Order::PAYMENT_PENDING, $order->payment_status);
        $this->assertSame(0.0, (float) $order->amount_paid);

        $payment = $order->payments()->firstOrFail();
        $this->assertSame(Payment::METHOD_CASH, $payment->method);
        $this->assertSame(Payment::STATUS_PENDING, $payment->status);
        $this->assertSame(330.0, (float) $payment->amount);

        $admin = $this->admin();

        $this->actingAs($admin)
            ->patch(route('admin.orders.status', $order), ['status' => 'Preparing'])
            ->assertSessionHasErrors('status');

        $this->assertSame(OrderStatus::PendingPayment, $order->refresh()->status);
        $this->assertSame(0, $order->receipts()->count());

        $this->actingAs($admin)
            ->patch(route('admin.orders.payments.verify', [$order, $payment]), [
                'note' => 'Cash received at the counter.',
            ])
            ->assertRedirect(route('admin.orders.show', $order));

        $order->refresh();
        $payment->refresh();

        $this->assertSame(OrderStatus::Paid, $order->status);
        $this->assertSame(Order::PAYMENT_PAID, $order->payment_status);
        $this->assertSame(330.0, (float) $order->amount_paid);
        $this->assertSame(0.0, $order->balanceDue());
        $this->assertNotNull($order->paid_at);
        $this->assertSame(Payment::STATUS_VERIFIED, $payment->status);
        $this->assertSame($admin->id, $payment->verified_by);

        $receipt = $order->receipts()->firstOrFail();
        $snapshot = $receipt->snapshot;

        $this->assertStringStartsWith('RCPT-', $receipt->receipt_number);
        $this->assertSame(330.0, (float) $receipt->amount);
        $this->assertSame(0.0, (float) $receipt->balance_due);
        $this->assertSame(Payment::METHOD_CASH, $receipt->payment_method);
        $this->assertSame($admin->id, $receipt->issued_by);
        $this->assertSame($order->order_number, $snapshot['order_number']);
        $this->assertSame('Brew & Go Signature', $snapshot['items'][0]['product_name']);
        $this->assertSame(2, $snapshot['items'][0]['quantity']);
        $this->assertSame('Regular', collect($snapshot['items'][0]['options'])->firstWhere('group', 'size')['name']);

        $this->actingAs($customer)
            ->get(route('receipts.show', $receipt))
            ->assertOk()
            ->assertSee($order->order_number)
            ->assertSee('Brew & Go Signature')
            ->assertSee('Cash at cashier');

        $other = User::factory()->create();
        $this->actingAs($other)->get(route('receipts.show', $receipt))->assertForbidden();

        foreach (['Preparing', 'Ready', 'Completed'] as $status) {
            $this->actingAs($admin)
                ->patch(route('admin.orders.status', $order), ['status' => $status])
                ->assertRedirect(route('admin.orders.show', $order));
        }

        $this->assertSame(OrderStatus::Completed, $order->refresh()->status);
    }

    public function test_dine_in_checkout_no_longer_requires_a_table_number(): void
    {
        $customer = $this->customer();
        $this->addToCart($customer);

        $this->actingAs($customer)
            ->post('/checkout', [
                'service_type' => 'dine_in',
                'people_count' => 2,
            ])
            ->assertSessionHasNoErrors();

        $order = Order::latest('id')->firstOrFail();

        $this->assertSame(Order::SERVICE_DINE_IN, $order->service_type);
        $this->assertNull($order->table_number);
    }

    public function test_pickup_checkout_requires_a_qr_reference_and_a_valid_pickup_time(): void
    {
        $customer = $this->customer();
        $this->addToCart($customer);

        $this->actingAs($customer)
            ->from('/checkout')
            ->post('/checkout', $this->pickupPayload([
                'payment_reference' => null,
            ]))
            ->assertRedirect('/checkout')
            ->assertSessionHasErrors('payment_reference');

        $this->actingAs($customer)
            ->from('/checkout')
            ->post('/checkout', $this->pickupPayload([
                'pickup_at' => now()->addMinutes(5)->format('Y-m-d H:i:s'),
            ]))
            ->assertRedirect('/checkout')
            ->assertSessionHasErrors('pickup_at');

        $this->actingAs($customer)
            ->from('/checkout')
            ->post('/checkout', $this->pickupPayload([
                'payment_method' => 'cash',
            ]))
            ->assertRedirect('/checkout')
            ->assertSessionHasErrors('payment_method');

        $this->actingAs($customer)
            ->from('/checkout')
            ->post('/checkout', $this->pickupPayload([
                'payment_proof' => null,
            ]))
            ->assertRedirect('/checkout')
            ->assertSessionHasErrors('payment_proof');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_pickup_checkout_shows_the_saved_qr_instructions_and_proof_upload(): void
    {
        $customer = $this->customer();

        SiteSetting::updateOrCreate(['key' => 'payment_qr_code'], ['value' => 'images/payment-qr/store-qr.png']);
        SiteSetting::updateOrCreate(['key' => 'payment_qr_instructions'], ['value' => 'Scan this QR with GCash and upload your receipt.']);

        $this->addToCart($customer);

        $this->actingAs($customer)
            ->get('/checkout')
            ->assertOk()
            ->assertSee('images/payment-qr/store-qr.png')
            ->assertSee('Scan this QR with GCash and upload your receipt.')
            ->assertSee('Proof of payment')
            ->assertSee('name="payment_proof"', false)
            ->assertSee('Advance payment required');
    }

    public function test_verified_pickup_full_advance_payment_clears_preparation(): void
    {
        Notification::fake();

        $customer = $this->customer();
        $admin = $this->admin();
        $order = $this->placeOrder($customer, $this->pickupPayload());

        $this->assertSame(OrderStatus::PendingPayment, $order->status);
        $this->assertSame(330.0, (float) $order->total);
        $this->assertSame(330.0, (float) $order->required_payment_amount);
        $this->assertSame(Order::PAYMENT_PENDING, $order->payment_status);
        $this->assertNotNull($order->pickup_at);

        $payment = $order->payments()->firstOrFail();
        $this->assertSame(Payment::METHOD_QR, $payment->method);
        $this->assertSame(Payment::TYPE_FULL, $payment->type);
        $this->assertSame('GCASH-REF-123', $payment->reference);
        $this->assertSame(330.0, (float) $payment->amount);
        $this->assertNotNull($payment->proof_path);
        $this->assertTrue(Storage::disk(PaymentProofService::DISK)->exists(basename($payment->proof_path)));

        $this->actingAs($admin)
            ->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertSee('View proof of payment');

        $this->actingAs($admin)
            ->patch(route('admin.orders.payments.verify', [$order, $payment]))
            ->assertRedirect(route('admin.orders.show', $order));

        $order->refresh();

        $this->assertSame(OrderStatus::Paid, $order->status);
        $this->assertSame(Order::PAYMENT_PAID, $order->payment_status);
        $this->assertSame(330.0, (float) $order->amount_paid);
        $this->assertSame(0.0, $order->balanceDue());
        $this->assertTrue($order->isClearedForPreparation());

        $paymentReceipt = $order->receipts()->firstOrFail();
        $this->assertSame(0.0, (float) $paymentReceipt->balance_due);
        $this->assertSame(330.0, (float) $paymentReceipt->amount);

        $this->actingAs($admin)
            ->patch(route('admin.orders.status', $order), ['status' => 'Preparing'])
            ->assertRedirect(route('admin.orders.show', $order));

        $order->refresh();
        $this->assertSame(OrderStatus::Preparing, $order->status);
        $this->assertNotNull($order->preparation_start_at);

        $this->actingAs($admin)
            ->patch(route('admin.orders.status', $order), ['status' => 'Ready'])
            ->assertRedirect(route('admin.orders.show', $order));

        $order->refresh();

        $this->assertSame(OrderStatus::Ready, $order->status);
        $this->assertNotNull($order->pickup_deadline_at);
        $this->assertTrue($order->pickup_deadline_at->equalTo($order->pickup_at->copy()->addMinutes(30)));

        Notification::assertSentTo($customer, OrderReadyForPickup::class);
    }

    public function test_pickup_without_advance_payment_can_be_marked_paid_and_prepared_without_payment_records(): void
    {
        SiteSetting::updateOrCreate(
            ['key' => 'pickup_advance_payment_required'],
            ['value' => '0'],
        );

        $customer = $this->customer();
        $admin = $this->admin();

        $order = $this->placeOrder($customer, $this->pickupPayload([
            'payment_method' => 'cash',
            'payment_reference' => null,
        ]));

        $this->assertSame(OrderStatus::PendingPayment, $order->status);
        $this->assertSame(0.0, (float) $order->required_payment_amount);
        $this->assertSame(Order::PAYMENT_UNPAID, $order->payment_status);
        $this->assertSame(0, $order->payments()->count());

        foreach (['Paid', 'Preparing'] as $status) {
            $this->actingAs($admin)
                ->patch(route('admin.orders.status', $order), ['status' => $status])
                ->assertRedirect(route('admin.orders.show', $order));
        }

        $this->assertSame(OrderStatus::Preparing, $order->refresh()->status);
        $this->assertSame(0.0, (float) $order->amount_paid);
        $this->assertSame(0, $order->receipts()->count());
    }

    public function test_partial_dine_in_payment_keeps_the_order_pending_until_the_full_amount_is_verified(): void
    {
        $customer = $this->customer();
        $admin = $this->admin();
        $order = $this->placeOrder($customer);

        $pending = $order->payments()->firstOrFail();

        $this->actingAs($admin)
            ->patch(route('admin.orders.payments.reject', [$order, $pending]), [
                'rejection_reason' => 'The full cash amount has not been received yet.',
            ]);

        $this->actingAs($admin)
            ->post(route('admin.orders.payments.record', $order), [
                'method' => Payment::METHOD_CASH,
                'amount' => 100,
            ])
            ->assertRedirect(route('admin.orders.show', $order));

        $order->refresh();

        $this->assertSame(OrderStatus::PendingPayment, $order->status);
        $this->assertSame(Order::PAYMENT_DEPOSIT, $order->payment_status);
        $this->assertSame(100.0, (float) $order->amount_paid);
        $this->assertSame(230.0, $order->balanceDue());
        $this->assertFalse($order->isClearedForPreparation());

        $this->actingAs($admin)
            ->patch(route('admin.orders.status', $order), ['status' => 'Paid'])
            ->assertSessionHasErrors('status');

        $this->actingAs($admin)
            ->post(route('admin.orders.payments.record', $order), [
                'method' => Payment::METHOD_CASH,
                'amount' => 230,
            ])
            ->assertRedirect(route('admin.orders.show', $order));

        $order->refresh();

        $this->assertSame(OrderStatus::Paid, $order->status);
        $this->assertSame(Order::PAYMENT_PAID, $order->payment_status);
        $this->assertSame(330.0, (float) $order->amount_paid);
        $this->assertSame(0.0, $order->balanceDue());
        $this->assertSame(2, $order->receipts()->count());
    }

    public function test_rejected_payment_never_marks_the_order_as_paid(): void
    {
        $customer = $this->customer();
        $admin = $this->admin();
        $order = $this->placeOrder($customer, $this->pickupPayload());
        $payment = $order->payments()->firstOrFail();

        $this->actingAs($admin)
            ->patch(route('admin.orders.payments.reject', [$order, $payment]), [
                'rejection_reason' => 'Reference number was not found.',
            ])
            ->assertRedirect(route('admin.orders.show', $order));

        $payment->refresh();
        $order->refresh();

        $this->assertSame(Payment::STATUS_REJECTED, $payment->status);
        $this->assertSame($admin->id, $payment->rejected_by);
        $this->assertSame('Reference number was not found.', $payment->rejection_reason);
        $this->assertSame(OrderStatus::PendingPayment, $order->status);
        $this->assertSame(Order::PAYMENT_UNPAID, $order->payment_status);
        $this->assertSame(0.0, (float) $order->amount_paid);
        $this->assertSame(0, $order->receipts()->count());

        $this->actingAs($admin)
            ->patch(route('admin.orders.status', $order), ['status' => 'Paid'])
            ->assertSessionHasErrors(['status' => 'Verify the required payment before marking this order as Paid.']);

        $this->assertSame(OrderStatus::PendingPayment, $order->refresh()->status);
    }

    public function test_ready_pickup_notifies_the_customer_and_the_overdue_command_notifies_admins(): void
    {
        Notification::fake();

        $customer = $this->customer();
        $admin = $this->admin();
        $order = $this->placeOrder($customer, $this->pickupPayload());
        $payment = $order->payments()->firstOrFail();

        $this->actingAs($admin)->patch(route('admin.orders.payments.verify', [$order, $payment]));
        $this->actingAs($admin)->patch(route('admin.orders.status', $order), ['status' => 'Preparing']);
        $this->actingAs($admin)->patch(route('admin.orders.status', $order), ['status' => 'Ready']);

        $order->refresh();
        Notification::assertSentTo($customer, OrderReadyForPickup::class);

        $this->travelTo($order->pickup_deadline_at->copy()->addMinute());

        $this->artisan('orders:flag-overdue')
            ->expectsOutputToContain('Flagged 1 overdue pickup order(s).')
            ->assertExitCode(0);

        $order->refresh();

        $this->assertSame(OrderStatus::Overdue, $order->status);
        $this->assertNotNull($order->overdue_at);

        Notification::assertSentTo($admin, OrderOverdue::class);
        Notification::assertNotSentTo($customer, OrderOverdue::class);

        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id,
            'status' => OrderStatus::Overdue->value,
        ]);
    }

    public function test_each_order_status_change_notifies_the_customer_with_the_new_status(): void
    {
        Notification::fake();

        $customer = $this->customer();
        $admin = $this->admin();
        $order = $this->placeOrder($customer, $this->pickupPayload());

        Notification::assertSentTo(
            $customer,
            OrderStatusChanged::class,
            fn (OrderStatusChanged $notification) => $notification->status === OrderStatus::PendingPayment
        );

        $payment = $order->payments()->firstOrFail();

        $this->actingAs($admin)->patch(route('admin.orders.payments.verify', [$order, $payment]));

        Notification::assertSentTo(
            $customer,
            OrderStatusChanged::class,
            fn (OrderStatusChanged $notification) => $notification->status === OrderStatus::Paid
        );

        foreach (['Preparing', 'Ready', 'Completed'] as $status) {
            $this->actingAs($admin)
                ->patch(route('admin.orders.status', $order), ['status' => $status])
                ->assertRedirect(route('admin.orders.show', $order));
        }

        $this->assertSame(OrderStatus::Completed, $order->refresh()->status);

        Notification::assertSentTo(
            $customer,
            OrderStatusChanged::class,
            fn (OrderStatusChanged $notification) => $notification->status === OrderStatus::Preparing
        );
        Notification::assertSentTo(
            $customer,
            OrderReadyForPickup::class,
            fn (OrderReadyForPickup $notification) => $notification->order->is($order)
        );
        Notification::assertSentTo(
            $customer,
            OrderStatusChanged::class,
            fn (OrderStatusChanged $notification) => $notification->status === OrderStatus::Completed
        );
    }

    public function test_order_status_notifier_covers_pending_confirmed_and_every_other_status(): void
    {
        Notification::fake();

        $customer = $this->customer();
        $order = $this->placeOrder($customer, $this->pickupPayload());
        $notifier = app(OrderStatusNotifier::class);

        foreach (OrderStatus::cases() as $status) {
            $notifier->notify($order, $status);
        }

        foreach (OrderStatus::cases() as $status) {
            if ($status === OrderStatus::Ready) {
                Notification::assertSentTo(
                    $customer,
                    OrderReadyForPickup::class,
                    function (OrderReadyForPickup $notification) use ($customer, $order): bool {
                        $data = $notification->toArray($customer);

                        return $notification->order->is($order)
                            && $data['status'] === OrderStatus::Ready->value
                            && $data['status_label'] === OrderStatus::Ready->label()
                            && $data['order_number'] === $order->order_number
                            && $data['order_total'] === (float) $order->total
                            && $data['url'] === route('orders.show', $order);
                    }
                );

                continue;
            }

            Notification::assertSentTo(
                $customer,
                OrderStatusChanged::class,
                function (OrderStatusChanged $notification) use ($customer, $status, $order): bool {
                    $data = $notification->toArray($customer);

                    return $notification->status === $status
                        && $data['status'] === $status->value
                        && $data['status_label'] === $status->label()
                        && $data['order_number'] === $order->order_number
                        && $data['order_total'] === (float) $order->total
                        && $data['url'] === route('orders.show', $order);
                }
            );
        }
    }

    public function test_approved_cancellation_request_notifies_the_customer(): void
    {
        Notification::fake();

        $customer = $this->customer();
        $admin = $this->admin();
        $order = $this->placeOrder($customer, $this->pickupPayload());

        $request = OrderRequest::create([
            'order_id' => $order->id,
            'user_id' => $customer->id,
            'type' => OrderRequest::TYPE_CANCELLATION,
            'reason' => 'I no longer need this order.',
            'status' => OrderRequest::STATUS_PENDING,
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.requests.review', $request), [
                'decision' => OrderRequest::STATUS_APPROVED,
                'admin_note' => 'Cancellation approved.',
            ])
            ->assertRedirect(route('admin.requests.index'));

        $this->assertSame(OrderStatus::Cancelled, $order->refresh()->status);

        Notification::assertSentTo(
            $customer,
            OrderStatusChanged::class,
            fn (OrderStatusChanged $notification) => $notification->status === OrderStatus::Cancelled
                && $notification->previousStatus === OrderStatus::PendingPayment
        );
    }

    public function test_customer_notification_inbox_shows_status_details_and_read_state(): void
    {
        $customer = $this->customer();
        $order = $this->placeOrder($customer, $this->pickupPayload());

        app(OrderStatusNotifier::class)->notify($order, OrderStatus::Preparing);

        $this->actingAs($customer)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('Preparing your order')
            ->assertSee('Preparing')
            ->assertSee($order->order_number)
            ->assertSee('₱'.number_format((float) $order->total, 2))
            ->assertSee('Pickup '.$order->pickup_at->format('M d, Y · h:i A'));

        $this->actingAs($customer)
            ->getJson(route('notifications.unread-count'))
            ->assertOk()
            ->assertJsonPath('count', 2);

        $this->actingAs($customer)
            ->patch(route('notifications.read-all'))
            ->assertRedirect();

        $this->actingAs($customer)
            ->getJson(route('notifications.unread-count'))
            ->assertOk()
            ->assertJsonPath('count', 0);
    }

    public function test_start_preparation_command_only_starts_due_and_paid_pickup_orders(): void
    {
        $customer = $this->customer();
        $admin = $this->admin();

        $due = $this->placeOrder($customer, $this->pickupPayload());
        $this->actingAs($admin)->patch(route('admin.orders.payments.verify', [$due, $due->payments()->firstOrFail()]));
        $due->update(['pickup_at' => now()->addMinutes(10)]);

        $later = $this->placeOrder($customer, $this->pickupPayload());
        $this->actingAs($admin)->patch(route('admin.orders.payments.verify', [$later, $later->payments()->firstOrFail()]));

        $unpaid = $this->placeOrder($customer, $this->pickupPayload());
        $unpaid->update(['pickup_at' => now()->addMinutes(10)]);

        $this->artisan('orders:start-preparation')
            ->expectsOutputToContain('Started preparation for 1 pickup order(s).')
            ->assertExitCode(0);

        $this->assertSame(OrderStatus::Preparing, $due->refresh()->status);
        $this->assertNotNull($due->preparation_start_at);
        $this->assertSame(OrderStatus::Paid, $later->refresh()->status);
        $this->assertSame(OrderStatus::PendingPayment, $unpaid->refresh()->status);
    }

    public function test_cancelling_a_ready_pickup_order_records_the_waste_and_cancellation_details(): void
    {
        $customer = $this->customer();
        $admin = $this->admin();
        $order = $this->placeOrder($customer, $this->pickupPayload());

        $this->actingAs($admin)->patch(route('admin.orders.payments.verify', [$order, $order->payments()->firstOrFail()]));
        $this->actingAs($admin)->patch(route('admin.orders.status', $order), ['status' => 'Preparing']);
        $this->actingAs($admin)->patch(route('admin.orders.status', $order), ['status' => 'Ready']);

        $this->actingAs($admin)
            ->patch(route('admin.orders.status', $order), [
                'status' => 'Cancelled',
                'note' => 'Customer did not collect the order.',
            ])
            ->assertRedirect(route('admin.orders.show', $order));

        $order->refresh();

        $this->assertSame(OrderStatus::Cancelled, $order->status);
        $this->assertNotNull($order->cancelled_at);
        $this->assertSame('Customer did not collect the order.', $order->cancelled_reason);
        $this->assertSame($admin->id, $order->cancelled_by);

        $waste = $order->wasteItems()->get();
        $this->assertCount(1, $waste);
        $this->assertSame('Brew & Go Signature', $waste->first()->product_name);
        $this->assertSame(2, $waste->first()->quantity);
        $this->assertSame('Customer did not collect the order.', $waste->first()->reason);
        $this->assertSame($admin->id, $waste->first()->logged_by);

        $this->actingAs($admin)->patch(route('admin.orders.status', $order), ['status' => 'Cancelled']);

        $this->assertSame(1, $order->wasteItems()->count());
    }

    public function test_approved_refund_marks_the_preserved_payment_as_refunded(): void
    {
        $customer = $this->customer();
        $admin = $this->admin();
        $order = $this->placeOrder($customer);

        $this->actingAs($admin)->patch(route('admin.orders.payments.verify', [$order, $order->payments()->firstOrFail()]));

        foreach (['Preparing', 'Ready', 'Completed'] as $status) {
            $this->actingAs($admin)->patch(route('admin.orders.status', $order), ['status' => $status]);
        }

        $this->actingAs($customer)
            ->post(route('orders.requests.store', $order), [
                'type' => OrderRequest::TYPE_REFUND,
                'reason' => 'The drink did not match the order.',
            ])
            ->assertRedirect(route('orders.show', $order));

        $orderRequest = $order->requests()->firstOrFail();

        $this->actingAs($admin)
            ->patch(route('admin.requests.review', $orderRequest), [
                'decision' => OrderRequest::STATUS_APPROVED,
                'admin_note' => 'Refund approved and returned in cash.',
            ])
            ->assertRedirect(route('admin.requests.index'));

        $order->refresh();
        $orderRequest->refresh();

        $this->assertSame(OrderRequest::STATUS_APPROVED, $orderRequest->status);
        $this->assertSame(Order::PAYMENT_REFUNDED, $order->payment_status);
        $this->assertSame(330.0, (float) $order->amount_paid);
        $this->assertSame(1, $order->payments()->count());
        $this->assertSame(Payment::STATUS_VERIFIED, $order->payments()->firstOrFail()->status);
    }

    public function test_configured_partial_deposit_preserves_the_deposit_payment_and_creates_a_second_receipt(): void
    {
        SiteSetting::updateOrCreate(['key' => 'pickup_deposit_enabled'], ['value' => '1']);
        SiteSetting::updateOrCreate(['key' => 'pickup_deposit_type'], ['value' => 'percent']);
        SiteSetting::updateOrCreate(['key' => 'pickup_deposit_value'], ['value' => '50']);

        $customer = $this->customer();
        $admin = $this->admin();
        $order = $this->placeOrder($customer, $this->pickupPayload());

        $deposit = $order->payments()->firstOrFail();
        $this->actingAs($admin)->patch(route('admin.orders.payments.verify', [$order, $deposit]));

        $this->actingAs($admin)
            ->post(route('admin.orders.payments.record', $order), [
                'method' => Payment::METHOD_CASH,
                'amount' => 165,
                'notes' => 'Balance settled at pickup.',
            ])
            ->assertRedirect(route('admin.orders.show', $order));

        $order->refresh();
        $deposit->refresh();

        $this->assertSame(Order::PAYMENT_PAID, $order->payment_status);
        $this->assertSame(330.0, (float) $order->amount_paid);
        $this->assertSame(0.0, $order->balanceDue());
        $this->assertSame(Payment::STATUS_VERIFIED, $deposit->status);
        $this->assertSame(165.0, (float) $deposit->amount);
        $this->assertSame(2, $order->payments()->count());
        $this->assertSame(2, $order->receipts()->count());
        $this->assertSame(0.0, (float) $order->receipts()->latest('issued_at')->firstOrFail()->balance_due);
    }

    public function test_admin_can_configure_pickup_protection_and_deposit_validation(): void
    {
        $admin = $this->admin();

        $payload = [
            'brand_name' => 'Brew & Go',
            'brand_tagline' => 'Small batch coffee',
            'contact_phone' => '09171234567',
            'contact_email' => 'hello@brewandgo.test',
            'name' => $admin->name,
            'email' => $admin->email,
            'pickup_advance_payment_required' => '1',
            'pickup_deposit_enabled' => '1',
            'pickup_deposit_type' => 'percent',
            'pickup_deposit_value' => '150',
            'pickup_prep_minutes' => '15',
            'pickup_deadline_minutes' => '45',
            'pickup_min_notice_minutes' => '20',
            'order_policy_payment' => 'Payment policy text.',
            'order_policy_cancellation' => 'Cancellation policy text.',
            'order_policy_refund' => 'Refund policy text.',
            'order_policy_unclaimed' => 'Unclaimed policy text.',
        ];

        $this->actingAs($admin)
            ->from(route('admin.settings.edit'))
            ->put(route('admin.settings.update'), $payload)
            ->assertRedirect(route('admin.settings.edit'))
            ->assertSessionHasErrors('pickup_deposit_value');

        $payload['pickup_deposit_value'] = '40';

        $this->actingAs($admin)
            ->put(route('admin.settings.update'), $payload)
            ->assertRedirect(route('admin.settings.edit'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('site_settings', [
            'key' => 'pickup_deposit_value',
            'value' => '40',
        ]);
        $this->assertDatabaseHas('site_settings', [
            'key' => 'pickup_deadline_minutes',
            'value' => '45',
        ]);
        $this->assertDatabaseHas('site_settings', [
            'key' => 'order_policy_unclaimed',
            'value' => 'Unclaimed policy text.',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.settings.edit'))
            ->assertOk()
            ->assertSee('Pickup protection')
            ->assertSee('Unclaimed policy text.');
    }

    public function test_checkout_displays_the_payment_cancellation_refund_and_unclaimed_policies(): void
    {
        $customer = $this->customer();
        $this->addToCart($customer);

        $this->actingAs($customer)
            ->get('/checkout')
            ->assertOk()
            ->assertSee('Payment, cancellation', false)
            ->assertSee('Dine-in orders are paid at the cashier')
            ->assertSee('You may request cancellation before preparation begins')
            ->assertSee('Approved refunds are returned through the original payment method')
            ->assertSee('flagged Overdue')
            ->assertSee('Pending Payment')
            ->assertSee('Due now (pickup advance)');
    }

    public function test_customer_notification_inbox_marks_updates_as_read(): void
    {
        $customer = $this->customer();
        $order = $this->placeOrder($customer);

        $notification = $customer->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => OrderReadyForPickup::class,
            'data' => [
                'title' => 'Ready for pickup',
                'message' => 'Order '.$order->order_number.' is ready for pickup.',
                'order_number' => $order->order_number,
                'url' => route('orders.show', $order),
            ],
        ]);

        $this->actingAs($customer)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('Ready for pickup')
            ->assertSee($order->order_number);

        $this->actingAs($customer)
            ->get(route('notifications.open', $notification->id))
            ->assertRedirect(route('orders.show', $order));

        $this->assertNotNull($notification->refresh()->read_at);

        $this->actingAs($customer)->patch(route('notifications.read-all'))->assertRedirect();
    }

    public function test_customer_header_groups_account_actions_and_reports_unread_notifications(): void
    {
        $customer = $this->customer();

        $notification = $customer->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => OrderReadyForPickup::class,
            'data' => [
                'title' => 'Ready for pickup',
                'message' => 'Your order is ready.',
            ],
        ]);

        $this->actingAs($customer)
            ->get('/menu')
            ->assertOk()
            ->assertSee('data-bs-toggle="dropdown"', false)
            ->assertSee('Dark mode')
            ->assertSee('Notifications')
            ->assertSee('(1)')
            ->assertSee('data-notification-count', false)
            ->assertSee('data-notification-count-label', false)
            ->assertSee('data-notification-url="'.route('notifications.unread-count').'"', false);

        $this->actingAs($customer)
            ->getJson(route('notifications.unread-count'))
            ->assertOk()
            ->assertJsonPath('count', 1);

        $notification->markAsRead();

        $this->actingAs($customer)
            ->getJson(route('notifications.unread-count'))
            ->assertOk()
            ->assertJsonPath('count', 0);

        $this->actingAs($customer)
            ->get('/menu')
            ->assertOk()
            ->assertSee('data-notification-count-label></span>', false);
    }

    public function test_admin_notification_inbox_marks_overdue_alerts_as_read(): void
    {
        $customer = $this->customer();
        $admin = $this->admin();
        $order = $this->placeOrder($customer, $this->pickupPayload());

        $notification = $admin->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => OrderOverdue::class,
            'data' => [
                'title' => 'Overdue pickup order',
                'message' => 'Order '.$order->order_number.' was not collected.',
                'order_number' => $order->order_number,
                'url' => route('admin.orders.show', $order),
            ],
        ]);

        $this->actingAs($admin)
            ->get(route('admin.notifications.index'))
            ->assertOk()
            ->assertSee('Overdue pickup order')
            ->assertSee($order->order_number);

        $this->actingAs($admin)
            ->get(route('admin.notifications.open', $notification->id))
            ->assertRedirect(route('admin.orders.show', $order));

        $this->assertNotNull($notification->refresh()->read_at);

        $this->actingAs($admin)->patch(route('admin.notifications.read-all'))->assertRedirect();
    }

    private function customer(): User
    {
        return User::where('email', 'customer@brewandgo.test')->firstOrFail();
    }

    private function admin(): User
    {
        return User::where('email', 'admin@brewandgo.test')->firstOrFail();
    }

    private function signature(): Product
    {
        return Product::with(['options', 'addons'])->where('slug', 'brew-and-go-signature')->firstOrFail();
    }

    /**
     * @return array<int, int>
     */
    private function defaultOptions(Product $product): array
    {
        return $product->options
            ->where('is_active', true)
            ->groupBy('group_name')
            ->map(function ($group) {
                $option = $group->firstWhere('is_default', true) ?? $group->first();

                return $option->id;
            })
            ->values()
            ->all();
    }

    private function addToCart(User $customer, int $quantity = 2): void
    {
        $product = $this->signature();

        $this->actingAs($customer)->post('/cart/items', [
            'product_id' => $product->id,
            'quantity' => $quantity,
            'options' => $this->defaultOptions($product),
        ])->assertRedirect(route('cart.index'));
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function placeOrder(User $customer, array $overrides = []): Order
    {
        $this->addToCart($customer);

        $this->actingAs($customer)
            ->post('/checkout', array_merge([
                'service_type' => 'dine_in',
                'people_count' => 2,
            ], $overrides))
            ->assertRedirect();

        return Order::latest('id')->firstOrFail();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function pickupPayload(array $overrides = []): array
    {
        return array_merge([
            'service_type' => 'pickup',
            'pickup_name' => 'Test Customer',
            'pickup_phone' => '09181234567',
            'pickup_at' => now()->addHours(2)->format('Y-m-d H:i:s'),
            'payment_method' => 'qr',
            'payment_reference' => 'GCASH-REF-123',
            'payment_proof' => UploadedFile::fake()->createWithContent(
                'payment-proof.png',
                base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Y9ZlS8AAAAASUVORK5CYII=', true)
            ),
        ], $overrides);
    }
}
