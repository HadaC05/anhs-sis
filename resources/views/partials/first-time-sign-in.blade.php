<div style="margin:0 0 16px;padding:20px;border:1px solid #cce2e2;border-radius:18px;background:#f3fafa;color:#125461;font-family:Arial,Helvetica,sans-serif;">
    <h4 style="margin:0 0 8px;font-size:18px;line-height:1.4;font-weight:700;">First time sign in</h4>
    <p style="margin:0 0 8px;font-size:14px;line-height:1.6;">Use your LRN as your username.</p>
    <p style="margin:0 0 14px;font-size:14px;line-height:1.6;">
        Password: first 2 letters of your first name + first 2 letters of your last name + {{ $enrollmentYear }} (enrollment year) + <strong>anhs</strong>. Use lowercase letters, with no spaces or punctuation.
    </p>
    <div style="padding:14px 16px;border-radius:16px;background:#ffffff;">
        <p style="margin:0 0 10px;font-size:12px;line-height:1.5;color:#52717a;">Example only &mdash; Jane Doe, enrolled in {{ $enrollmentYear }}</p>
        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="font-size:14px;line-height:1.6;">
            <tr>
                <td style="width:90px;padding:0 10px 6px 0;color:#52717a;vertical-align:top;">LRN</td>
                <td style="padding:0 0 6px;color:#0c2c55;font-weight:700;overflow-wrap:anywhere;"><code style="font-family:Consolas,'Courier New',monospace;">123456789012</code></td>
            </tr>
            <tr>
                <td style="padding:0 10px 0 0;color:#52717a;vertical-align:top;">Password</td>
                <td style="color:#0c2c55;font-weight:700;overflow-wrap:anywhere;"><code style="font-family:Consolas,'Courier New',monospace;">{{ \App\Support\StudentCredentials::passwordFormatExample((int) $enrollmentYear) }}</code></td>
            </tr>
        </table>
    </div>
    <p style="margin:12px 0 0;font-size:12px;line-height:1.6;color:#52717a;">Use your own registered name and LRN. Change your password after your first sign in. If you have already changed it, use your current password.</p>
</div>
