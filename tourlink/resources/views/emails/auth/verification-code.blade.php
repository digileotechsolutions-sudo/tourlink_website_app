<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="x-apple-disable-message-reformatting">
    <title>Your Havenedge Tourlink verification code</title>
    <style>
        @media only screen and (max-width: 620px) {
            .email-card { width: 100% !important; }
            .email-content { padding: 28px 22px !important; }
            .email-code { font-size: 30px !important; letter-spacing: 7px !important; }
        }
    </style>
</head>
<body style="margin:0;padding:0;background:#f3f5f4;font-family:Arial,Helvetica,sans-serif;color:#24352d;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:#f3f5f4;">
        <tr>
            <td align="center" style="padding:32px 12px;">
                <table role="presentation" class="email-card" width="600" cellspacing="0" cellpadding="0" border="0" style="width:600px;max-width:600px;background:#ffffff;border:1px solid #e3e9e5;border-radius:16px;overflow:hidden;">
                    <tr>
                        <td style="padding:24px 32px;background:#1f0052;color:#ffffff;">
                            <p style="margin:0;font-size:12px;font-weight:bold;letter-spacing:2px;text-transform:uppercase;">Havenedge Tourlink</p>
                            <p style="margin:8px 0 0;font-size:21px;font-weight:bold;">Secure account verification</p>
                        </td>
                    </tr>
                    <tr>
                        <td class="email-content" style="padding:34px 36px 28px;">
                            <p style="margin:0 0 16px;font-size:16px;line-height:1.6;">Hello {{ $name }},</p>
                            @if (!empty($registration))
                                <p style="margin:0 0 18px;font-size:15px;line-height:1.7;">Thank you for creating your Havenedge Tourlink account. Confirm your email address with this one-time verification code:</p>
                            @else
                                <p style="margin:0 0 18px;font-size:15px;line-height:1.7;">Use this one-time verification code to confirm your email address:</p>
                            @endif
                            <p style="margin:0 0 8px;font-size:14px;line-height:1.6;font-weight:bold;">Your Havenedge Tourlink verification code is: {{ $code }}</p>
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin:12px 0 22px;">
                                <tr>
                                    <td class="email-code" style="padding:15px 20px;border-radius:10px;background:#f5f1fb;color:#1f0052;font-size:34px;font-weight:bold;letter-spacing:10px;">{{ $code }}</td>
                                </tr>
                            </table>
                            <p style="margin:0 0 14px;font-size:14px;line-height:1.7;"><strong>This code expires in {{ $expiresInMinutes }} minutes.</strong> Enter it on the verification page to continue.</p>
                            <p style="margin:0;font-size:13px;line-height:1.7;color:#65736b;">For your security, never share this code with anyone. If you did not create this account or request a code, you can ignore this email.</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:18px 32px;border-top:1px solid #e8ede9;background:#fafbfa;color:#78847d;font-size:12px;line-height:1.6;">
                            Havenedge Tourlink<br>
                            This is an automated security message. Please do not reply with your verification code.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
