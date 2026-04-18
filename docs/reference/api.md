# Reference: API 仕様

Fleximo は主に Inertia 経由で動作するため公開 REST API は最小限ですが、以下のエンドポイントは
外部連携・Webhook・モバイルクライアント向けに用意されています。

## OpenAPI 定義

- ファイル: [`openapi.yml`](./openapi.yml)
- 閲覧: [Swagger Editor](https://editor.swagger.io/) に貼り付けるか、以下でローカル起動できます。

```bash
npx @redocly/cli preview-docs docs/reference/openapi.yml
```

## 主要エンドポイント

| メソッド | パス                          | 用途                              |
| -------- | ----------------------------- | --------------------------------- |
| POST     | `/api/webhooks/fincode`       | fincode からの決済 Webhook 受信    |
| GET      | `/api/tenants/search`         | テナント検索（顧客向け）           |
| GET      | `/api/tenants/{slug}/menus`   | メニュー一覧                       |
| POST     | `/api/orders`                 | 注文作成                          |
| GET      | `/api/orders/{id}`            | 注文詳細                          |

## 認証

- 顧客向けAPI: Laravel Sanctum のセッション Cookie
- モバイルクライアント: Sanctum のパーソナルアクセストークン
- Webhook: fincode 署名ヘッダー検証

## レートリミット

- 未認証: 60 req/min
- 認証済み: 120 req/min

変更は `routes/api.php` の `throttle:` ミドルウェアで行います。
