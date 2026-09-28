<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $statusLabel }}</title>
</head>
<body style="margin:0;padding:0;background:#f3f4f6;font-family:Arial,Helvetica,sans-serif;color:#1f2937;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f3f4f6;padding:24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:560px;background:#ffffff;border:1px solid #e5e7eb;border-radius:12px;overflow:hidden;">
                    @include('mail.partials.school-logo')
                    <tr>
                        <td style="background:#296374;color:#ffffff;padding:20px 24px;">
                            <p style="margin:0;font-size:12px;letter-spacing:0.12em;text-transform:uppercase;opacity:0.8;">Agusan National High School</p>
                            <h1 style="margin:8px 0 0;font-size:20px;">{{ $statusLabel }}</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:24px;">
                            <p style="margin:0 0 12px;font-size:15px;">Hello {{ $studentName }},</p>
                            @if ($isEnrolled)
                                <p style="margin:0 0 12px;font-size:14px;line-height:1.6;">
                                    Your required enrollment documents have been verified. You are now enrolled
                                    @if ($schoolYear)
                                        for school year {{ $schoolYear }}
                                    @endif.
                                </p>
                            @else
                                <p style="margin:0 0 12px;font-size:14px;line-height:1.6;">
                                    Your enrollment form has been received. You are temporarily enrolled
                                    @if ($schoolYear)
                                        for school year {{ $schoolYear }}
                                    @endif
                                    while some required documents are still pending. Please sign in and upload your Birth Certificate and Form 137 / SF9.
                                </p>
                            @endif
                            <p style="margin:0 0 8px;font-size:14px;font-weight:bold;">Login instructions</p>
                            <p style="margin:0 0 12px;font-size:14px;line-height:1.6;">
                                Use these instructions to sign in, then change your password immediately after your first login.
                            </p>
                            <p style="margin:0 0 8px;font-size:13px;line-height:1.6;">
                                Username: use your LRN.
                            </p>
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin:0 0 16px;background:#f3f4f6;border:1px solid #e5e7eb;border-radius:8px;">
                                <tr>
                                    <td style="padding:14px;font-size:13px;line-height:1.7;">
                                        <strong>Sample student (for illustration only)</strong><br>
                                        First name: Jane<br>
                                        Last name: Doe<br>
                                        LRN: <code>123456789012</code><br>
                                        Enrollment year: {{ $enrollmentYear }}
                                    </td>
                                </tr>
                            </table>
                            <p style="margin:0 0 16px;font-size:13px;line-height:1.6;">
                                Password: use the first 2 letters of your first name + the first 2 letters of your last name + {{ $enrollmentYear }} + anhs.
                                Use lowercase letters and no spaces.
                            </p>
                            <p style="margin:0 0 16px;font-size:13px;line-height:1.6;">
                                For Jane Doe: <strong>ja</strong> (Jane) + <strong>do</strong> (Doe) + <strong>{{ $enrollmentYear }}</strong> + <strong>anhs</strong>.<br>
                                Example username: <code>123456789012</code><br>
                                Example password: <code>{{ $passwordExample }}</code>
                            </p>
                            <p style="margin:0 0 16px;font-size:13px;line-height:1.6;">
                                The details above are examples only. Use your own registered first name, last name, and LRN to sign in.
                                If you have already changed your password, use your current password instead.
                            </p>
                            <p style="margin:0;">
                                <a href="{{ $loginUrl }}" style="display:inline-block;background:#296374;color:#ffffff;text-decoration:none;font-size:13px;font-weight:bold;padding:10px 16px;border-radius:8px;">
                                    Go to Login
                                </a>
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
