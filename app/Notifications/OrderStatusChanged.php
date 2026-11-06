<?php

namespace App\Notifications;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Support\Branding;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderStatusChanged extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Order $order,
        public OrderStatus $status,
        public ?OrderStatus $previousStatus = null,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $order = $this->order;

        return (new MailMessage)
            ->subject($this->title().' · Order '.$order->order_number)
            ->greeting($this->title())
            ->line($this->message())
            ->line('Order: '.$order->order_number)
            ->line('Status: '.$this->status->label())
            ->line('Service: '.$order->serviceLabel())
            ->when(
                $order->pickup_at,
                fn (MailMessage $message) => $message->line('Pickup schedule: '.$order->pickup_at->format('M d, Y · h:i A'))
            )
            ->line('Total: ₱'.number_format((float) $order->total, 2))
            ->action('View order', route('orders.show', $order))
            ->line('Thank you for choosing '.Branding::name().'.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title(),
            'message' => $this->message(),
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'previous_status' => $this->previousStatus?->value,
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'service_type' => $this->order->service_type,
            'pickup_at' => $this->order->pickup_at?->toIso8601String(),
            'order_total' => (float) $this->order->total,
            'url' => route('orders.show', $this->order),
        ];
    }

    private function title(): string
    {
        return match ($this->status) {
            OrderStatus::PendingPayment => 'Order placed',
            OrderStatus::Pending => 'Order pending',
            OrderStatus::Paid => 'Payment confirmed',
            OrderStatus::Confirmed => 'Order confirmed',
            OrderStatus::Preparing => 'Preparing your order',
            OrderStatus::Ready => 'Ready for pickup',
            OrderStatus::Overdue => 'Pickup overdue',
            OrderStatus::Completed => 'Order completed',
            OrderStatus::Rejected => 'Order rejected',
            OrderStatus::Cancelled => 'Order cancelled',
        };
    }

    private function message(): string
    {
        $number = $this->order->order_number;

        return match ($this->status) {
            OrderStatus::PendingPayment => 'Order '.$number.' has been placed and is awaiting confirmation.',
            OrderStatus::Pending => 'Order '.$number.' is pending confirmation.',
            OrderStatus::Paid => 'Payment for order '.$number.' has been confirmed.',
            OrderStatus::Confirmed => 'Order '.$number.' has been confirmed.',
            OrderStatus::Preparing => 'Order '.$number.' is now being prepared.',
            OrderStatus::Ready => $this->order->isPickup()
                ? 'Order '.$number.' is ready for pickup.'
                : 'Order '.$number.' is ready to be served.',
            OrderStatus::Overdue => 'Order '.$number.' was not collected by its pickup deadline. Please contact Brew & Go if you still need it.',
            OrderStatus::Completed => 'Order '.$number.' has been completed. Thank you for choosing Brew & Go.',
            OrderStatus::Rejected => 'Order '.$number.' has been rejected. Please contact Brew & Go for assistance.',
            OrderStatus::Cancelled => 'Order '.$number.' has been cancelled.',
        };
    }
}
