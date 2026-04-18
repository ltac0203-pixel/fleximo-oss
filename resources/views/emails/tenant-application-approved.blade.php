<x-emails.layout title="テナント申し込み承認のお知らせ">
    <h1 style="font-size:18px;font-weight:700;border-bottom:2px solid #0ea5e9;padding-bottom:10px;margin:0 0 20px;color:#0f172a;">
        テナント申し込みが承認されました
    </h1>

    <p style="margin:0 0 12px;">{{ $application->applicant_name }} 様</p>

    <p style="margin:0 0 20px;">
        この度は Fleximo へのお申し込みありがとうございます。<br>
        審査の結果、<strong>{{ $application->tenant_name }}</strong> のテナント登録を承認いたしました。
    </p>

    @if($requiresPasswordReset)
    <div style="background-color:#f0fdf4;border:1px solid #22c55e;border-radius:4px;padding:16px;margin:0 0 20px;">
        <h2 style="font-size:14px;font-weight:600;margin:0 0 12px;color:#16a34a;">ご利用開始の手順</h2>
        <ol style="margin:0;padding-left:20px;color:#0f172a;">
            <li style="margin-bottom:8px;">下記のボタンからパスワードを設定してください</li>
            <li style="margin-bottom:8px;">パスワード設定後、管理画面にログインできます</li>
            <li>店舗情報やメニューを登録して、サービスを開始しましょう</li>
        </ol>
    </div>
    @else
    <div style="background-color:#f0fdf4;border:1px solid #22c55e;border-radius:4px;padding:16px;margin:0 0 20px;">
        <h2 style="font-size:14px;font-weight:600;margin:0 0 12px;color:#16a34a;">ご利用開始の手順</h2>
        <ol style="margin:0;padding-left:20px;color:#0f172a;">
            <li style="margin-bottom:8px;">下記のボタンからダッシュボードにログインしてください</li>
            <li style="margin-bottom:8px;">申し込み時に設定したメールアドレスとパスワードをご使用ください</li>
            <li>店舗情報やメニューを登録して、サービスを開始しましょう</li>
        </ol>
    </div>
    @endif

    <table style="width:100%;border-collapse:collapse;margin:0 0 20px;">
        <tr>
            <th style="text-align:left;padding:8px 12px;background-color:#f1f5f9;border:1px solid #e2e8f0;width:140px;font-weight:600;">申し込み番号</th>
            <td style="padding:8px 12px;border:1px solid #e2e8f0;">{{ $application->application_code }}</td>
        </tr>
        <tr>
            <th style="text-align:left;padding:8px 12px;background-color:#f1f5f9;border:1px solid #e2e8f0;font-weight:600;">店舗名</th>
            <td style="padding:8px 12px;border:1px solid #e2e8f0;">{{ $tenant->name }}</td>
        </tr>
        <tr>
            <th style="text-align:left;padding:8px 12px;background-color:#f1f5f9;border:1px solid #e2e8f0;font-weight:600;">ログインメール</th>
            <td style="padding:8px 12px;border:1px solid #e2e8f0;">{{ $user->email }}</td>
        </tr>
    </table>

    @if($requiresPasswordReset)
    <div style="text-align:center;margin:28px 0;">
        <a href="{{ $passwordResetUrl }}"
            style="display:inline-block;background-color:#0ea5e9;color:#ffffff;text-decoration:none;padding:14px 28px;border-radius:4px;font-weight:bold;font-size:16px;box-shadow:2px 2px 0 0 rgba(14,165,233,0.2);">
            パスワードを設定する
        </a>
    </div>

    <div style="background-color:#fffbeb;border:1px solid #f59e0b;border-radius:4px;padding:12px;margin:0 0 20px;">
        <p style="margin:0;font-size:13px;color:#92400e;">
            <strong>ご注意：</strong>このリンクは60分間有効です。<br>
            期限切れの場合は、ログイン画面の「パスワードを忘れた方」から再設定をお願いします。
        </p>
    </div>
    @else
    <div style="text-align:center;margin:28px 0;">
        <a href="{{ $loginUrl }}"
            style="display:inline-block;background-color:#0ea5e9;color:#ffffff;text-decoration:none;padding:14px 28px;border-radius:4px;font-weight:bold;font-size:16px;box-shadow:2px 2px 0 0 rgba(14,165,233,0.2);">
            ダッシュボードにログイン
        </a>
    </div>
    @endif

    <p style="margin:0 0 4px;">
        ご不明な点がございましたら、お気軽にお問い合わせください。<br>
        Fleximo をよろしくお願いいたします。
    </p>

    <hr style="border:none;border-top:1px solid #e2e8f0;margin:28px 0;">

    <p style="font-size:12px;color:#64748b;margin:0;">
        このメールは Fleximo から自動送信されました。<br>
        お心当たりがない場合は、このメールを破棄してください。
    </p>
</x-emails.layout>
