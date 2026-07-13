<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

final class MarginPreviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'real_cost' => ['required', 'numeric', 'min:0.01'],
            'bits_consumed' => ['sometimes', 'integer', 'min:0'],
            'retail_value' => ['sometimes', 'numeric', 'min:0'],
            'min_margin_percent' => ['sometimes', 'numeric', 'min:10', 'max:30'],
            'max_margin_percent' => ['sometimes', 'numeric', 'min:15', 'max:40'],
        ];
    }
}
