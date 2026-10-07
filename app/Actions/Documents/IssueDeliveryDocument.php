<?php

namespace App\Actions\Documents;

use App\Models\CompanySetting;
use App\Models\Delivery;
use App\Models\DeliveryDocument;
use Illuminate\Support\Facades\DB;

class IssueDeliveryDocument
{
    public function execute(Delivery $delivery): ?DeliveryDocument
    {
        return DB::transaction(function () use ($delivery) {
            $existing = DeliveryDocument::query()->where('delivery_id', $delivery->id)->first();

            if ($existing !== null) {
                return $existing;
            }

            $delivery->loadMissing('lines.orderLine', 'order', 'dealer', 'driver');
            $lines = $delivery->lines->filter(fn ($line) => $line->delivered_pieces > 0)->values();

            if ($lines->isEmpty()) {
                return null;
            }

            $company = CompanySetting::current();
            $dealer = $delivery->dealer;
            $document = DeliveryDocument::query()->create([
                'number' => DeliveryDocument::nextNumber(),
                'delivery_id' => $delivery->id,
                'order_id' => $delivery->order_id,
                'dealer_id' => $delivery->dealer_id,
                'issued_on' => ($delivery->finished_at ?? now())->toDateString(),
                'company_legal_name' => $company->legal_name,
                'company_tax_number' => $company->tax_number,
                'company_tax_office' => $company->tax_office,
                'company_address' => $company->address,
                'company_footnote' => $company->footnote,
                'dealer_name' => $dealer->company_name,
                'dealer_tax_number' => $dealer->tax_number,
                'dealer_tax_office' => $dealer->tax_office,
                'order_number' => $delivery->order->number,
                'recipient_name' => $delivery->recipient_name,
                'driver_name' => $delivery->driver->name,
                'province' => $delivery->province,
                'district' => $delivery->district,
                'delivery_address' => $delivery->delivery_address,
            ]);

            foreach ($lines as $line) {
                $document->lines()->create([
                    'sku' => $line->orderLine->sku,
                    'product_name' => $line->orderLine->product_name,
                    'unit_name' => $line->orderLine->unit_name,
                    'pieces' => $line->delivered_pieces,
                ]);
            }

            return $document->load('lines');
        });
    }
}
