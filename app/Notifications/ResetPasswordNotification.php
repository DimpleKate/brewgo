<?php

namespace App\Notifications;

use App\Support\Branding;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $token, public bool $admin = false) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $route = $this->admin ? 'admin.password.reset' : 'password.reset';
        $url = route($route, [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);

        return (new MailMessage)
            ->subject('Reset your '.Branding::name().' password')
            ->greeting('Password reset requested')
            ->line('We received a request to reset the password for your '.($this->admin ? 'administrator ' : '').'account.')
            ->action('Reset password', $url)
            ->line('This link expires in 60 minutes.')
            ->line('If you did not request a password reset, no further action is required. Your password will not change.');
    }
}
