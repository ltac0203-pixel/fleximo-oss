<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>メンテナンス中 - Fleximo</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: #f8fafc;
            color: #334155;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
        }
        .bg-shapes { position: absolute; inset: 0; overflow: hidden; pointer-events: none; }
        .bg-shapes svg { position: absolute; }
        .bg-tl { left: -80px; top: -80px; width: 384px; height: 384px; color: #e0f2fe; }
        .bg-tr { right: -64px; top: 80px; width: 256px; height: 256px; color: #cffafe; }
        .bg-br { right: -40px; bottom: -40px; width: 288px; height: 288px; color: #e0f2fe; }
        .main { position: relative; z-index: 10; width: 100%; max-width: 640px; padding: 16px; text-align: center; }
        .code { font-size: clamp(96px, 15vw, 144px); font-weight: 700; color: #0ea5e9; line-height: 1; }
        .card {
            margin-top: 32px;
            background: rgba(255,255,255,0.9);
            border: 1px solid #e2e8f0;
            padding: 32px;
        }
        .icon-wrap { margin-bottom: 24px; display: flex; justify-content: center; }
        .icon-wrap svg { width: 96px; height: 96px; color: #0ea5e9; }
        h1 { font-size: clamp(24px, 4vw, 36px); font-weight: 700; color: #0f172a; }
        p { margin-top: 16px; font-size: clamp(14px, 2vw, 18px); line-height: 1.6; color: #64748b; }
        .btn {
            display: inline-flex;
            align-items: center;
            margin-top: 32px;
            padding: 10px 20px;
            font-size: 14px;
            font-weight: 500;
            color: #fff;
            background: #0ea5e9;
            border: 1px solid #0ea5e9;
            cursor: pointer;
            text-decoration: none;
        }
        .btn:hover { background: #0284c7; }
    </style>
</head>
<body>
    <div class="bg-shapes">
        <svg class="bg-tl" viewBox="0 0 200 200" fill="currentColor">
            <polygon points="0,0 200,0 0,200" opacity="0.5"/>
            <polygon points="40,0 200,0 40,160" opacity="0.3"/>
            <polygon points="80,0 200,0 80,120" opacity="0.2"/>
        </svg>
        <svg class="bg-tr" viewBox="0 0 100 100">
            <polygon points="50,5 95,27.5 95,72.5 50,95 5,72.5 5,27.5" fill="currentColor" opacity="0.4"/>
            <polygon points="50,20 80,35 80,65 50,80 20,65 20,35" fill="currentColor" opacity="0.3"/>
        </svg>
        <svg class="bg-br" viewBox="0 0 150 150" fill="currentColor">
            <polygon points="150,150 150,50 50,150" opacity="0.4"/>
            <polygon points="150,150 150,80 80,150" opacity="0.3"/>
        </svg>
    </div>

    <div class="main">
        <div class="code">503</div>
        <div class="card">
            <div class="icon-wrap">
                <svg viewBox="0 0 100 100">
                    <polygon points="50,5 95,27.5 95,72.5 50,95 5,72.5 5,27.5" fill="currentColor" opacity="0.1" stroke="currentColor" stroke-width="2"/>
                </svg>
            </div>
            <h1>ただいまメンテナンス中です</h1>
            <p>サービスの改善作業を行っています。<br>しばらくしてから再度アクセスしてください。</p>
            <button class="btn" onclick="window.location.reload()">再読み込み</button>
        </div>
    </div>
</body>
</html>
