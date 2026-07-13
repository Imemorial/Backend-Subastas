<?php

declare(strict_types=1);

namespace App\Services\Payment;

final class StripeMockGateway
{
    /**
     * Simula un cargo exitoso de Stripe.
     *
     * @param  array<string, mixed>  $metadata
     * @return array{success: bool, payment_intent_id: string}
     */
    public function charge(float $amountEur, array $metadata = []): array
    {
        return [
            'success' => true,
            'payment_intent_id' => 'pi_mock_'.uniqid(),
        ];
    }
}
