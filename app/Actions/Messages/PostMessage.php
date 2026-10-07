<?php

namespace App\Actions\Messages;

use App\Actions\Mail\Notify;
use App\Enums\DealerApplicationStatus;
use App\Models\Dealer;
use App\Models\Message;
use App\Models\MessageThread;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PostMessage
{
    public function open(User $actor, int $dealerId, ?int $orderId, string $subject, string $body): MessageThread
    {
        return DB::transaction(function () use ($actor, $dealerId, $orderId, $subject, $body) {
            if ($actor->dealer_id !== null && (int) $actor->dealer_id !== $dealerId) {
                throw ValidationException::withMessages([
                    'dealer_id' => __('Choose your own company.'),
                ]);
            }

            $dealer = Dealer::query()->find($dealerId);

            if ($dealer === null || $dealer->application_status !== DealerApplicationStatus::Approved) {
                throw ValidationException::withMessages([
                    'dealer_id' => __('Choose an approved dealer.'),
                ]);
            }

            if ($orderId !== null) {
                $belongs = Order::query()->whereKey($orderId)->where('dealer_id', $dealer->id)->exists();

                if (! $belongs) {
                    throw ValidationException::withMessages([
                        'order_id' => __('This order belongs to another company.'),
                    ]);
                }
            }

            $thread = MessageThread::query()->create([
                'dealer_id' => $dealer->id,
                'order_id' => $orderId,
                'user_id' => $actor->id,
                'subject' => trim($subject),
            ]);

            $message = $thread->messages()->create([
                'user_id' => $actor->id,
                'body' => trim($body),
            ]);

            DB::afterCommit(fn () => app(Notify::class)->newMessage($message->id));

            return $thread;
        });
    }

    public function reply(User $actor, MessageThread $thread, string $body): Message
    {
        return DB::transaction(function () use ($actor, $thread, $body) {
            if ($actor->dealer_id !== null && (int) $actor->dealer_id !== (int) $thread->dealer_id) {
                throw ValidationException::withMessages([
                    'body' => __('Choose your own company.'),
                ]);
            }

            $message = $thread->messages()->create([
                'user_id' => $actor->id,
                'body' => trim($body),
            ]);

            DB::afterCommit(fn () => app(Notify::class)->newMessage($message->id));

            return $message;
        });
    }
}
