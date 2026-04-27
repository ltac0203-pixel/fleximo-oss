# Fleximo

[English README](./README.md)

[![License: Apache 2.0](https://img.shields.io/badge/License-Apache_2.0-blue.svg)](./LICENSE)
[![Status: MVP](https://img.shields.io/badge/status-MVP-orange)](#)
[![PHP](https://img.shields.io/badge/PHP-%5E8.3-777BB4?logo=php&logoColor=white)](https://www.php.net/)
[![Laravel](https://img.shields.io/badge/Laravel-12-FF2D20?logo=laravel&logoColor=white)](https://laravel.com/)
[![React](https://img.shields.io/badge/React-19-61DAFB?logo=react&logoColor=black)](https://react.dev/)

**Fleximo** は、**日本の飲食店・学食向けオープンソース・マルチテナント型モバイルオーダープラットフォーム**です。
飲食店はレジ待ちなしでテイクアウト注文・決済をウェブ上で受け付けられ、利用者は単一アカウントでプラットフォーム上の全店舗を利用できます。

> **これは「プラットフォーム型」のソフトウェアです** — 1つのデプロイ環境を1運営者が立ち上げ、その上で複数の飲食店テナントを束ねる前提で設計されています。店舗ごとに個別ホスティングする「単店舗型 SaaS」とは別物で、マルチテナントDB・プラットフォーム管理者・fincode のプラットフォームタイプ加盟店契約を前提とした構造になっています。詳細は [`docs/explanation/multi-tenancy.md`](./docs/explanation/multi-tenancy.md) を参照。

![Fleximo — 飲食店・学食向けモバイルオーダー](./docs/assets/hero.png)

> 稼働中のリファレンス環境: **https://fleximo.jp**
> ステータス: **MVP / 初期OSS**。API・スキーマは変更される可能性があります。本番利用時はリリースタグに固定することを推奨します。

## スクリーンショット

| 顧客向けLP (https://fleximo.jp) | 事業者向けページ |
| --- | --- |
| ![顧客向けLP](./docs/assets/hero.png) | ![事業者向けページ](./docs/assets/for-business.png) |

フルページキャプチャ: [`docs/assets/landing-full.png`](./docs/assets/landing-full.png)

---

## なぜ Fleximo か

日本国内のモバイルオーダー系SaaSはクローズド・店舗単位課金・小規模店には割高、というものが多いのが現状です。Fleximo は次の方針を取ります。

- **セルフホスト可能** — Xserver や VPS など自前サーバーで運用でき、決済手数料以外の従量課金はありません。
- **マルチテナント前提** — 1つのデプロイで複数店舗を運営でき、顧客は1アカウントで全店舗を利用できます。
- **日本市場向けに設計** — fincode（クレジットカード / PayPay）、日本語UI、税込価格、テイクアウト優先フロー。
- **枯れた技術スタック** — Laravel + React + MySQL/MariaDB + Redis。

---

## 機能（MVP）

- テナント（飲食店）管理 / テナント管理者・スタッフのログイン
- 顧客向けの横断店舗検索
- メニュー閲覧 / テナント単位のカート
- **fincode** 決済（クレジットカード・PayPay）、Webhookで確定
- スタッフ向け KDS（Kitchen Display System）
- テナント別の分析ダッシュボード
- MVP では**テイクアウト専用**（イートイン・デリバリーは対象外）

### 注文ステータス

```
PENDING_PAYMENT → PAID → ACCEPTED → IN_PROGRESS → READY → COMPLETED
```

フロントのステータス変化だけで注文を確定することはありません。**fincode の Webhook を正とします。**

### 主要ロール

| ロール         | 権限                                                                   |
| -------------- | ---------------------------------------------------------------------- |
| `tenant_admin` | メニュー・スタッフ・注文・決済設定・ダッシュボード管理                 |
| `tenant_staff` | KDS での注文対応、ステータス更新                                       |
| `customer`     | 店舗検索・注文・決済・注文履歴閲覧（全テナント共通アカウント）         |

---

## マルチテナント設計

- **単一DB・共有テーブル**で `tenant_id` による行分離
- Eloquent Global Scope + Policy + DB制約でクロステナントアクセスを防止
- Redis キー・キュージョブ・ストレージパスにも `tenant_id` を含める

詳細は [`docs/reference/architecture.md`](./docs/reference/architecture.md) と [`docs/explanation/multi-tenancy.md`](./docs/explanation/multi-tenancy.md) を参照してください。

---

## 技術スタック

| レイヤー           | 技術                                           |
| ------------------ | ---------------------------------------------- |
| バックエンド       | Laravel 12 (PHP ^8.3)                          |
| フロントエンド     | React 19 + Inertia.js 2 + TypeScript 5         |
| データベース       | MariaDB / MySQL                                |
| キャッシュ・キュー | Redis                                          |
| 認証               | Laravel Sanctum 4                              |
| 決済               | fincode（クレジットカード・PayPay）            |
| スタイリング       | Tailwind CSS 3                                 |
| ビルド             | Vite 7                                         |
| UIライブラリ       | @headlessui/react, recharts, @dnd-kit          |
| エラー監視         | Sentry                                         |

---

## クイックスタート（ローカル開発）

### 必要環境

- PHP ^8.3（`mbstring`, `intl`, `pdo_mysql`, `redis` 拡張）
- Composer 2.x
- Node.js 20+ / npm
- MariaDB 10.6+ または MySQL 8+
- Redis 6+

### セットアップ

```bash
git clone https://github.com/ltac0203-pixel/fleximo-oss.git
cd fleximo-oss

composer install
cp .env.example .env
php artisan key:generate

# .env に DB / Redis / fincode の認証情報を設定

php artisan migrate --seed
npm install
npm run dev
```

別ターミナルで以下を起動します。

```bash
php artisan serve
php artisan queue:listen
```

`http://localhost:8000` を開いてください。

### テスト

```bash
composer test              # PHPUnit
npm run test               # Vitest
npm run test:e2e           # Playwright
vendor/bin/phpstan analyse # 静的解析
vendor/bin/pint            # PHP フォーマッタ
```

> テストは MariaDB/MySQL 上で実行します。SQLite は**サポート対象外**です。

---

## 設定

主要な `.env` 変数（全項目は `.env.example` を参照）。

```env
APP_URL=https://your-domain.example.com
DB_CONNECTION=mysql
REDIS_HOST=127.0.0.1

# fincode（すべての値は fincode 管理画面 https://www.fincode.jp/ から取得）
FINCODE_API_KEY=            # シークレットキー（サーバー側）。ブラウザに露出させない
VITE_FINCODE_PUBLIC_KEY=    # パブリックキー（ブラウザ側）。決済フォームの fincode.js で使用
FINCODE_SHOP_ID=            # プラットフォーム管理者のショップ ID
FINCODE_WEBHOOK_SECRET=     # Webhook 署名検証用の HMAC シークレット

# 法令対応（本番では必ず自社情報に書き換える）
COMPANY_NAME="Your Company Name"
COMPANY_REPRESENTATIVE="Your Representative Name"
COMPANY_POSTAL_CODE="000-0000"
COMPANY_ADDRESS="Your Company Address"
COMPANY_CONTACT_EMAIL=contact@example.com
```

> 参加する各テナントのショップ ID は `.env` ではなく DB（テナントごとに保持）に格納します。プラットフォーム管理者が **管理者 → テナントショップ ID** 画面（`/admin/tenant-shop-ids`）から登録し、API 呼び出し時に `Tenant-Shop-Id` ヘッダとして送信されます。詳細は [`docs/how-to/configure-fincode.md`](docs/how-to/configure-fincode.md) を参照。

> **日本でセルフホストする方へ**: 同梱の特商法表記・プライバシーポリシー等（`/legal/*`）は `config/legal.php` のプレースホルダを表示します。本番公開前に `COMPANY_*` 変数を**必ず**自社情報に書き換えてください。プレースホルダのまま公開すると特定商取引法に違反します。

---

## ディレクトリ構成

```
app/              Laravel 本体（Controller / Service / Model / Policy）
resources/js/     React + Inertia フロントエンド（TypeScript）
routes/           HTTP / Inertia / API ルート
database/         マイグレーション・ファクトリ・シーダー
docs/             Diátaxis に基づくドキュメント（tutorials / how-to / reference / explanation）
tests/            PHPUnit + Vitest + Playwright テスト
```

---

## ロードマップ

- イートイン / デリバリーフローの追加
- 多言語UI（まず EN / JA、続いて KR / ZH）
- 外部POS連携（Square、Airレジ等）
- テナントのセルフサインアップと課金
- 在庫・原価管理

Issue / Pull Request は GitHub で管理します。

---

## コントリビュート

貢献を歓迎します。PR を作成する前に以下をご確認ください。

1. [設計原則](./docs/explanation/design-principles.md) を読み、スコープの線引きを理解する。
2. [コミットメッセージ規約](./docs/how-to/commit-guidelines.md) に従う。
3. `main` への直 push は禁止。PR を作成し、Lint / Test / Build をすべて通過させる。
4. 機密情報（カード番号・CVV・APIキー・個人情報）をログに出力しない。

チュートリアル・How-to・リファレンス・設計背景は [`docs/`](./docs/) を参照してください。

---

## セキュリティ

セキュリティ脆弱性を**公開 Issue で報告しないでください**。
再現手順を添えてメンテナにプライベート連絡（GitHub プロフィール参照）をお願いします。可能な限り速やかに対応し、公表時期を調整します。

---

## ライセンス

**Apache License, Version 2.0** で提供します。詳細は [`LICENSE`](./LICENSE) および [`NOTICE`](./NOTICE) を参照してください。
