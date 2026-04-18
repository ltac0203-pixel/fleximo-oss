# How-to: fincode 決済を設定する

Fleximo は決済プロバイダとして [fincode](https://www.fincode.jp/) を使います。
このガイドは、fincode アカウントを作成してから Fleximo 側で決済が動くところまでを扱います。

## ゴール

- クレジットカード決済が通る
- PayPay 決済が通る
- fincode からの Webhook を Fleximo が受け取って注文を確定できる

## 手順

### 1. fincode アカウントを作る

1. <https://www.fincode.jp/> でアカウント登録する
2. テスト環境のショップを作る（この時点で作るのはプラットフォーム運営者自身のショップ）
3. fincode 管理画面から以下をメモする
   - **API キー（シークレット）** — サーバー側で使用
   - **API キー（パブリック）** — ブラウザ側で使用
   - **プラットフォーム用ショップ ID** — 運営者自身のショップ ID
   - **Webhook 署名シークレット**（手順 4 で Webhook を登録すると発行される）

### 2. `.env` に認証情報をセットする

```env
FINCODE_API_KEY=<secret-key>
VITE_FINCODE_PUBLIC_KEY=<public-key>
FINCODE_SHOP_ID=<platform-shop-id>
FINCODE_WEBHOOK_SECRET=  # 手順 4 で設定
```

`VITE_FINCODE_PUBLIC_KEY` は Vite がビルド時にバンドルするため、変更後は `npm run build`（または `npm run dev` の再起動）が必要です。

### 3. 参加テナントの fincode ショップ ID を登録する

各テナント（加盟店）は自分のショップ ID を持ちます。これは `.env` ではなく DB にテナントごとに保存します。

1. fincode 管理画面でテナント用のショップを作り、ショップ ID をコピーする
2. Fleximo にプラットフォーム管理者としてログインし、`/admin/tenant-shop-ids` を開く
3. 対象テナントの行にショップ ID を入力して保存する

Fleximo は以降、そのテナントに関する fincode API 呼び出しに `Tenant-Shop-Id` ヘッダを付与します。

### 4. Webhook エンドポイントを登録する

fincode 管理画面の「Webhook 設定」で以下を登録します。

| 項目     | 値                                               |
| -------- | ------------------------------------------------ |
| URL      | `https://<your-domain>/api/webhooks/fincode`     |
| イベント | `payment.succeeded`, `payment.failed`, `payment.captured` |

ローカル開発では [ngrok](https://ngrok.com/) などでローカルサーバーを公開してください。

### 5. Webhook 署名シークレットを保存する

fincode が発行するシークレットを `FINCODE_WEBHOOK_SECRET` に入れます。
これが空だと、Fleximo は Webhook を全て拒否します（なりすまし防止のため）。

### 6. 動作確認

1. ローカルで注文を作り、テストカードで決済する
2. `storage/logs/laravel.log` に `[fincode] webhook received` が出ることを確認
3. `orders` テーブルで該当注文が `PAID` になっていることを確認

## うまくいかないとき

- **Webhook が届いているが注文が確定しない** → 署名検証に失敗している可能性。`FINCODE_WEBHOOK_SECRET` を確認。
- **決済ページに `fincode is not configured` と出る** → `FINCODE_API_KEY` / `VITE_FINCODE_PUBLIC_KEY` / `FINCODE_SHOP_ID` のいずれかが未設定、または対象テナントの `fincode_shop_id` が `/admin/tenant-shop-ids` で未登録。
- **決済ページで fincode.js が初期化できない** → `VITE_FINCODE_PUBLIC_KEY` が未設定。Vite は環境変数をビルド時にバンドルするので、追加後は Vite を再ビルドする必要があります。
- **本番でテストカードが通ってしまう** → テスト環境のキーのまま本番にしている。本番用のキーに差し替える。

## 関連

- [決済フローの背景](../explanation/payment-flow.md)
- [注文ステータス一覧](../reference/order-statuses.md)
