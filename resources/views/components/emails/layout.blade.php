@props(['title'])
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ $title }} | {{ config('app.name', 'Fleximo') }}</title>
  <style>
    body { margin:0; padding:0; background-color:#f8fafc; }
    table { border-spacing:0; }
    @media (max-width:620px) {
      .email-card { width:100% !important; }
      .email-body { padding:24px 20px !important; }
    }
  </style>
</head>
<body>
  <!-- 外側ラッパー (slate-50背景) -->
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#f8fafc;">
    <tr><td align="center" style="padding:40px 16px;">

      <!-- カード (max-width:600px) -->
      <table class="email-card" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;">

        <!-- スカイブルーのトップアクセントライン (4px) -->
        <tr><td style="background:#0ea5e9;height:4px;border-radius:8px 8px 0 0;"></td></tr>

        <!-- ヘッダー: ロゴ -->
        <tr>
          <td style="background:#ffffff;padding:24px 40px 20px;border-left:1px solid #e2e8f0;border-right:1px solid #e2e8f0;">
            <span style="font-size:22px;font-weight:800;color:#0ea5e9;letter-spacing:-0.5px;font-family:'Helvetica Neue',Arial,'Hiragino Sans',sans-serif;">
              {{ config('app.name', 'Fleximo') }}
            </span>
          </td>
        </tr>

        <!-- 区切り線 -->
        <tr><td style="background:#ffffff;border-left:1px solid #e2e8f0;border-right:1px solid #e2e8f0;">
          <div style="height:1px;background:#e2e8f0;margin:0 40px;"></div>
        </td></tr>

        <!-- メインコンテンツ (slot) -->
        <tr>
          <td class="email-body" style="background:#ffffff;padding:32px 40px;border-left:1px solid #e2e8f0;border-right:1px solid #e2e8f0;font-family:'Helvetica Neue',Arial,'Hiragino Sans',sans-serif;font-size:14px;line-height:1.7;color:#0f172a;">
            {{ $slot }}
          </td>
        </tr>

        <!-- フッター -->
        <tr>
          <td style="background:#f8fafc;border:1px solid #e2e8f0;border-top:none;border-radius:0 0 8px 8px;padding:20px 40px;text-align:center;font-family:'Helvetica Neue',Arial,'Hiragino Sans',sans-serif;font-size:12px;color:#64748b;">
            @php
              $appName = config('app.name', 'Fleximo');
              $appUrl = rtrim((string) config('app.url', 'https://example.com'), '/');
              $appHost = parse_url($appUrl, PHP_URL_HOST) ?: $appUrl;
            @endphp
            <p style="margin:0;">
              &copy; {{ date('Y') }} {{ $appName }}. All rights reserved.
            </p>
            <p style="margin:4px 0 0;">
              <a href="{{ $appUrl }}" style="color:#0ea5e9;text-decoration:none;">{{ $appHost }}</a>
            </p>
          </td>
        </tr>

      </table>
    </td></tr>
  </table>
</body>
</html>
