<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateWinnerShowcaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'winner_name' => ['sometimes', 'string', 'max:120'],
            'product_name' => ['sometimes', 'string', 'max:255'],
            'short_description' => ['nullable', 'string', 'max:280'],
            'final_price' => ['sometimes', 'numeric', 'min:0'],
            'retail_value' => ['sometimes', 'numeric', 'min:0.01'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
            'image' => ['nullable', 'image', 'max:5120'],
        ];
    }
}
