<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1c1917; }
        h1 { font-size: 18px; margin: 0 0 4px; }
        h2 { font-size: 13px; margin: 16px 0 6px; }
        p { margin: 0 0 4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #d6d3d1; padding: 6px 8px; text-align: left; }
        th { background: #f5f5f4; }
        .muted { color: #57534e; }
        .foot { margin-top: 28px; font-size: 11px; }
        .disclaimer { margin-top: 8px; font-weight: bold; }
    </style>
</head>
<body>
    @if ($logo)
        <img src="{{ $logo }}" alt="" style="max-height: 56px; margin-bottom: 8px;">
    @endif
    <h1>{{ $document->company_legal_name }}</h1>
    <p>{{ $document->company_address }}</p>
    @if ($document->company_tax_office || $document->company_tax_number)
        <p class="muted">{{ $document->company_tax_office }} {{ $document->company_tax_number }}</p>
    @endif

    <h2>Teslim / sevk belgesi {{ $document->number }}</h2>
    <p>Tarih: {{ $document->issued_on->format('d.m.Y') }}</p>
    <p>Sipariş no: {{ $document->order_number }}</p>

    <h2>Bayi</h2>
    <p>{{ $document->dealer_name }}</p>
    @if ($document->dealer_tax_office || $document->dealer_tax_number)
        <p class="muted">{{ $document->dealer_tax_office }} {{ $document->dealer_tax_number }}</p>
    @endif
    <p>{{ $document->delivery_address }}</p>
    <p>{{ $document->province }} / {{ $document->district->label() }}</p>
    <p>Teslim alan: {{ $document->recipient_name }}</p>
    <p>Teslim eden: {{ $document->driver_name }}</p>

    <table>
        <thead>
            <tr>
                <th>Ürün</th>
                <th>Miktar</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($document->lines as $line)
                <tr>
                    <td>{{ $line->sku }} — {{ $line->product_name }}</td>
                    <td>{{ $line->pieces }} {{ $line->unit_name }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="foot">
        <p>{{ $document->company_footnote }}</p>
        @if (! str_contains($document->company_footnote, $disclaimer))
            <p class="disclaimer">{{ $disclaimer }}</p>
        @endif
    </div>
</body>
</html>
