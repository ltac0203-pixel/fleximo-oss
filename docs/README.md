# Fleximo ドキュメント

このディレクトリは [Diátaxis](https://diataxis.fr/) フレームワークに基づいて整理されています。
目的に応じて、以下の4つの象限から参照してください。

| ディレクトリ           | 目的                 | 読者が求めるもの                               |
| ---------------------- | -------------------- | ---------------------------------------------- |
| [`tutorials/`](./tutorials/)     | **学習**             | 初めて触る人が手を動かして動作を確認できる手順 |
| [`how-to/`](./how-to/)           | **問題解決（作業）** | 特定のゴールを達成するためのレシピ             |
| [`reference/`](./reference/)     | **情報の参照**       | 正確で網羅的な仕様・構造・定義                 |
| [`explanation/`](./explanation/) | **理解**             | なぜその設計なのかという背景と考え方           |

## 各象限のインデックス

### Tutorials（学習）

- [Getting Started — ローカル環境を立ち上げて初めての注文を通す](./tutorials/getting-started.md)

### How-to Guides（作業）

- [fincode 決済を設定する](./how-to/configure-fincode.md)
- [新しいテナント（飲食店）を追加する](./how-to/add-a-tenant.md)
- [本番環境にデプロイする](./how-to/deploy-production.md)
- [バックアップとリストア](./how-to/backup-and-restore.md)
- [アップグレードする](./how-to/upgrade.md)
- [シークレットをローテーションする](./how-to/rotate-secrets.md)
- [リリースを作成する](./how-to/release.md)
- [コミットメッセージ規約](./how-to/commit-guidelines.md)

### Reference（参照）

- [アーキテクチャ概要](./reference/architecture.md)
- [注文ステータス一覧](./reference/order-statuses.md)
- [環境変数リファレンス](./reference/configuration.md)
- [API 仕様 (OpenAPI)](./reference/api.md)

### Explanation（理解）

- [マルチテナント設計の考え方](./explanation/multi-tenancy.md)
- [決済フローとなぜ Webhook を正とするのか](./explanation/payment-flow.md)
- [設計原則とスコープの線引き](./explanation/design-principles.md)

## ドキュメントに貢献する

新しいドキュメントを追加するときは、まず「これは4象限のどれか？」を決めてから配置してください。
境界が曖昧な場合は [Diátaxis の公式ガイド](https://diataxis.fr/how-to-use-diataxis/) を参照するか、
Issue で相談してください。
