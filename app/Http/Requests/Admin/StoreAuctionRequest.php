<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

final class StoreAuctionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'product_id' => ['required', 'exists:products,id'],
            'starting_price' => ['nullable', 'numeric', 'min:0'],
            'bid_increment' => ['nullable', 'numeric', 'min:0.01'],
            'initial_timer_seconds' => ['nullable', 'integer', 'min:5', 'max:120'],
            'timer_extension_seconds' => ['nullable', 'integer', 'min:5', 'max:60'],
            'min_margin_percent' => ['nullable', 'numeric', 'min:10', 'max:30'],
            'max_margin_percent' => ['nullable', 'numeric', 'min:15', 'max:40'],
            'scheduled_at' => ['nullable', 'date', 'after:now'],
            'start_immediately' => ['nullable', 'boolean'],
            'planned_bits' => ['nullable', 'integer', 'min:0'],
            'planned_closing_price' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
