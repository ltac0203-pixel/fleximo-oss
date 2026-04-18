<x-emails.layout title="お問い合わせ">
    <h1 style="font-size:18px;font-weight:700;border-bottom:2px solid #0ea5e9;padding-bottom:10px;margin:0 0 20px;color:#0f172a;">
        お問い合わせがありました
    </h1>

    <table style="width:100%;border-collapse:collapse;margin-bottom:20px;">
        <tr>
            <th style="text-align:left;padding:8px 12px;background-color:#f1f5f9;border:1px solid #e2e8f0;width:100px;font-weight:600;">お名前</th>
            <td style="padding:8px 12px;border:1px solid #e2e8f0;">{{ $senderName }}</td>
        </tr>
        <tr>
            <th style="text-align:left;padding:8px 12px;background-color:#f1f5f9;border:1px solid #e2e8f0;font-weight:600;">メール</th>
            <td style="padding:8px 12px;border:1px solid #e2e8f0;">
                <a href="mailto:{{ $senderEmail }}" style="color:#0ea5e9;">{{ $senderEmail }}</a>
            </td>
        </tr>
        <tr>
            <th style="text-align:left;padding:8px 12px;background-color:#f1f5f9;border:1px solid #e2e8f0;font-weight:600;">件名</th>
            <td style="padding:8px 12px;border:1px solid #e2e8f0;">{{ $contactSubject }}</td>
        </tr>
    </table>

    <h2 style="font-size:14px;font-weight:600;margin:0 0 10px;color:#0f172a;">お問い合わせ内容</h2>
    <div style="background-color:#f0f9ff;border:1px solid #0ea5e9;padding:16px;white-space:pre-wrap;border-radius:4px;color:#0f172a;">{{ $contactMessage }}</div>

    <hr style="border:none;border-top:1px solid #e2e8f0;margin:28px 0;">

    <p style="font-size:12px;color:#64748b;margin:0;">
        このメールは Fleximo のお問い合わせフォームから自動送信されました。<br>
        返信は送信者のメールアドレス宛に直接行ってください。
    </p>
</x-emails.layout>
