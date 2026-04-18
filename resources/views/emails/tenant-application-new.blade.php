<x-emails.layout title="新規テナント申し込み通知">
    <h1 style="font-size:18px;font-weight:700;border-bottom:2px solid #0ea5e9;padding-bottom:10px;margin:0 0 20px;color:#0f172a;">
        新規テナント申し込みがありました
    </h1>

    <p style="margin:0 0 20px;">新規テナント申し込みを受け付けました。審査をお願いします。</p>

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
            <th style="text-align:left;padding:8px 12px;background-color:#f1f5f9;border:1px solid #e2e8f0;font-weight:600;">申請者名</th>
            <td style="padding:8px 12px;border:1px solid #e2e8f0;">{{ $application->applicant_name }}</td>
        </tr>
        <tr>
            <th style="text-align:left;padding:8px 12px;background-color:#f1f5f9;border:1px solid #e2e8f0;font-weight:600;">メールアドレス</th>
            <td style="padding:8px 12px;border:1px solid #e2e8f0;">
                <a href="mailto:{{ $application->applicant_email }}" style="color:#0ea5e9;">{{ $application->applicant_email }}</a>
            </td>
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
        <tr>
            <th style="text-align:left;padding:8px 12px;background-color:#f1f5f9;border:1px solid #e2e8f0;font-weight:600;">申し込み日時</th>
            <td style="padding:8px 12px;border:1px solid #e2e8f0;">{{ $application->created_at->format('Y年m月d日 H:i') }}</td>
        </tr>
    </table>

    <div style="background-color:#fffbeb;border:1px solid #f59e0b;border-radius:4px;padding:12px;margin:0 0 24px;">
        <p style="margin:0;font-size:13px;color:#92400e;">
            <strong>要対応：</strong>管理画面にて申し込み内容を審査してください。
        </p>
    </div>

    <div style="text-align:center;margin:0 0 24px;">
        <a href="{{ url('/admin/applications/' . $application->id) }}"
            style="display:inline-block;background-color:#0ea5e9;color:#ffffff;text-decoration:none;padding:12px 24px;border-radius:4px;font-weight:bold;box-shadow:2px 2px 0 0 rgba(14,165,233,0.2);">
            申し込み詳細を確認する
        </a>
    </div>

    <hr style="border:none;border-top:1px solid #e2e8f0;margin:28px 0;">

    <p style="font-size:12px;color:#64748b;margin:0;">
        このメールは Fleximo 管理システムから自動送信されました。
    </p>
</x-emails.layout>
