<?php

namespace App\Http\Requests\Messages;

use App\Models\MessageThread;
use App\Policies\MessageThreadPolicy;
use Illuminate\Foundation\Http\FormRequest;

class ReplyMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        $thread = $this->route('thread');

        return $thread instanceof MessageThread && ($this->user()?->can('reply', $thread) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:5000'],
        ];
    }

    protected function failedAuthorization(): void
    {
        $thread = $this->route('thread');
        $status = 403;

        if ($thread instanceof MessageThread && $this->user() !== null) {
            $status = app(MessageThreadPolicy::class)->deniedStatus($this->user(), $thread);
        }

        abort($status);
    }
}
