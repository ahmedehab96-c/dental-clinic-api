@php
    $rtl = app()->getLocale() === 'ar';
    $dir = $rtl ? 'rtl' : 'ltr';
    $align = $rtl ? 'right' : 'left';
    $font = "'Segoe UI', Tahoma, Arial, 'Helvetica Neue', sans-serif";
    // Unicode isolates keep Latin values like "+966…" in their own direction inside Arabic text.
    $isolate = fn ($value) => "\u{2068}{$value}\u{2069}";
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ $dir }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title>{{ $subject }}</title>
</head>
<body style="margin:0; padding:0; background-color:#f1f5f9; font-family:{{ $font }};">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f1f5f9;">
        <tr>
            <td align="center" style="padding:32px 12px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" dir="{{ $dir }}" style="max-width:560px; background-color:#ffffff; border-radius:20px; overflow:hidden; border:1px solid #e2e8f0;">
                    {{-- Header --}}
                    <tr>
                        <td style="background-color:#087ea4; padding:24px 28px; text-align:{{ $align }};">
                            <span style="display:inline-block; width:36px; height:36px; line-height:36px; border-radius:10px; background-color:#ffffff; color:#087ea4; font-weight:700; font-size:18px; text-align:center; vertical-align:middle;">R</span>
                            <span style="font-size:18px; font-weight:700; color:#ffffff; vertical-align:middle; padding-{{ $rtl ? 'right' : 'left' }}:10px;">{{ __('appointment_mail.brand') }}</span>
                        </td>
                    </tr>

                    {{-- Body --}}
                    <tr>
                        <td style="padding:28px; text-align:{{ $align }}; color:#0f172a;">
                            <p style="margin:0 0 12px; font-size:16px; font-weight:600;">{{ $greeting }}</p>
                            <p style="margin:0 0 22px; font-size:15px; line-height:1.7; color:#334155;">{{ $intro }}</p>

                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e2e8f0; border-radius:14px; background-color:#f8fafc;">
                                @foreach ($details as $label => $value)
                                    <tr>
                                        <td style="padding:11px 16px; font-size:13px; color:#64748b; width:38%; text-align:{{ $align }}; {{ $loop->last ? '' : 'border-bottom:1px solid #e2e8f0;' }}">{{ $label }}</td>
                                        <td style="padding:11px 16px; font-size:14px; font-weight:600; color:#0f172a; text-align:{{ $align }}; {{ $loop->last ? '' : 'border-bottom:1px solid #e2e8f0;' }}"><bdi>{{ $value }}</bdi></td>
                                    </tr>
                                @endforeach
                            </table>

                            @if ($actionUrl)
                                <table role="presentation" cellpadding="0" cellspacing="0" style="margin:26px 0 4px;" align="{{ $align }}">
                                    <tr>
                                        <td style="border-radius:999px; background-color:#087ea4;">
                                            <a href="{{ $actionUrl }}" style="display:inline-block; padding:12px 26px; font-size:14px; font-weight:600; color:#ffffff; text-decoration:none; border-radius:999px;">{{ $actionText }}</a>
                                        </td>
                                    </tr>
                                </table>
                            @endif
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="padding:20px 28px 26px; border-top:1px solid #e2e8f0; text-align:{{ $align }}; font-size:12px; line-height:1.7; color:#64748b;">
                            @if ($clinic['phone'] || $clinic['email'])
                                <p style="margin:0 0 6px;">{{ __('appointment_mail.contact', ['phone' => $isolate($clinic['phone'] ?? '—'), 'email' => $isolate($clinic['email'] ?? '—')]) }}</p>
                            @endif
                            @if ($clinic['address'])
                                <p style="margin:0 0 6px;">{{ $clinic['address'] }}</p>
                            @endif
                            <p style="margin:0; color:#94a3b8;">{{ __('appointment_mail.footer_reason', ['brand' => __('appointment_mail.brand')]) }}</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
