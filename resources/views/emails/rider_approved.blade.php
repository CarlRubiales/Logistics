<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application Approved - Lumora Logistics</title>
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
                            <p style="margin: 4px 0 0 0; color: #74C69D; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px;">
                                ✓ Application Approved
                            </p>
                        </td>
                    </tr>

                    <!-- Body Content -->
                    <tr>
                        <td style="padding: 32px 30px 24px 30px;">
                            <div style="text-align: center; margin-bottom: 20px;">
                                <div style="display: inline-block; width: 56px; height: 56px; line-height: 56px; border-radius: 50%; background: #D8F3DC; color: #1E6B37; font-size: 28px; font-weight: bold;">
                                    ✓
                                </div>
                            </div>

                            <h2 style="margin: 0 0 12px 0; font-size: 20px; font-weight: 800; color: #2D1522; text-align: center;">
                                Welcome to the Team, {{ $name ?? 'Rider' }}!
                            </h2>

                            <p style="margin: 0 0 20px 0; font-size: 14px; line-height: 1.6; color: #6E5D57; text-align: center;">
                                Great news! Your courier application has been reviewed and <strong style="color: #1E6B37;">approved</strong> by the operations team at <strong>{{ $hubName ?? 'your local Hub' }}</strong>.
                            </p>

                            <!-- Hub & Account Summary Card -->
                            <div style="background-color: #FBF7F4; border: 1px solid #EBDDD8; border-radius: 12px; padding: 18px 20px; margin: 0 0 24px 0;">
                                <div style="font-size: 12px; font-weight: 700; color: #2D1522; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 12px; border-bottom: 1px solid #EBDDD8; padding-bottom: 6px;">
                                    Courier Assignment Details
                                </div>
                                <table width="100%" cellspacing="0" cellpadding="4" style="font-size: 13px; color: #4A3A35;">
                                    <tr>
                                        <td width="38%" style="color: #8A7B7E; font-weight: 600;">Registered Email:</td>
                                        <td style="font-weight: 700; color: #2D1522;">{{ $email }}</td>
                                    </tr>
                                    <tr>
                                        <td style="color: #8A7B7E; font-weight: 600;">Assigned Hub:</td>
                                        <td style="font-weight: 700; color: #2D1522;">{{ $hubName ?? 'Regional Hub' }}</td>
                                    </tr>
                                    @if(!empty($branch))
                                    <tr>
                                        <td style="color: #8A7B7E; font-weight: 600;">Branch Territory:</td>
                                        <td style="font-weight: 700; color: #2D1522;">{{ $branch }}</td>
                                    </tr>
                                    @endif
                                    @if(!empty($vehicle))
                                    <tr>
                                        <td style="color: #8A7B7E; font-weight: 600;">Registered Vehicle:</td>
                                        <td style="font-weight: 700; color: #2D1522;">{{ $vehicle }}</td>
                                    </tr>
                                    @endif
                                    <tr>
                                        <td style="color: #8A7B7E; font-weight: 600;">Account Status:</td>
                                        <td style="font-weight: 800; color: #1E6B37;">Active & Ready for Dispatch</td>
                                    </tr>
                                </table>
                            </div>

                            <p style="margin: 0 0 16px 0; font-size: 14px; line-height: 1.6; color: #6E5D57;">
                                <strong>What to do next:</strong>
                            </p>
                            <ol style="margin: 0 0 24px 0; padding-left: 20px; font-size: 13.5px; line-height: 1.6; color: #6E5D57;">
                                <li style="margin-bottom: 6px;">Open the <strong>LMR GO Courier</strong> mobile app on your phone.</li>
                                <li style="margin-bottom: 6px;">Sign in using your email (<strong>{{ $email }}</strong>) and the password you created during registration.</li>
                                <li style="margin-bottom: 6px;">Toggle your status to <strong>Online</strong> when you are ready to receive delivery dispatches.</li>
                                <li>Report to <strong>{{ $hubName ?? 'your hub' }}</strong> for any parcel collection schedules.</li>
                            </ol>

                            <div style="background-color: #E8F5E9; border-left: 4px solid #2E7D32; padding: 12px 16px; border-radius: 6px; margin: 0 0 16px 0;">
                                <p style="margin: 0; font-size: 12.5px; color: #1B5E20; line-height: 1.5;">
                                    <strong>Drive Safely!</strong> Always wear your approved helmet and safety gear during delivery routes.
                                </p>
                            </div>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #FAF7F2; padding: 20px 30px; text-align: center; border-top: 1px solid #eaded6;">
                            <p style="margin: 0; font-size: 11px; color: #9C8983; line-height: 1.4;">
                                &copy; {{ date('Y') }} Lumora Logistics Hub · Automated Courier Dispatch System<br>
                                If you have any questions, please contact your hub manager or dispatch coordinator.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
