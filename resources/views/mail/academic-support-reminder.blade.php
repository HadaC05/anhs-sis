<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Academic support reminder</title>
</head>
<body style="margin:0;padding:0;background:#f3f4f6;font-family:Arial,Helvetica,sans-serif;color:#1f2937;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f3f4f6;padding:24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:560px;background:#ffffff;border:1px solid #e5e7eb;border-radius:12px;overflow:hidden;">
                    @include('mail.partials.school-logo')
                    <tr>
                        <td style="background:#296374;color:#ffffff;padding:20px 24px;">
                            <p style="margin:0;font-size:12px;letter-spacing:0.12em;text-transform:uppercase;">Agusan National High School</p>
                            <h1 style="margin:8px 0 0;font-size:20px;">Academic support reminder</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:24px;">
                            <p style="margin:0 0 12px;font-size:15px;">Hello {{ $studentName }},</p>
                            <p style="margin:0 0 12px;font-size:14px;line-height:1.6;">
                                Your recorded grades for {{ $periodLabel }}, SY {{ $schoolYear }}, show that you did not meet the passing grade of 75 in <strong>{{ $failedSubjectCount }} {{ $failedSubjectCount === 1 ? 'subject' : 'subjects' }}</strong>.
                            </p>
                            <p style="margin:0 0 12px;font-size:14px;line-height:1.6;">
                                Please contact your class adviser as soon as possible to discuss these grades, seek help, and receive instructions on your next steps. Your adviser can guide you on the support available and what you can do to improve your performance.
                            </p>
                            <p style="margin:0 0 20px;font-size:14px;line-height:1.6;">
                                You do not have to work through these challenges alone. Reaching out is an important step toward making progress.
                            </p>
                            <p style="margin:0;">
                                <a href="{{ $gradesUrl }}" style="display:inline-block;background:#296374;color:#ffffff;text-decoration:none;font-size:13px;font-weight:bold;padding:10px 16px;border-radius:8px;">View your grades</a>
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
