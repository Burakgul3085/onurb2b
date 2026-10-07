<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1c1917; }
        h1 { font-size: 16px; margin: 0; }
        p { margin: 4px 0 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border-bottom: 1px solid #d6d3d1; padding: 4px 6px; text-align: left; }
        th { font-size: 9px; }
        .money { text-align: right; }
        .note { margin-top: 12px; font-size: 9px; }
    </style>
</head>
<body>
    <h1>{{ $company }} — {{ $title }}</h1>
    <p>{{ $period }}</p>
    <table>
        <thead>
            <tr>
                @foreach ($result->columns as $column)
                    <th @class(['money' => in_array($column['key'], $result->moneyKeys, true)])>{{ $column['label'] }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ($result->rows as $row)
                <tr>
                    @foreach ($result->columns as $column)
                        <td @class(['money' => in_array($column['key'], $result->moneyKeys, true)])>{{ $row[$column['key']] ?? '' }}</td>
                    @endforeach
                </tr>
            @endforeach
            @if ($result->totals !== [])
                <tr>
                    @foreach ($result->columns as $index => $column)
                        <td @class(['money' => in_array($column['key'], $result->moneyKeys, true)])>
                            @if ($index === 0 && ! array_key_exists($column['key'], $result->totals))
                                Toplam
                            @else
                                {{ $result->totals[$column['key']] ?? '' }}
                            @endif
                        </td>
                    @endforeach
                </tr>
            @endif
        </tbody>
    </table>
    <p class="note">Operasyonel rapordur. Resmi belge yerine geçmez.</p>
</body>
</html>
