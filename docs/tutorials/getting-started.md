# Getting Started — ローカル環境で初めての注文を通す

このチュートリアルを終えると、あなたのマシンで Fleximo が起動し、デモテナントのメニューから
テストカード決済で注文を1件成立させた状態になります。所要時間は約30分です。

> このチュートリアルは**学習**のためのものです。本番構築の手順ではありません。本番デプロイは
> [How-to: 本番環境にデプロイする](../how-to/deploy-production.md) を参照してください。

## 前提環境

- PHP 8.3 以上（`mbstring`, `intl`, `pdo_mysql`, `redis` 拡張）
- Composer 2.x
- Node.js 20 以上
- MariaDB 10.6+ または MySQL 8+
- Redis 6+
- fincode のテスト環境アカウント（無料で作成可）

## 1. リポジトリを取得する

```bash
git clone https://github.com/ltac0203-pixel/fleximo-oss.git
cd fleximo-oss
```

## 2. 依存をインストールする

```bash
composer install
npm install
```

## 3. 環境ファイルを用意する

```bash
cp .env.example .env
php artisan key:generate
```

`.env` を開き、DB・Redis・fincode の値を埋めます。fincode はこの段階ではテスト環境キーで構いません。

## 4. データベースを初期化する

```bash
php artisan migrate --seed
```

シーダーによってデモテナント1件と商品・顧客アカウントが用意されます。

## 5. 開発サーバーを起動する

別々のターミナルで以下を並行起動します。

```bash
# Terminal 1
php artisan serve

# Terminal 2
php artisan queue:listen

# Terminal 3
npm run dev
```

## 6. 注文を通してみる

1. ブラウザで `http://localhost:8000` を開く
2. シーダーで作られた顧客アカウントでログインする（認証情報は `database/seeders/` を参照）
3. デモテナントのメニューから商品を1点カートに追加する
4. 決済画面で fincode のテストカード番号 `4111-1111-1111-1111` を入力して決済する
5. 別タブでテナント管理者としてログインし、KDS 画面で注文が `PAID → ACCEPTED` に遷移できることを確認する

## 次に読むもの

- [アーキテクチャ概要](../reference/architecture.md) — どのレイヤーで何が起きているか
- [決済フロー](../explanation/payment-flow.md) — なぜ Webhook が注文確定の正なのか
