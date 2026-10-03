<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="x-apple-disable-message-reformatting">
    <title>Reset your Havenedge Tourlink password</title>
    <style>
        @media only screen and (max-width: 620px) {
            .email-card { width: 100% !important; }
            .email-content { padding: 28px 22px !important; }
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
                            <p style="margin:8px 0 0;font-size:21px;font-weight:bold;">Password reset</p>
                        </td>
                    </tr>
                    <tr>
                        <td class="email-content" style="padding:34px 36px 28px;">
                            <p style="margin:0 0 16px;font-size:16px;line-height:1.6;">Hello {{ $name }},</p>
                            <p style="margin:0 0 18px;font-size:15px;line-height:1.7;">Someone requested a password reset for your Havenedge Tourlink account.</p>
                            <p style="margin:0 0 24px;font-size:14px;line-height:1.7;">Use the button below to choose a new password. This secure link expires in {{ $expiresInMinutes }} minutes and can only be used once.</p>
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 24px;">
                                <tr>
                                    <td align="center" style="border-radius:8px;background:#1f0052;">
                                        <a href="{{ $resetUrl }}" style="display:inline-block;padding:14px 24px;border:1px solid #1f0052;border-radius:8px;color:#ffffff;font-size:15px;font-weight:bold;text-decoration:none;">Reset Password</a>
                                    </td>
                                </tr>
                            </table>
                            <p style="margin:0 0 14px;font-size:13px;line-height:1.7;color:#65736b;">If the button does not work, copy and paste this link into your browser:<br><a href="{{ $resetUrl }}" style="color:#1f0052;word-break:break-all;">{{ $resetUrl }}</a></p>
                            <p style="margin:0;font-size:13px;line-height:1.7;color:#65736b;">If you did not request a password reset, ignore this email. Your password will remain unchanged. Contact Havenedge Tourlink support if you believe your account is at risk.</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:18px 32px;border-top:1px solid #e8ede9;background:#fafbfa;color:#78847d;font-size:12px;line-height:1.6;">
                            Havenedge Tourlink<br>
                            This is an automated security message. Never share your password or reset link.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
