<x-emails.layout title="テナント申し込み受付確認">
    <h1 style="font-size:18px;font-weight:700;border-bottom:2px solid #0ea5e9;padding-bottom:10px;margin:0 0 20px;color:#0f172a;">
        テナント申し込みを受け付けました
    </h1>

    <p style="margin:0 0 12px;">{{ $application->applicant_name }} 様</p>

    <p style="margin:0 0 20px;">
        Fleximo へのテナント申し込みありがとうございます。<br>
        以下の内容でお申し込みを受け付けました。
    </p>

    <table style="width:100%;border-collapse:collapse;margin:0 0 20px;">
        <tr>
            <th style="text-align:left;padding:8px 12px;background-color:#f1f5f9;border:1px solid #e2e8f0;width:120px;font-weight:600;">申し込み番号</th>
            <td style="padding:8px 12px;border:1px solid #e2e8f0;font-weight:bold;">{{ $application->application_code }}</td>
        </tr>
        <tr>
            <th style="text-align:left;padding:8px 12px;background-color:#f1f5f9;border:1px solid #e2e8f0;font-weight:600;">店舗名</th>
            <td style="padding:8px 12px;border:1px solid #e2e8f0;">{{ $application->tenant_name }}</td>
        </tr>
        <tr>
            <th style="text-align:left;padding:8px 12px;background-color:#f1f5f9;border:1px solid #e2e8f0;font-weight:600;">業種</th>
            <td style="padding:8px 12px;border:1px solid #e2e8f0;">{{ $application->business_type->label() }}</td>
        </tr>
        <tr>
            <th style="text-align:left;padding:8px 12px;background-color:#f1f5f9;border:1px solid #e2e8f0;font-weight:600;">お名前</th>
            <td style="padding:8px 12px;border:1px solid #e2e8f0;">{{ $application->applicant_name }}</td>
        </tr>
        <tr>
            <th style="text-align:left;padding:8px 12px;background-color:#f1f5f9;border:1px solid #e2e8f0;font-weight:600;">メールアドレス</th>
            <td style="padding:8px 12px;border:1px solid #e2e8f0;">{{ $application->applicant_email }}</td>
        </tr>
        <tr>
            <th style="text-align:left;padding:8px 12px;background-color:#f1f5f9;border:1px solid #e2e8f0;font-weight:600;">電話番号</th>
            <td style="padding:8px 12px;border:1px solid #e2e8f0;">{{ $application->applicant_phone }}</td>
        </tr>
        @if($application->tenant_address)
        <tr>
            <th style="text-align:left;padding:8px 12px;background-color:#f1f5f9;border:1px solid #e2e8f0;font-weight:600;">住所</th>
            <td style="padding:8px 12px;border:1px solid #e2e8f0;">{{ $application->tenant_address }}</td>
        </tr>
        @endif
    </table>

    <div style="background-color:#f0f9ff;border:1px solid #0ea5e9;border-radius:4px;padding:16px;margin:0 0 20px;">
        <h2 style="font-size:14px;font-weight:600;margin:0 0 8px;color:#0ea5e9;">今後の流れ</h2>
        <ol style="margin:0;padding-left:20px;color:#0f172a;">
            <li>当社にて申し込み内容を審査いたします</li>
            <li>審査完了後、結果をメールにてお知らせします</li>
            <li>承認された場合は、ログイン情報をお送りします</li>
        </ol>
    </div>

    <p style="margin:0;">
        審査には通常1〜3営業日程度お時間をいただきます。<br>
        ご不明な点がございましたら、お気軽にお問い合わせください。
    </p>

    <hr style="border:none;border-top:1px solid #e2e8f0;margin:28px 0;">

    <p style="font-size:12px;color:#64748b;margin:0;">
        このメールは Fleximo から自動送信されました。<br>
        お心当たりがない場合は、このメールを破棄してください。
    </p>
</x-emails.layout>
