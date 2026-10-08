<?php

namespace App\Http\Requests\Dealers;

use App\Models\Dealer;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDealerNotificationEmailRequest extends FormRequest
{
    public function authorize(): bool
    {
        $dealer = $this->route('dealer');

        return $dealer instanceof Dealer
            && $this->user()?->dealer_id !== null
            && (int) $this->user()->dealer_id === (int) $dealer->id;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $dealer = $this->route('dealer');
        $dealerId = $dealer instanceof Dealer ? $dealer->id : null;

        return [
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('dealers', 'email')->ignore($dealerId),
                Rule::unique('users', 'email')->ignore($this->user() instanceof User ? $this->user()->id : null),
            ],
        ];
    }
}
