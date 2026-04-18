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
2. テスト環境のショップを作る
3. 「API キー」「ショップ ID」をメモする

### 2. `.env` に認証情報をセットする

```env
FINCODE_API_KEY=<your-fincode-secret-key>
FINCODE_SHOP_ID=<your-fincode-shop-id>
FINCODE_WEBHOOK_SECRET=  # 次のステップで設定
```

### 3. Webhook エンドポイントを登録する

fincode 管理画面の「Webhook 設定」で以下を登録します。

| 項目     | 値                                               |
| -------- | ------------------------------------------------ |
| URL      | `https://<your-domain>/api/webhooks/fincode`     |
| イベント | `payment.succeeded`, `payment.failed`, `payment.captured` |

ローカル開発では [ngrok](https://ngrok.com/) などでローカルサーバーを公開してください。

### 4. Webhook 署名シークレットを保存する

fincode が発行するシークレットを `FINCODE_WEBHOOK_SECRET` に入れます。
これが空だと、Fleximo は Webhook を全て拒否します（なりすまし防止のため）。

### 5. 動作確認

1. ローカルで注文を作り、テストカードで決済する
2. `storage/logs/laravel.log` に `[fincode] webhook received` が出ることを確認
3. `orders` テーブルで該当注文が `PAID` になっていることを確認

## うまくいかないとき

- **Webhook が届いているが注文が確定しない** → 署名検証に失敗している可能性。`FINCODE_WEBHOOK_SECRET` を確認。
- **決済ページに `fincode is not configured` と出る** → `FINCODE_API_KEY` / `FINCODE_SHOP_ID` が未設定。
- **本番でテストカードが通ってしまう** → テスト環境のキーのまま本番にしている。本番用のキーに差し替える。

## 関連

- [決済フローの背景](../explanation/payment-flow.md)
- [注文ステータス一覧](../reference/order-statuses.md)
