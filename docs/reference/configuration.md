# Reference: 環境変数リファレンス

`.env` に設定できる変数の一覧です。完全なサンプルは [`.env.example`](../../.env.example) を参照してください。

## アプリケーション

| 変数名         | デフォルト                | 説明                                 |
| -------------- | ------------------------- | ------------------------------------ |
| `APP_NAME`     | `Fleximo`                 | アプリ表示名                         |
| `APP_ENV`      | `local`                   | `local` / `staging` / `production`   |
| `APP_DEBUG`    | `false`                   | 本番では必ず `false`                 |
| `APP_URL`      | `http://localhost:8000`   | 外部からアクセスする URL             |
| `APP_TIMEZONE` | `Asia/Tokyo`              | タイムゾーン                         |

## データベース

| 変数名          | 値                    | 備考                              |
| --------------- | --------------------- | --------------------------------- |
| `DB_CONNECTION` | `mysql`               | **SQLite は未サポート**           |
| `DB_HOST`       | `127.0.0.1`           |                                   |
| `DB_PORT`       | `3306`                |                                   |
| `DB_DATABASE`   | `fleximo`             |                                   |
| `DB_USERNAME`   | ー                    |                                   |
| `DB_PASSWORD`   | ー                    |                                   |

## Redis / キュー / キャッシュ

| 変数名            | 値                | 備考                 |
| ----------------- | ----------------- | -------------------- |
| `REDIS_HOST`      | `127.0.0.1`       |                      |
| `REDIS_PASSWORD`  | `null`            | 必要なら設定         |
| `REDIS_PORT`      | `6379`            |                      |
| `QUEUE_CONNECTION`| `redis`           |                      |
| `CACHE_STORE`     | `redis`           |                      |
| `SESSION_DRIVER`  | `redis`           |                      |

## fincode（決済）

| 変数名                    | 説明                                     |
| ------------------------- | ---------------------------------------- |
| `FINCODE_API_KEY`         | fincode の API キー                     |
| `FINCODE_SHOP_ID`         | fincode のショップ ID                   |
| `FINCODE_WEBHOOK_SECRET`  | Webhook の署名検証用シークレット         |
| `FINCODE_ENV`             | `test` / `production`                    |

設定方法は [How-to: fincode 決済を設定する](../how-to/configure-fincode.md) を参照してください。

## 法令対応（日本）

特定商取引法表記・プライバシーポリシー・利用規約ページ（`/legal/*`）に差し込まれる値です。
**本番運用では必ず自社情報に書き換えてください**。プレースホルダのまま公開すると法令違反になります。

| 変数名                   | 説明                           |
| ------------------------ | ------------------------------ |
| `COMPANY_NAME`           | 運営会社名                     |
| `COMPANY_REPRESENTATIVE` | 代表者氏名                     |
| `COMPANY_POSTAL_CODE`    | 郵便番号（例: `000-0000`）     |
| `COMPANY_ADDRESS`        | 所在地                         |
| `COMPANY_CONTACT_EMAIL`  | 問い合わせ先メール             |

## エラー監視 / ログ

| 変数名                   | 説明                              |
| ------------------------ | --------------------------------- |
| `SENTRY_LARAVEL_DSN`     | Sentry（サーバー側）の DSN         |
| `VITE_SENTRY_DSN`        | Sentry（フロント側）の DSN         |
| `LOG_CHANNEL`            | `stack` / `daily` / `stderr` など |

## メール

| 変数名          | 例                   |
| --------------- | -------------------- |
| `MAIL_MAILER`   | `smtp`               |
| `MAIL_HOST`     | `smtp.example.com`   |
| `MAIL_PORT`     | `587`                |
| `MAIL_USERNAME` | ー                   |
| `MAIL_PASSWORD` | ー                   |
| `MAIL_FROM_ADDRESS` | `noreply@...`    |
| `MAIL_FROM_NAME`    | `Fleximo`        |
