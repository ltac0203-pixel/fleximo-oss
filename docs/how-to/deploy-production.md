# How-to: 本番環境にデプロイする

このガイドは、Fleximo を一般的な Linux サーバー（Xserver、さくら VPS、ConoHa など）にデプロイする
最小限の手順をまとめたものです。Docker / Kubernetes を使う場合は別途ガイドを追加予定です。

## ゴール

- 本番ドメインで Fleximo が HTTPS で動く
- データベースとキューが永続化されている
- fincode Webhook が届く

## 前提

- SSH で接続できるサーバー
- ドメインと SSL 証明書（Let's Encrypt 等）
- MariaDB/MySQL・Redis が利用可能
- PHP 8.2 / Node.js 20 / Composer

## 手順

### 1. コードを配置

```bash
ssh user@your-server
cd /var/www
git clone https://github.com/<your-org>/fleximo.git
cd fleximo
```

### 2. 依存をインストールし、ビルドする

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
```

### 3. `.env` を本番用に設定

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.example.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=fleximo
DB_USERNAME=...
DB_PASSWORD=...

REDIS_HOST=127.0.0.1

FINCODE_API_KEY=...           # 本番キー
FINCODE_SHOP_ID=...
FINCODE_WEBHOOK_SECRET=...

# 法令対応（必ず自社情報に書き換える）
COMPANY_NAME="..."
COMPANY_REPRESENTATIVE="..."
COMPANY_POSTAL_CODE="..."
COMPANY_ADDRESS="..."
COMPANY_CONTACT_EMAIL="..."
```

### 4. マイグレーションとキャッシュ

```bash
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

> **重要**: 本番では `migrate:fresh`, `migrate:refresh`, `db:wipe` を**絶対に実行しない**でください。
> スキーマ変更は新規マイグレーションを追加する形で対応します。

### 5. キューワーカーとスケジューラを常駐させる

systemd または supervisor で以下を登録します。

```
php artisan queue:work --sleep=3 --tries=3
* * * * * cd /var/www/fleximo && php artisan schedule:run >> /dev/null 2>&1
```

### 6. Web サーバーの設定

Nginx / Apache で `public/` をドキュメントルートにし、HTTPS を有効化します。
`/api/webhooks/fincode` への POST が通ることを必ず確認してください。

### 7. 動作確認

- トップページが表示される
- 顧客として注文→決済が完了する
- 管理画面の KDS に注文が流れる
- `/legal/transactions` の特商法表記が自社情報になっている

## ロールバック

デプロイ前に以下を取得しておきます。

- データベースのダンプ
- `.env` のコピー
- 直前のコミット SHA

問題があった場合はコードを前コミットに戻し、必要ならダンプからDBを復元します。

## 関連

- [環境変数リファレンス](../reference/configuration.md)
- [設計原則](../explanation/design-principles.md)
