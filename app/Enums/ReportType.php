<?php

namespace App\Enums;

enum ReportType: string
{
    case Sales = 'sales';
    case Orders = 'orders';
    case Stock = 'stock';
    case StockMovements = 'stock_movements';
    case Dealers = 'dealers';
    case DealerSales = 'dealer_sales';
    case Ledger = 'ledger';
    case Collections = 'collections';
    case Deliveries = 'deliveries';
    case ProductSales = 'product_sales';
    case CategorySales = 'category_sales';
    case BrandSales = 'brand_sales';

    public function label(): string
    {
        return match ($this) {
            self::Sales => 'Satış',
            self::Orders => 'Sipariş',
            self::Stock => 'Stok',
            self::StockMovements => 'Stok hareket',
            self::Dealers => 'Bayi',
            self::DealerSales => 'Bayi satış',
            self::Ledger => 'Cari',
            self::Collections => 'Tahsilat',
            self::Deliveries => 'Teslimat',
            self::ProductSales => 'Ürün satış',
            self::CategorySales => 'Kategori satış',
            self::BrandSales => 'Marka satış',
        };
    }

    public function hint(): string
    {
        return match ($this) {
            self::Sales => 'Teslimatta işlenen satış kayıtları.',
            self::Orders => 'Siparişin kilitlenmiş ödenecek tutarı.',
            self::Stock => 'Anlık stok. Tarih filtresi uygulanmaz.',
            self::StockMovements => 'Depo hareketleri.',
            self::Dealers => 'Bakiye güncel caridir.',
            self::DealerSales => 'Dönemdeki satış borcu ve iade alacağı.',
            self::Ledger => 'Cari hareketler.',
            self::Collections => 'Tahsilat alacakları.',
            self::Deliveries => 'Eskişehir teslimatları.',
            self::ProductSales, self::CategorySales, self::BrandSales => 'Teslim edilip iade edilmemiş adetler, kilitli fiyat ve belge iskontosuyla.',
        };
    }
}
