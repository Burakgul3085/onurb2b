<?php

namespace App\Actions\Audit;

use App\Enums\AuditAction;
use App\Models\AuditLog;
use App\Models\User;
use BackedEnum;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class RecordAudit
{
    /**
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     */
    public function write(
        AuditAction $action,
        ?Model $subject = null,
        ?string $number = null,
        array $before = [],
        array $after = [],
        ?User $actor = null,
    ): AuditLog {
        $actor ??= auth()->user();
        $before = $this->clean($before);
        $after = $this->clean($after);
        $agent = request()->userAgent();

        return AuditLog::query()->create([
            'user_id' => $actor instanceof User ? $actor->id : null,
            'action' => $action,
            'auditable_type' => $subject !== null ? $subject::class : null,
            'auditable_id' => $subject?->getKey(),
            'entity_number' => $number !== null && $number !== '' ? $number : null,
            'old_values' => $before === [] ? null : $before,
            'new_values' => $after === [] ? null : $after,
            'ip_address' => request()->ip(),
            'user_agent' => is_string($agent) && $agent !== '' ? Str::limit($agent, 1000, '') : null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    private function clean(array $values): array
    {
        $hidden = ['password', 'password_confirmation', 'current_password', 'remember_token', 'token'];
        $clean = [];

        foreach ($values as $key => $value) {
            if (in_array($key, $hidden, true)) {
                continue;
            }

            $clean[$key] = $this->scalar($value);
        }

        return $clean;
    }

    private function scalar(mixed $value): mixed
    {
        if ($value instanceof BackedEnum) {
            return $value->value;
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        if (is_bool($value) || is_int($value) || $value === null) {
            return $value;
        }

        if (is_array($value)) {
            return array_map(fn (mixed $item) => $this->scalar($item), $value);
        }

        return mb_substr((string) $value, 0, 500);
    }
}
