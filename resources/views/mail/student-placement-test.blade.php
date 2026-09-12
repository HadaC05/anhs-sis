<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Placement test recommended</title>
</head>
<body style="margin:0;padding:0;background:#f3f4f6;font-family:Arial,Helvetica,sans-serif;color:#1f2937;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f3f4f6;padding:24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:560px;background:#ffffff;border:1px solid #e5e7eb;border-radius:12px;overflow:hidden;">
                    <tr>
                        <td style="background:#296374;color:#ffffff;padding:20px 24px;">
                            <p style="margin:0;font-size:12px;letter-spacing:0.12em;text-transform:uppercase;opacity:0.8;">Agusan National High School</p>
                            <h1 style="margin:8px 0 0;font-size:20px;">Placement test recommended</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:24px;">
                            <p style="margin:0 0 12px;font-size:15px;">Hello {{ $studentName }},</p>
                            <p style="margin:0 0 16px;font-size:14px;line-height:1.6;">
                                The Guidance Office has recommended that you take a placement test as part of your enrollment
                                @if ($schoolYear)
                                    for school year {{ $schoolYear }}
                                @endif
                                @if ($gradeLabel)
                                    in {{ $gradeLabel }}
                                @endif.
                            </p>

                            <p style="margin:0 0 8px;font-size:14px;font-weight:bold;">Why this is needed</p>
                            @if ($summary)
                                <p style="margin:0 0 12px;font-size:14px;line-height:1.6;">
                                    {{ $summary }}.
                                    @if ($age)
                                        Your age at school year start is {{ $age }} years.
                                    @endif
                                    @if ($detail)
                                        {{ $detail }}
                                    @endif
                                    A placement test helps the school confirm the grade level that best matches your current skills.
                                </p>
                            @elseif ($expectedRange)
                                <p style="margin:0 0 12px;font-size:14px;line-height:1.6;">
                                    @if ($gradeLabel)
                                        Expected age for {{ $gradeLabel }} is {{ $expectedRange }} years at school year start.
                                    @else
                                        Expected age for this grade is {{ $expectedRange }} years at school year start.
                                    @endif
                                    @if ($age)
                                        Your age at school year start is {{ $age }} years.
                                    @endif
                                    A placement test helps the school confirm the grade level that best matches your current skills.
                                </p>
                            @else
                                <p style="margin:0 0 12px;font-size:14px;line-height:1.6;">
                                    A placement test helps the Guidance Office confirm the grade level that best matches your current skills before your enrollment is completed.
                                </p>
                            @endif

                            <p style="margin:0 0 8px;font-size:14px;font-weight:bold;">What you should do</p>
                            <ol style="margin:0 0 16px;padding-left:20px;font-size:14px;line-height:1.7;">
                                <li>Contact or visit the Guidance Office to confirm your test schedule and venue.</li>
                                <li>Bring your Learner Reference Number (LRN) and a valid school or government-issued ID.</li>
                                <li>Arrive at least 15 minutes before the scheduled time.</li>
                                <li>After the test, wait for the Guidance Office to record your result. Your enrollment record will be updated once the result is in.</li>
                            </ol>

                            <p style="margin:0 0 16px;font-size:14px;line-height:1.6;">
                                If you have questions, please visit the Guidance Office during school hours.
                            </p>
                            <p style="margin:0;">
                                <a href="{{ $loginUrl }}" style="display:inline-block;background:#296374;color:#ffffff;text-decoration:none;font-size:13px;font-weight:bold;padding:10px 16px;border-radius:8px;">
                                    Go to Student Portal
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
