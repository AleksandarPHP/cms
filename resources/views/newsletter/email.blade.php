<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', $locale) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $headline !== '' ? $headline : 'Newsletter' }}</title>
    @if($googleFont)
    <link rel="stylesheet" href="{{ $googleFont }}">
    @endif
    <link rel="stylesheet" href="{{ asset('assets/vendor/fontawesome-free-5.13.1/css/all.min.css') }}">
</head>
<body style="margin:0;padding:0;background:{{ $palette['page'] }};color:{{ $palette['text'] }};font-family:{{ $fontFamily }};">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:{{ $palette['page'] }};">
        <tr>
            <td align="center" style="padding:18px 12px 32px;">
                @if($browserUrl)
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:640px;">
                    <tr>
                        <td align="right" style="padding:0 4px 10px;font-size:12px;">
                            <a href="{{ $browserUrl }}" style="color:{{ $palette['muted'] }};text-decoration:underline;">{{ $labels['view'] }}</a>
                        </td>
                    </tr>
                </table>
                @endif

                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:640px;background:#ffffff;">
                    <tr>
                        <td style="background:{{ $palette['accent'] }};padding:16px 28px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td valign="middle" style="color:{{ $palette['on_accent'] }};font-size:13px;line-height:1.35;">
                                        @if($phone !== '')
                                        <div style="font-size:11px;letter-spacing:0.08em;font-weight:700;">{{ $labels['phone'] }}</div>
                                        <div style="font-size:16px;font-weight:700;">{{ $phone }}</div>
                                        @endif
                                    </td>
                                    <td valign="middle" style="color:{{ $palette['on_accent'] }};font-size:13px;line-height:1.35;padding-left:16px;">
                                        @if($email !== '')
                                        <div style="font-size:11px;letter-spacing:0.08em;font-weight:700;">{{ $labels['email'] }}</div>
                                        <div style="font-size:16px;font-weight:700;">{{ $email }}</div>
                                        @endif
                                    </td>
                                    <td valign="middle" align="right" style="white-space:nowrap;">
                                        @if($instagram)
                                        <a href="{{ $instagram }}" target="_blank" rel="noopener" style="display:inline-block;width:34px;height:34px;margin-left:8px;border-radius:50%;background:#ffffff;color:{{ $palette['accent'] }};text-align:center;line-height:34px;text-decoration:none;">
                                            <i class="fab fa-instagram"></i>
                                        </a>
                                        @endif
                                        @if($facebook)
                                        <a href="{{ $facebook }}" target="_blank" rel="noopener" style="display:inline-block;width:34px;height:34px;margin-left:8px;border-radius:50%;background:#ffffff;color:{{ $palette['accent'] }};text-align:center;line-height:34px;text-decoration:none;">
                                            <i class="fab fa-facebook-f"></i>
                                        </a>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="background:{{ $palette['dark'] }};padding:22px 28px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td valign="middle" style="color:{{ $palette['on_dark'] }};">
                                        <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                                            <tr>
                                                <td style="background:#ffffff;padding:8px 12px;">
                                                    @if($logo)
                                                    <img src="{{ $logo }}" alt="{{ $companyName }}" style="display:block;max-width:210px;max-height:52px;height:auto;border:0;">
                                                    @else
                                                    <div style="font-size:22px;font-weight:700;color:#1a1a1a;">{{ $companyName }}</div>
                                                    @endif
                                                </td>
                                            </tr>
                                        </table>
                                        @if($address !== '')
                                        <div style="margin-top:8px;font-size:12px;opacity:0.9;">{{ $address }}</div>
                                        @endif
                                    </td>
                                    <td valign="middle" align="right" style="padding-left:16px;">
                                        <div style="color:{{ $palette['mark'] }};font-size:22px;font-weight:800;letter-spacing:0.08em;">NEWSLETTER</div>
                                        @if($headline !== '')
                                        <div style="margin-top:4px;color:{{ $palette['on_dark'] }};font-size:14px;font-weight:700;">{{ $headline }}</div>
                                        @endif
                                        <div style="margin-top:4px;color:{{ $palette['on_dark'] }};font-size:13px;letter-spacing:0.04em;">{{ $issue }}</div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    @if($intro !== '')
                    <tr>
                        <td style="padding:28px 32px 8px;font-size:16px;line-height:1.55;color:{{ $palette['text'] }};">
                            {!! nl2br(e($intro)) !!}
                        </td>
                    </tr>
                    @endif

                    @foreach($blogs as $blog)
                    @if($blog['image'])
                    <tr>
                        <td style="padding-top:18px;">
                            <a href="{{ $blog['url'] }}" target="_blank" rel="noopener">
                                <img src="{{ $blog['image'] }}" alt="{{ $blog['title'] }}" width="640" style="display:block;width:100%;max-width:640px;height:auto;border:0;">
                            </a>
                        </td>
                    </tr>
                    @endif
                    <tr>
                        <td style="padding:22px 32px 28px;">
                            @if($blog['date'] !== '')
                            <div style="color:{{ $palette['accent'] }};font-size:13px;font-weight:700;">{{ $blog['date'] }}</div>
                            @endif
                            <h2 style="margin:8px 0 10px;font-size:26px;line-height:1.2;letter-spacing:0.03em;text-transform:uppercase;color:#1a1a1a;">{{ $blog['title'] }}</h2>
                            @if($blog['subtitle'] !== '')
                            <p style="margin:0 0 18px;font-size:16px;line-height:1.5;color:#3d3d3d;">{{ $blog['subtitle'] }}</p>
                            @endif
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td valign="middle">
                                        <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                                            <tr>
                                                <td bgcolor="{{ $palette['accent'] }}" style="border-radius:2px;">
                                                    <a href="{{ $blog['url'] }}" target="_blank" rel="noopener" style="display:inline-block;padding:12px 26px;font-size:14px;font-weight:800;letter-spacing:0.06em;text-decoration:none;color:{{ $palette['on_accent'] }};">{{ $labels['more'] }}</a>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                    <td valign="middle" align="right">
                                        <a href="{{ $blog['facebook'] }}" target="_blank" rel="noopener" style="display:inline-block;width:34px;height:34px;margin-left:8px;border-radius:50%;background:#1877f2;color:#ffffff;text-align:center;line-height:34px;text-decoration:none;">
                                            <i class="fab fa-facebook-f"></i>
                                        </a>
                                        <a href="{{ $blog['mail'] }}" style="display:inline-block;width:34px;height:34px;margin-left:8px;border-radius:50%;background:#e74c3c;color:#ffffff;text-align:center;line-height:34px;text-decoration:none;">
                                            <i class="fas fa-envelope"></i>
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    @endforeach

                    <tr>
                        <td style="padding:8px 32px 28px;border-top:1px solid {{ $palette['line'] }};">
                            <p style="margin:18px 0 10px;">
                                <a href="{{ $privacyUrl }}" target="_blank" rel="noopener" style="color:{{ $palette['link'] }};font-weight:700;text-decoration:underline;">{{ $labels['privacy'] }} {{ $companyName }}</a>
                            </p>
                            <p style="margin:0 0 8px;color:{{ $palette['link'] }};font-size:16px;font-weight:700;">{{ $labels['known'] }}</p>
                            <p style="margin:0 0 18px;font-size:15px;line-height:1.5;color:#333333;">
                                {{ $labels['known_before'] }}
                                @if($subscribeUrl)
                                <a href="{{ $subscribeUrl }}" style="color:{{ $palette['link'] }};font-weight:700;">{{ $labels['subscribe'] }}</a>
                                @else
                                <strong style="color:{{ $palette['link'] }};">{{ $labels['subscribe'] }}</strong>
                                @endif
                                {{ str_replace(':company', $companyName, $labels['known_after']) }}
                                @if($facebook)
                                <a href="{{ $facebook }}" target="_blank" rel="noopener" style="color:{{ $palette['link'] }};font-weight:700;">{{ $labels['facebook'] }}</a>
                                @else
                                {{ $labels['facebook'] }}
                                @endif
                            </p>
                            @if($unsubscribeUrl)
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td bgcolor="{{ $palette['accent'] }}" style="border-radius:2px;">
                                        <a href="{{ $unsubscribeUrl }}" style="display:inline-block;padding:12px 22px;font-size:14px;font-weight:800;letter-spacing:0.04em;text-decoration:none;color:{{ $palette['on_accent'] }};">{{ $labels['unsubscribe'] }}</a>
                                    </td>
                                </tr>
                            </table>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td style="background:{{ $palette['dark'] }};padding:26px 28px;color:{{ $palette['on_dark'] }};">
                            @if($logo)
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td style="background:#ffffff;padding:8px 12px;">
                                        <img src="{{ $logo }}" alt="{{ $companyName }}" style="display:block;max-width:180px;max-height:46px;height:auto;border:0;">
                                    </td>
                                </tr>
                            </table>
                            @endif
                            @if($footerLine !== '')
                            <div style="margin-top:12px;font-size:13px;line-height:1.4;">{{ $footerLine }}</div>
                            @endif
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
