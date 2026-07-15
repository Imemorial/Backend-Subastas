<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

final class ResetPasswordNotification extends ResetPassword
{
    public function toMail($notifiable): MailMessage
    {
        $frontendUrl = rtrim((string) config('app.frontend_url'), '/');
        $resetUrl = $frontendUrl.'/restablecer-contrasena?'.http_build_query([
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);

        return (new MailMessage)
            ->subject('Recupera tu contraseña - BitsAuction')
            ->greeting('Hola')
            ->line('Has solicitado restablecer la contraseña de tu cuenta en BitsAuction.')
            ->action('Restablecer contraseña', $resetUrl)
            ->line('Este enlace caduca en '.config('auth.passwords.users.expire').' minutos.')
            ->line('Si no solicitaste este cambio, puedes ignorar este correo.');
    }
}
