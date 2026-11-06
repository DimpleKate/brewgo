<?php

namespace App\Notifications;

use App\Models\Order;
use App\Models\Payment;
use App\Support\Branding;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentRejected extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Order $order, public Payment $payment) {}

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
            ->subject('Payment for order '.$this->order->order_number.' needs attention')
            ->greeting('We could not verify your payment')
            ->line('The payment of ₱'.number_format((float) $this->payment->amount, 2).' for order '.$this->order->order_number.' was rejected.')
            ->line('Reason: '.($this->payment->rejection_reason ?: 'The payment details could not be verified.'))
            ->line('You can upload a clearer proof of payment or choose another payment method from your order page.')
            ->action('Re-upload or choose a payment method', route('orders.show', $this->order))
            ->line('Need help? Contact '.Branding::name().'.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Payment rejected',
            'message' => 'Payment for order '.$this->order->order_number.' was rejected. '.($this->payment->rejection_reason ?: ''),
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'payment_id' => $this->payment->id,
            'rejection_reason' => $this->payment->rejection_reason,
            'url' => route('orders.show', $this->order),
        ];
    }
}
