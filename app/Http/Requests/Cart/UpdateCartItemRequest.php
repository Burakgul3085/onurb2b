<?php

namespace App\Http\Requests\Cart;

use App\Actions\Cart\AddCartItem;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCartItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('shop') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'quantity' => ['required', 'integer', 'min:1', 'max:'.AddCartItem::MAX_QUANTITY],
        ];
    }
}
