<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\BitPack */
final class BitPackResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'bits_amount' => $this->bits_amount,
            'bonus_bits' => $this->bonus_bits,
            'total_bits' => $this->bits_amount + $this->bonus_bits,
            'price_eur' => (float) $this->price_eur,
            'bit_unit_price' => (float) $this->bit_unit_price,
            'is_featured' => $this->is_featured,
        ];
    }
}
