<?php

declare(strict_types=1);

return [
    'bit_value_eur' => (float) env('BIT_VALUE_EUR', 0.20),

    'margin' => [
        'auction_min_percent' => (float) env('AUCTION_MARGIN_MIN', 17.0),
        'auction_max_percent' => (float) env('AUCTION_MARGIN_MAX', 25.0),
        'weekly_min_percent' => (float) env('WEEKLY_MARGIN_MIN', 17.0),
        'weekly_max_percent' => (float) env('WEEKLY_MARGIN_MAX', 25.0),
        // El margen semanal manda: una subasta puede cerrar barata si la semana ya va bien.
        'weekly_primary' => (bool) env('WEEKLY_MARGIN_PRIMARY', true),
        // Margen mínimo en subastas ya cerradas esta semana antes de permitir cierres subsidiados.
        'subsidy_bank_min_percent' => (float) env('WEEKLY_SUBSIDY_BANK_MIN', 17.0),
    ],

    'pricing' => [
        'retail_multiplier' => (float) env('AUCTION_RETAIL_MULTIPLIER', 1.85),
        'margin_carrier_cost_threshold' => (float) env('AUCTION_MARGIN_CARRIER_THRESHOLD', 500.0),
    ],

    'timer' => [
        'retention_extension_seconds' => (int) env('AUCTION_RETENTION_EXTENSION', 8),
        'min_remaining_to_allow_close' => (int) env('AUCTION_MIN_REMAINING_CLOSE', 0),
    ],
];
