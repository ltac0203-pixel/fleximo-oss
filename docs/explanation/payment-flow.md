# Explanation: 決済フローと、なぜ Webhook を正とするのか

Fleximo の注文は「フロントが決済成功したタイミング」ではなく「fincode からの Webhook を受け取った
タイミング」で確定します。この設計は慎重な選択であり、以下に理由を説明します。

## ナイーブな実装の問題点

素朴に書くとこうなります。

```ts
// フロント
const result = await fincode.chargeCard(cardToken);
if (result.status === 'succeeded') {
  await api.post('/orders/{id}/confirm');  // ← ここで注文確定
}
```

このコードは**高確率で障害を起こします**。

1. fincode は成功したが、`confirm` API への通信がネットワーク切断で失敗 → 料金は引き落とされたが注文は `PENDING_PAYMENT` のまま
2. 逆に、fincode の結果を待たずにユーザーがタブを閉じた → 決済は進んでいるが、フロントの `confirm` が飛ばない
3. 悪意のあるユーザーがブラウザ DevTools で `status === 'succeeded'` を偽装 → 料金未払いで注文確定

つまり **「クライアントが注文の真偽を宣言する」設計は、信頼できない**。

## Webhook を正とするとどうなるか

正しいフローはこうなります。

```
顧客 ─► 注文作成 (PENDING_PAYMENT) ─► fincode に遷移 ─► 決済
                                                           │
                                                           ▼
                                   fincode ─► Webhook ─► Fleximo (PAID)
                                                           │
                                                           ▼
                                                    通知 / KDS に流す
```

フロントは「決済ページに送り出す」までが仕事です。確定はサーバー側で Webhook を受けて行います。

## Webhook 受信側の責務

`POST /api/webhooks/fincode` で以下を行います。

1. **署名検証**: fincode の `Fincode-Signature` ヘッダーを HMAC-SHA256 で検証
2. **冪等性**: 同じ `event_id` を2回受け取っても 1回しか処理しない（Redis の SETNX で重複排除）
3. **状態遷移**:
   - `payment.succeeded` → 注文を `PAID` に
   - `payment.failed` → 注文を `PAYMENT_FAILED` に
4. **後処理ジョブ投入**: KDS 通知・売上集計・メール送信は非同期キューで行う

## よくある誤りと対策

| 誤り                                      | 正しい対応                                  |
| ----------------------------------------- | ------------------------------------------- |
| フロントの `onSuccess` で注文を確定する    | フロントはリダイレクトのみ。確定は Webhook |
| Webhook が遅いのでタイムアウトで確定する  | 遅いなら fincode の UI で「処理中」を表示   |
| Webhook を同期的に全処理する              | 受信→保存→202返却→以降は非同期ジョブに渡す |
| 署名検証をスキップしている                | **絶対にやらない**。なりすまし決済が可能になる |

## 開発環境での Webhook テスト

ローカルでは [ngrok](https://ngrok.com/) や [cloudflared](https://github.com/cloudflare/cloudflared)
でトンネルを張り、fincode 管理画面に `https://xxx.ngrok.io/api/webhooks/fincode` を登録します。

## 関連

- [注文ステータス一覧](../reference/order-statuses.md)
- [How-to: fincode を設定する](../how-to/configure-fincode.md)
