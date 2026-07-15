<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class WelcomeNotification extends Notification
{
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $frontendUrl = rtrim((string) config('app.frontend_url'), '/');
        $name = $notifiable->name ?: 'Jugador';

        return (new MailMessage)
            ->subject('¡Bienvenido a BitsAuction!')
            ->greeting("Hola, {$name}")
            ->line('Tu cuenta en BitsAuction ya está lista. Prepárate para competir en subastas en directo y llevarte premios a precios imposibles.')
            ->line('Recarga Bits, entra en la mesa y haz tu primera puja cuando veas una subasta activa.')
            ->action('Entrar y jugar', $frontendUrl)
            ->line('¡Mucha suerte en la mesa!');
    }
}
