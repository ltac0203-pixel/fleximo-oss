# Reference: アーキテクチャ概要

Fleximo の構成要素と責務を列挙した参照ドキュメントです。
「なぜこの設計なのか」は [Explanation: マルチテナント設計](../explanation/multi-tenancy.md) を参照してください。

## 論理構成図

```
┌──────────────────────────────────────────────────────┐
│ Browser (React 19 + Inertia 2 + TypeScript)          │
│   - 顧客フロント / テナント管理 / KDS                │
└────────────────────┬─────────────────────────────────┘
                     │ Inertia (同一オリジン)
┌────────────────────▼─────────────────────────────────┐
│ Laravel 12 (PHP 8.2)                                 │
│   Controllers ─► Services ─► Models (Eloquent)       │
│   Policies / FormRequest / Global Scopes             │
└────┬──────────────────────┬──────────────────────────┘
     │                      │
┌────▼──────────┐  ┌────────▼────────┐  ┌──────────────┐
│ MariaDB/MySQL │  │ Redis           │  │ fincode API  │
│ (共有テーブル) │  │ (cache / queue) │  │ (決済/Webhook)│
└───────────────┘  └─────────────────┘  └──────────────┘
```

## 主要コンポーネント

| コンポーネント               | 責務                                                             |
| ---------------------------- | ---------------------------------------------------------------- |
| `app/Http/Controllers/`      | リクエスト/レスポンスの橋渡し。ビジネスロジックは持たない        |
| `app/Services/`              | ユースケース単位のビジネスロジック                               |
| `app/Models/`                | Eloquent モデル。Global Scope でテナント分離を強制                |
| `app/Policies/`              | 権限判定。Controller は `authorize()` 経由で通す                 |
| `app/Http/Requests/`         | FormRequest によるバリデーション                                 |
| `app/Jobs/`                  | 非同期処理（メール送信、決済後処理、集計など）                   |
| `resources/js/Pages/`        | Inertia ページコンポーネント                                     |
| `resources/js/api/`          | フロントからのAPI呼び出しはここに集約                            |
| `resources/js/Hooks/`        | 副作用・データ取得フック                                         |

## データ層

- 単一データベース・共有テーブル
- 全テナント依存テーブルに `tenant_id` カラムを持つ
- Eloquent Global Scope が `WHERE tenant_id = ?` を自動付与
- 複合ユニーク制約は `(tenant_id, ...)` で定義

## 認証と権限

- 認証方式は **Laravel Sanctum 4.0 に統一**
- セッション方式で Inertia からアクセス
- ロール: `tenant_admin` / `tenant_staff` / `customer`
- 権限判定は **Policy 必須**。Controller に `if` で書かない

## 決済

- fincode との通信は `app/Services/Payment/` 配下に集約
- 注文確定は **Webhook** で行う（フロントのステータス遷移では確定させない）
- 詳細は [Explanation: 決済フロー](../explanation/payment-flow.md)

## キュー

- Redis をキューブローカーとして使用
- 非同期ジョブは `php artisan queue:work` で消費
- テナント別の処理でも `tenant_id` をジョブペイロードに含める

## ディレクトリ早見

```
app/
├── Http/
│   ├── Controllers/    (薄い)
│   ├── Middleware/
│   └── Requests/       (FormRequest)
├── Models/             (Eloquent + Global Scope)
├── Policies/           (権限)
├── Services/           (ビジネスロジック)
├── Jobs/               (非同期)
└── Support/            (ヘルパ)
resources/js/
├── Pages/              (Inertia ページ)
├── Layouts/            (画面レイアウト)
├── Components/         (再利用 UI)
├── Hooks/              (副作用/データ取得)
└── api/                (API 呼び出し集約)
```
