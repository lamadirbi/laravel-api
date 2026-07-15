<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPasswordNotification extends ResetPassword
{
    public function toMail($notifiable): MailMessage
    {
        $frontend = rtrim((string) env('FRONTEND_URL', 'http://localhost:3000'), '/');
        $email = urlencode($notifiable->getEmailForPasswordReset());
        $token = urlencode($this->token);
        $url = "{$frontend}/reset-password?token={$token}&email={$email}";

        return (new MailMessage)
            ->subject('إعادة تعيين كلمة المرور — GazaCare Connect')
            ->greeting('مرحباً')
            ->line('تلقّيت هذا البريد لأننا استلمنا طلباً لإعادة تعيين كلمة المرور لحسابك.')
            ->action('إعادة تعيين كلمة المرور', $url)
            ->line('إذا لم تطلب إعادة التعيين، تجاهل هذه الرسالة.')
            ->salutation('GazaCare Connect');
    }
}
