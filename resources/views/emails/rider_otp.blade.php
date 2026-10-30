<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lumora Logistics - Verification Code</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f7f3f0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #2D1522;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #f7f3f0; padding: 30px 15px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" style="max-width: 520px; background: #ffffff; border-radius: 16px; border: 1px solid #eaded6; overflow: hidden; box-shadow: 0 4px 20px rgba(45, 21, 34, 0.06);" cellspacing="0" cellpadding="0">
                    <!-- Header -->
                    <tr>
                        <td style="background-color: #2D1522; padding: 26px 30px; text-align: center;">
                            <h1 style="margin: 0; color: #FAF7F2; font-size: 22px; font-weight: 800; letter-spacing: 1px;">
                                LUMORA LOGISTICS
                            </h1>
                            <p style="margin: 4px 0 0 0; color: #E8A598; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.8px;">
                                Courier Rider Verification
                            </p>
                        </td>
                    </tr>

                    <!-- Body Content -->
                    <tr>
                        <td style="padding: 32px 30px 24px 30px;">
                            <p style="margin: 0 0 16px 0; font-size: 15px; line-height: 1.5; color: #2D1522;">
                                Hello <strong>{{ $name ?? 'Rider' }}</strong>,
                            </p>
                            <p style="margin: 0 0 24px 0; font-size: 14px; line-height: 1.6; color: #6E5D57;">
                                Thank you for applying to be a Courier Rider with Lumora Logistics! To verify your email address and continue your registration, please enter the following One-Time Password (OTP) in your mobile app:
                            </p>

                            <!-- OTP Box -->
                            <div style="background-color: #FDF6F0; border: 2px dashed #E0533C; border-radius: 12px; padding: 20px; text-align: center; margin: 0 0 24px 0;">
                                <div style="font-size: 34px; font-weight: 800; letter-spacing: 8px; color: #E0533C; font-family: 'Courier New', Courier, monospace;">
                                    {{ $otp }}
                                </div>
                                <div style="font-size: 12px; color: #9C8983; margin-top: 8px; font-weight: 600;">
                                    Valid for 10 minutes
                                </div>
                            </div>

                            <p style="margin: 0 0 8px 0; font-size: 13px; line-height: 1.5; color: #6E5D57;">
                                <strong>Security Notice:</strong> Never share this verification code with anyone. Lumora staff will never ask for your code.
                            </p>
                            <p style="margin: 0; font-size: 12px; line-height: 1.5; color: #9C8983;">
                                If you did not request this registration code, please disregard this email.
                            </p>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #FAF7F2; padding: 20px 30px; text-align: center; border-top: 1px solid #eaded6;">
                            <p style="margin: 0; font-size: 11px; color: #9C8983; line-height: 1.4;">
                                &copy; {{ date('Y') }} Lumora Logistics Hub · Last-Mile Manifest System<br>
                                Automated Dispatch System · Please do not reply to this email
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
