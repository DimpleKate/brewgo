<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderOverdue extends Notification implements ShouldQueue
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
        return (new MailMessage)
            ->subject('Pickup order '.$this->order->order_number.' is overdue')
            ->greeting('Overdue pickup order')
            ->line('Order '.$this->order->order_number.' was not collected by its pickup deadline.')
            ->line('Customer: '.($this->order->pickup_name ?: $this->order->user?->name))
            ->line('Pickup deadline: '.$this->order->pickup_deadline_at?->format('M d, Y · h:i A'))
            ->line('The order has been flagged Overdue. Please contact the customer or cancel the order and log the items as waste if they remain unclaimed.')
            ->action('Open order', route('admin.orders.show', $this->order));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Overdue pickup order',
            'message' => 'Order '.$this->order->order_number.' was not collected by its pickup deadline.',
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'url' => route('admin.orders.show', $this->order),
        ];
    }
}
