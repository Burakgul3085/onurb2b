<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanySetting extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'legal_name',
        'tax_number',
        'tax_office',
        'address',
        'logo_path',
        'footnote',
    ];

    public static function current(): self
    {
        return self::query()->firstOrFail();
    }
}
