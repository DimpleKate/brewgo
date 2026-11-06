<?php

namespace App\Notifications;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Support\Branding;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderReadyForPickup extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Order $order) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $deadline = $this->order->pickup_deadline_at;

        return (new MailMessage)
            ->subject('Your order '.$this->order->order_number.' is ready for pickup')
            ->greeting('Your order is ready!')
            ->line('Order '.$this->order->order_number.' is ready for pickup.')
            ->line('Pickup name: '.($this->order->pickup_name ?: $notifiable->name))
            ->line('Total: ₱'.number_format((float) $this->order->total, 2))
            ->when($deadline, fn (MailMessage $message) => $message->line('Please collect it by '.$deadline->format('M d, Y · h:i A').'.'))
            ->line('Orders not collected by the pickup deadline are marked Overdue and may be cancelled.')
            ->action('View order', route('orders.show', $this->order))
            ->line('Thank you for choosing '.Branding::name().'.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Ready for pickup',
            'message' => 'Order '.$this->order->order_number.' is ready for pickup.',
            'status' => OrderStatus::Ready->value,
            'status_label' => OrderStatus::Ready->label(),
            'previous_status' => null,
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'service_type' => $this->order->service_type,
            'pickup_at' => $this->order->pickup_at?->toIso8601String(),
            'pickup_deadline_at' => $this->order->pickup_deadline_at?->toIso8601String(),
            'order_total' => (float) $this->order->total,
            'url' => route('orders.show', $this->order),
        ];
    }
}
