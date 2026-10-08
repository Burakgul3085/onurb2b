@php
    $actionLabel = 'Bağlantıyı aç';
    if (is_string($actionUrl ?? null) && str_contains($actionUrl, 'reset-password')) {
        $actionLabel = 'Parolayı belirle';
    } elseif (is_string($actionUrl ?? null) && str_contains($actionUrl, (string) parse_url((string) config('app.url'), PHP_URL_HOST))) {
        $actionLabel = 'Panele git';
    }
@endphp
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f0e7;margin:0;padding:0;">
    <tr>
        <td align="center" style="padding:28px 12px;">
            <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="width:100%;max-width:600px;">
                <tr>
                    <td style="background:#1c2834;border-radius:18px 18px 0 0;padding:22px 28px;">
                        <table role="presentation" cellpadding="0" cellspacing="0">
                            <tr>
                                <td width="36" height="36" align="center" valign="middle" style="width:36px;height:36px;background:#9a5b2f;border-radius:10px;color:#ffffff;font-family:Georgia,'Times New Roman',serif;font-size:14px;font-weight:700;">OB</td>
                                <td style="padding-left:12px;font-family:Arial,Helvetica,sans-serif;">
                                    <div style="color:#ffffff;font-size:16px;font-weight:700;letter-spacing:0.02em;">Onur B2B</div>
                                    <div style="color:#d5dde6;font-size:12px;margin-top:2px;">{{ $legalName }} · Eskişehir</div>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td style="background:#ffffff;padding:32px 28px 28px;font-family:Arial,Helvetica,sans-serif;color:#1c2834;">
                        <div style="color:#9a5b2f;font-size:11px;font-weight:700;letter-spacing:0.14em;text-transform:uppercase;">{{ $eyebrow }}</div>
                        <h1 style="margin:10px 0 0;font-size:22px;line-height:1.35;font-weight:700;color:#1c2834;">{{ $subjectLine }}</h1>
                        <div style="height:3px;width:48px;background:#9a5b2f;border-radius:99px;margin:18px 0 22px;"></div>
                        @foreach ($paragraphs as $paragraph)
                            <p style="margin:0 0 14px;font-size:15px;line-height:1.65;color:#1c2834;">{!! nl2br(e($paragraph)) !!}</p>
                        @endforeach
                        @if ($actionUrl)
                            <table role="presentation" cellpadding="0" cellspacing="0" style="margin:8px 0 6px;">
                                <tr>
                                    <td style="background:#1c2834;border-radius:10px;">
                                        <a href="{{ $actionUrl }}" style="display:inline-block;padding:12px 20px;color:#ffffff;font-family:Arial,Helvetica,sans-serif;font-size:14px;font-weight:700;text-decoration:none;">{{ $actionLabel }}</a>
                                    </td>
                                </tr>
                            </table>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td style="background:#f7f4ee;border-top:1px solid #e7e1d6;border-radius:0 0 18px 18px;padding:18px 28px;font-family:Arial,Helvetica,sans-serif;color:#5e6d7c;font-size:12px;line-height:1.6;">
                        <strong style="color:#1c2834;">{{ $legalName }}</strong><br>
                        {{ $address }}<br>
                        Bu ileti Onur B2B sipariş ve bayi sistemi tarafından gönderilmiştir.
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
