<?php

namespace App\Notifications;

use App\Models\OrderRequest;
use App\Support\Branding;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderRequestUpdated extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public OrderRequest $orderRequest) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $order = $this->orderRequest->order;
        $approved = $this->orderRequest->status === OrderRequest::STATUS_APPROVED;

        return (new MailMessage)
            ->subject('Your '.$this->orderRequest->typeLabel().' request was '.$this->orderRequest->status)
            ->greeting('Request '.$this->orderRequest->status)
            ->line('Order: '.$order->order_number)
            ->line('Request: '.$this->orderRequest->typeLabel())
            ->when($this->orderRequest->admin_note, fn (MailMessage $message) => $message->line('Staff note: '.$this->orderRequest->admin_note))
            ->line($approved
                ? 'The request has been approved. Any approved refund is recorded against your order.'
                : 'The request was not approved. Contact '.Branding::name().' if you need more help.')
            ->action('View order', route('orders.show', $order));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->orderRequest->typeLabel().' request '.$this->orderRequest->status,
            'message' => 'Your '.$this->orderRequest->typeLabel().' request for order '.$this->orderRequest->order->order_number.' was '.$this->orderRequest->status.'.',
            'order_id' => $this->orderRequest->order_id,
            'order_number' => $this->orderRequest->order->order_number,
            'request_id' => $this->orderRequest->id,
            'url' => route('orders.show', $this->orderRequest->order),
        ];
    }
}
