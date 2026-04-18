<x-emails.layout title="テナント申し込みについてのご連絡">
    <h1 style="font-size:18px;font-weight:700;border-bottom:2px solid #0ea5e9;padding-bottom:10px;margin:0 0 20px;color:#0f172a;">
        テナント申し込みについてのご連絡
    </h1>

    <p style="margin:0 0 12px;">{{ $application->applicant_name }} 様</p>

    <p style="margin:0 0 20px;">
        この度は Fleximo へのお申し込みありがとうございます。<br>
        審査の結果、誠に残念ながら今回のお申し込みを承認することができませんでした。
    </p>

    <table style="width:100%;border-collapse:collapse;margin:0 0 20px;">
        <tr>
            <th style="text-align:left;padding:8px 12px;background-color:#f1f5f9;border:1px solid #e2e8f0;width:120px;font-weight:600;">申し込み番号</th>
            <td style="padding:8px 12px;border:1px solid #e2e8f0;">{{ $application->application_code }}</td>
        </tr>
        <tr>
            <th style="text-align:left;padding:8px 12px;background-color:#f1f5f9;border:1px solid #e2e8f0;font-weight:600;">店舗名</th>
            <td style="padding:8px 12px;border:1px solid #e2e8f0;">{{ $application->tenant_name }}</td>
        </tr>
    </table>

    @if($application->rejection_reason)
    <div style="background-color:#f8fafc;border:1px solid #e2e8f0;border-radius:4px;padding:16px;margin:0 0 20px;">
        <h2 style="font-size:14px;font-weight:600;margin:0 0 8px;color:#0f172a;">審査結果について</h2>
        <p style="margin:0;white-space:pre-wrap;color:#0f172a;">{{ $application->rejection_reason }}</p>
    </div>
    @endif

    <p style="margin:0 0 12px;">
        今回の結果に関してご不明な点がございましたら、<br>
        お気軽にお問い合わせください。
    </p>

    <p style="margin:0;">
        今後とも Fleximo をよろしくお願いいたします。
    </p>

    <hr style="border:none;border-top:1px solid #e2e8f0;margin:28px 0;">

    <p style="font-size:12px;color:#64748b;margin:0;">
        このメールは Fleximo から自動送信されました。<br>
        お心当たりがない場合は、このメールを破棄してください。
    </p>
</x-emails.layout>
