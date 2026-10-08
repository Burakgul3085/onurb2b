<?php

namespace App\Actions\Mail;

use App\Enums\MailTemplateKey;
use App\Models\CompanySetting;
use App\Models\Dealer;
use App\Models\LedgerEntry;
use App\Models\Message;
use App\Models\Order;
use App\Models\User;
use App\Support\Money\Money;

class Notify
{
    public function __construct(private QueueTemplatedMail $mail) {}

    public function dealerApplication(int $dealerId): void
    {
        $dealer = Dealer::query()->find($dealerId);

        if ($dealer === null) {
            return;
        }

        $this->mail->execute(MailTemplateKey::DealerApplication, $dealer->email, $this->dealerFields($dealer));
        $this->office(MailTemplateKey::DealerApplicationOffice, [
            'contact' => CompanySetting::current()->legal_name,
            'company' => $dealer->company_name,
            'email' => $dealer->email,
            'phone' => $dealer->phone,
        ], $dealer->email);
    }

    public function dealerApproved(int $dealerId): void
    {
        $dealer = Dealer::query()->find($dealerId);

        if ($dealer === null) {
            return;
        }

        $this->mail->execute(MailTemplateKey::DealerApproved, $dealer->email, $this->dealerFields($dealer));
    }

    public function dealerRejected(int $dealerId): void
    {
        $dealer = Dealer::query()->find($dealerId);

        if ($dealer === null) {
            return;
        }

        $this->mail->execute(MailTemplateKey::DealerRejected, $dealer->email, [
            ...$this->dealerFields($dealer),
            'reason' => (string) $dealer->rejection_reason,
        ]);
    }

    public function accountActivated(int $userId): void
    {
        $user = User::query()->with('dealer')->find($userId);

        if ($user === null) {
            return;
        }

        $this->mail->execute(MailTemplateKey::AccountActivated, $user->email, [
            'contact' => $user->name,
            'company' => $user->dealer?->company_name ?? '',
        ]);
    }

    public function orderPlaced(int $orderId): void
    {
        $this->orderMail(MailTemplateKey::OrderPlaced, $orderId);

        $order = Order::query()->with(['user', 'dealer'])->find($orderId);

        if ($order === null) {
            return;
        }

        $this->office(MailTemplateKey::OrderPlacedOffice, [
            'contact' => CompanySetting::current()->legal_name,
            'company' => $order->dealer->company_name,
            'order_number' => $order->number,
        ], $order->user?->email);
    }

    public function orderApproved(int $orderId): void
    {
        $this->orderMail(MailTemplateKey::OrderApproved, $orderId);
    }

    public function orderPreparing(int $orderId): void
    {
        $this->orderMail(MailTemplateKey::OrderPreparing, $orderId);
    }

    public function orderOutForDelivery(int $orderId): void
    {
        $this->orderMail(MailTemplateKey::OrderOutForDelivery, $orderId);
    }

    public function orderDelivered(int $orderId): void
    {
        $this->orderMail(MailTemplateKey::OrderDelivered, $orderId);
    }

    public function collectionRecorded(int $dealerId, string $amount): void
    {
        $dealer = Dealer::query()->find($dealerId);

        if ($dealer === null) {
            return;
        }

        $this->mail->execute(MailTemplateKey::CollectionRecorded, $dealer->email, [
            ...$this->dealerFields($dealer),
            'amount' => Money::format($amount).' ₺',
        ]);
    }

    public function newMessage(int $messageId): void
    {
        $message = Message::query()->with(['user', 'thread.dealer', 'thread.order'])->find($messageId);

        if ($message === null || $message->user === null || $message->thread === null) {
            return;
        }

        $thread = $message->thread;

        foreach ($this->recipients($message->user, $thread->dealer) as $recipient) {
            $this->mail->execute(MailTemplateKey::NewMessage, $recipient['email'], [
                'contact' => $recipient['name'],
                'sender' => $message->user->name,
                'subject' => trim($thread->subject.' [OB-'.$thread->mail_token.']'),
                'body' => $message->body,
                'order_number' => $thread->order?->number ?? '',
                'company' => $thread->dealer->company_name,
            ]);
        }
    }

    public function dueDateApproaching(LedgerEntry $entry): bool
    {
        $entry->loadMissing('dealer');

        if ($entry->dealer === null) {
            return false;
        }

        $dueOn = $entry->due_on?->timezone(config('app.timezone'))->format('d.m.Y') ?? '';
        $sent = $this->mail->execute(MailTemplateKey::DueDateApproaching, $entry->dealer->email, [
            ...$this->dealerFields($entry->dealer),
            'due_on' => $dueOn,
        ]);

        $this->office(MailTemplateKey::DueDateOffice, [
            'contact' => CompanySetting::current()->legal_name,
            'company' => $entry->dealer->company_name,
            'order_number' => $entry->order?->number ?? '',
            'amount' => Money::format($entry->debit).' ₺',
            'due_on' => $dueOn,
        ], $entry->dealer->email);

        return $sent;
    }

    public function criticalStock(User $user, string $lines): bool
    {
        return $this->mail->execute(MailTemplateKey::CriticalStock, $user->email, [
            'contact' => $user->name,
            'lines' => $lines,
        ]);
    }

    public function dailyReport(User $user, string $date, string $sales, int $orders): bool
    {
        return $this->mail->execute(MailTemplateKey::DailyReport, $user->email, [
            'contact' => $user->name,
            'date' => $date,
            'sales' => $sales,
            'orders' => (string) $orders,
        ]);
    }

    private function orderMail(MailTemplateKey $key, int $orderId): void
    {
        $order = Order::query()->with(['user', 'dealer'])->find($orderId);

        if ($order?->user === null) {
            return;
        }

        $this->mail->execute($key, $order->user->email, [
            'contact' => $order->user->name,
            'company' => $order->dealer->company_name,
            'order_number' => $order->number,
        ]);
    }

    /**
     * @param  array<string, string>  $fields
     */
    private function office(MailTemplateKey $key, array $fields, ?string $except = null): void
    {
        $email = trim((string) CompanySetting::current()->notification_email);

        if ($email === '' || ($except !== null && strcasecmp($email, $except) === 0)) {
            return;
        }

        $this->mail->execute($key, $email, $fields);
    }

    /**
     * @return array{contact: string, company: string}
     */
    private function dealerFields(Dealer $dealer): array
    {
        return [
            'contact' => $dealer->contact_name,
            'company' => $dealer->company_name,
        ];
    }

    /**
     * @return list<array{name: string, email: string}>
     */
    private function recipients(User $sender, Dealer $dealer): array
    {
        if ($sender->dealer_id !== null) {
            $email = trim((string) CompanySetting::current()->notification_email);

            if ($email === '' || strcasecmp($email, $sender->email) === 0) {
                return [];
            }

            return [['name' => CompanySetting::current()->legal_name, 'email' => $email]];
        }

        $users = User::query()
            ->where('dealer_id', $dealer->id)
            ->where('is_active', true)
            ->whereKeyNot($sender->id)
            ->get()
            ->map(fn (User $user) => ['name' => $user->name, 'email' => $user->email])
            ->filter(fn (array $user) => $user['email'] !== $sender->email)
            ->values()
            ->all();

        if ($users !== []) {
            return $users;
        }

        if ($dealer->email === $sender->email) {
            return [];
        }

        return [['name' => $dealer->contact_name, 'email' => $dealer->email]];
    }
}
