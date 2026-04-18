# Reference: 注文ステータス一覧

Fleximo の注文は以下のステータスを順に遷移します。背景と設計理由は
[Explanation: 決済フロー](../explanation/payment-flow.md) を参照してください。

## 遷移図

```
PENDING_PAYMENT ──► PAID ──► ACCEPTED ──► IN_PROGRESS ──► READY ──► COMPLETED
      │
      └──► PAYMENT_FAILED（決済失敗で終端）
```

※ 顧客キャンセル/店舗キャンセルは別途 `CANCELLED` を持ち、任意の状態から遷移できます（ただし
`COMPLETED` からはキャンセル不可）。

## 各ステータス

| ステータス         | 意味                                     | 遷移するトリガー                        |
| ------------------ | ---------------------------------------- | --------------------------------------- |
| `PENDING_PAYMENT`  | 注文は作成されたが決済未完了             | 顧客がチェックアウトボタンを押した      |
| `PAID`             | 決済が確定した（fincode Webhook 受信後） | **fincode Webhook**: `payment.succeeded` |
| `PAYMENT_FAILED`   | 決済失敗で終端                           | fincode Webhook: `payment.failed`       |
| `ACCEPTED`         | テナントが注文を受諾                     | テナント管理者/スタッフの操作           |
| `IN_PROGRESS`      | 調理中                                   | スタッフの操作                          |
| `READY`            | 受取可能                                 | スタッフの操作                          |
| `COMPLETED`        | 顧客が受け取り済み                       | スタッフの操作                          |
| `CANCELLED`        | キャンセル済み                           | 顧客または店舗の操作                    |

## 重要な不変条件

1. **`PAID` への遷移は Webhook 以外から起こしてはいけない**。
   フロントの「決済成功」イベントだけで `PAID` に進めると、
   ネットワーク障害時に決済失敗の注文を成立させてしまう。
2. **`COMPLETED` からは逆戻りしない**。
   売上集計の根拠になるため、取り消しは返金処理を別途行う。
3. **`PENDING_PAYMENT` のまま一定時間経過した注文は自動で `CANCELLED` になる**。
   タイムアウト時間は `config/orders.php` の `pending_payment_timeout` で設定。

## フロントに露出するラベル

| ステータス         | 顧客向け表示       | KDS 表示         |
| ------------------ | ------------------ | ---------------- |
| `PENDING_PAYMENT`  | 決済手続き中       | （KDSに出さない）|
| `PAID`             | お店が確認中です   | 新規注文         |
| `ACCEPTED`         | 準備中             | 受諾済み         |
| `IN_PROGRESS`      | 調理中             | 調理中           |
| `READY`            | 受取可能           | 受取可能         |
| `COMPLETED`        | 受取済み           | 完了             |
| `CANCELLED`        | キャンセル         | キャンセル       |
| `PAYMENT_FAILED`   | 決済に失敗しました | （KDSに出さない）|
