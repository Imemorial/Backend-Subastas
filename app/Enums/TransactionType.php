<?php

declare(strict_types=1);

namespace App\Enums;

enum TransactionType: string
{
    case BitPurchase = 'bit_purchase';
    case BidDebit = 'bid_debit';
    case ProductPayment = 'product_payment';
    case Refund = 'refund';
    case AdminAdjustment = 'admin_adjustment';
}
