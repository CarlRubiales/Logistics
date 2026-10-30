<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application Status - Lumora Logistics</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f7f3f0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #2D1522;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #f7f3f0; padding: 30px 15px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" style="max-width: 540px; background: #ffffff; border-radius: 16px; border: 1px solid #eaded6; overflow: hidden; box-shadow: 0 4px 20px rgba(45, 21, 34, 0.06);" cellspacing="0" cellpadding="0">
                    <!-- Header -->
                    <tr>
                        <td style="background-color: #2D1522; padding: 26px 30px; text-align: center;">
                            <h1 style="margin: 0; color: #FAF7F2; font-size: 22px; font-weight: 800; letter-spacing: 1px;">
                                LUMORA LOGISTICS
                            </h1>
                            <p style="margin: 4px 0 0 0; color: #E8A598; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px;">
                                Application Review Notice
                            </p>
                        </td>
                    </tr>

                    <!-- Body Content -->
                    <tr>
                        <td style="padding: 32px 30px 24px 30px;">
                            <div style="text-align: center; margin-bottom: 20px;">
                                <div style="display: inline-block; width: 56px; height: 56px; line-height: 56px; border-radius: 50%; background: #FDE8E8; color: #C62828; font-size: 28px; font-weight: bold;">
                                    ✕
                                </div>
                            </div>

                            <h2 style="margin: 0 0 12px 0; font-size: 19px; font-weight: 800; color: #2D1522; text-align: center;">
                                Courier Application Update
                            </h2>

                            <p style="margin: 0 0 18px 0; font-size: 14px; line-height: 1.6; color: #6E5D57;">
                                Hello <strong>{{ $name ?? 'Applicant' }}</strong>,
                            </p>

                            <p style="margin: 0 0 20px 0; font-size: 14px; line-height: 1.6; color: #6E5D57;">
                                Thank you for your interest in partnering as a courier rider with <strong>{{ $hubName ?? 'Lumora Logistics' }}</strong>.
                            </p>

                            <p style="margin: 0 0 14px 0; font-size: 14px; line-height: 1.6; color: #6E5D57;">
                                After careful review of your application and uploaded documents, the hub operations team was unable to approve your account at this time due to the following reason:
                            </p>

                            <!-- Reason Box -->
                            <div style="background-color: #FFF5F5; border: 1.5px solid #F5C6CB; border-left: 5px solid #C62828; border-radius: 8px; padding: 16px 20px; margin: 0 0 24px 0;">
                                <div style="font-size: 11px; font-weight: 800; color: #721C24; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px;">
                                    Hub Decision & Reason
                                </div>
                                <div style="font-size: 14px; line-height: 1.5; color: #2D1522; font-weight: 600;">
                                    "{{ $reason ?? 'Requirements or document verification criteria were not fulfilled.' }}"
                                </div>
                            </div>

                            <!-- Next steps -->
                            <div style="background-color: #FBF7F4; border: 1px solid #EBDDD8; border-radius: 10px; padding: 16px 18px; margin: 0 0 20px 0;">
                                <div style="font-size: 12px; font-weight: 700; color: #2D1522; margin-bottom: 6px;">
                                    What can you do?
                                </div>
                                <p style="margin: 0; font-size: 13px; line-height: 1.5; color: #6E5D57;">
                                    If this was due to unclear document photos (e.g. blurry driver’s license or outdated OR/CR), you may contact your assigned hub coordinator at <strong>{{ $hubName ?? 'your hub' }}</strong> or re-submit clearer copies to be re-evaluated.
                                </p>
                            </div>

                            <p style="margin: 0; font-size: 12px; line-height: 1.5; color: #9C8983;">
                                If you believe this decision was made in error or have further questions, please feel free to reach out to our hub support staff.
                            </p>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #FAF7F2; padding: 20px 30px; text-align: center; border-top: 1px solid #eaded6;">
                            <p style="margin: 0; font-size: 11px; color: #9C8983; line-height: 1.4;">
                                &copy; {{ date('Y') }} Lumora Logistics Hub · Automated Courier Dispatch System<br>
                                Please do not reply directly to this automated email.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
