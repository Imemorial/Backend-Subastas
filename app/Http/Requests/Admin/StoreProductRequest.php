<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'real_cost' => ['required', 'numeric', 'min:0.01'],
            'retail_value' => ['nullable', 'numeric', 'min:0'],
            'sku' => ['nullable', 'string', 'max:64', 'unique:products,sku'],
            'image' => ['nullable', 'image', 'max:5120'],
            'images' => ['nullable', 'array', 'max:10'],
            'images.*' => ['image', 'max:5120'],
            'status' => ['nullable', Rule::in(['draft', 'published', 'archived'])],
            'estimated_bits' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
