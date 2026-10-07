<?php

namespace App\Http\Requests\Dealers;

use Illuminate\Support\Str;

trait NormalizesDealerFields
{
    protected function prepareForValidation(): void
    {
        if ($this->has('email')) {
            $this->merge([
                'email' => Str::lower(trim((string) $this->input('email'))),
            ]);
        }
    }
}
