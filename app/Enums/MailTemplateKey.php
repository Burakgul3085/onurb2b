<?php

namespace App\Enums;

enum MailTemplateKey: string
{
    case DealerApplication = 'dealer_application';
    case DealerApproved = 'dealer_approved';
    case DealerRejected = 'dealer_rejected';
    case AccountActivated = 'account_activated';
    case OrderPlaced = 'order_placed';
    case OrderApproved = 'order_approved';
    case OrderPreparing = 'order_preparing';
    case OrderOutForDelivery = 'order_out_for_delivery';
    case OrderDelivered = 'order_delivered';
    case CollectionRecorded = 'collection_recorded';
    case DueDateApproaching = 'due_date_approaching';
    case NewMessage = 'new_message';

    public function label(): string
    {
        return match ($this) {
            self::DealerApplication => 'Bayi başvurusu',
            self::DealerApproved => 'Bayi onayı',
            self::DealerRejected => 'Bayi ret',
            self::AccountActivated => 'Hesap aktivasyonu',
            self::OrderPlaced => 'Sipariş alındı',
            self::OrderApproved => 'Sipariş onaylandı',
            self::OrderPreparing => 'Hazırlanıyor',
            self::OrderOutForDelivery => 'Teslimata çıktı',
            self::OrderDelivered => 'Teslim edildi',
            self::CollectionRecorded => 'Tahsilat işlendi',
            self::DueDateApproaching => 'Vade yaklaşması',
            self::NewMessage => 'Yeni mesaj',
        };
    }
}
