<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class PurchaseBitPackRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'bit_pack_id' => ['required', 'exists:bit_packs,id'],
            'idempotency_key' => ['nullable', 'string', 'max:64'],
        ];
    }
}
