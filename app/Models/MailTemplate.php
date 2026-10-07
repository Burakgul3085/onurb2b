<?php

namespace App\Models;

use App\Enums\MailTemplateKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MailTemplate extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'key',
        'name',
        'subject',
        'body',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'key' => MailTemplateKey::class,
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<MailLog, $this>
     */
    public function logs(): HasMany
    {
        return $this->hasMany(MailLog::class);
    }
}
