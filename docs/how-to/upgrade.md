# How-to: アップグレードする

このガイドは、本番運用中の Fleximo を新しいリリースタグに更新する手順をまとめたものです。
リリース運用そのもの（タグの切り方）は [`release.md`](./release.md) を参照してください。

## ゴール

- 本番環境を新しいリリースタグに安全に上げられる
- 失敗してもダンプから戻せる状態を保てる

## 前提

- 現在 `vX.Y.Z` のタグでデプロイされている（`git describe --tags` で確認）
- [バックアップ手順](./backup-and-restore.md) に沿って DB / `.env` / `storage/app/public` のバックアップを取得済み
- ステージング環境で同じバージョンの動作確認が済んでいる

## SemVer の読み方

| バンプ            | 例                | 想定される作業                                     |
| ----------------- | ----------------- | -------------------------------------------------- |
| PATCH（z 上昇）   | v0.2.0 → v0.2.1   | バグ修正のみ。基本は依存更新もなしで反映可能       |
| MINOR（y 上昇）   | v0.2.1 → v0.3.0   | 機能追加。マイグレーションや新規 ENV が増える可能性 |
| MAJOR（x 上昇）   | v0.x → v1.0       | 破壊的変更。CHANGELOG と Migration Guide を必読   |

`0.x.y` 期間中は MINOR バンプで破壊的変更を吸収します（[`release.md`](./release.md) 参照）。**MINOR でも CHANGELOG の `Breaking` セクションを必ず確認してください**。

## 手順

### 1. 事前確認

```bash
# 現在のバージョンを確認
cd /var/www/fleximo-oss
git fetch --tags
git describe --tags

# 上げる先のリリースノートを読む
gh release view v0.3.0
```

CHANGELOG の以下を必ず確認します。

- **Breaking** — 破壊的変更（設定やコードの修正が必要）
- **新規 ENV** — `.env` への追記が必要
- **Migration** — 新しいマイグレーションファイルの有無

### 2. バックアップ

詳細は [バックアップ手順](./backup-and-restore.md)。最低限、DB ダンプと `.env` のスナップショットを取得します。

```bash
DEST=/var/backups/fleximo
mkdir -p "$DEST"

# DB
set -a; source .env; set +a
mysqldump --single-transaction --quick --routines --triggers \
  --default-character-set=utf8mb4 \
  -u "$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" \
  | gzip > "$DEST/pre-upgrade-$(date +%Y%m%d-%H%M%S).sql.gz"

# .env
install -m 600 .env "$DEST/pre-upgrade-$(date +%Y%m%d-%H%M%S).env"

# 現在のコミット SHA（戻すときに使う）
git rev-parse HEAD > "$DEST/pre-upgrade-$(date +%Y%m%d-%H%M%S).sha"
```

### 3. メンテナンスモードに入る

```bash
php artisan down --render="errors::503" --retry=60
```

`--secret=...` を付けると、運用者だけが裏でアクセスして動作確認できます。

```bash
php artisan down --secret=preview-token --retry=60
# https://your-domain/preview-token にアクセスすると通常画面に入れる
```

### 4. キューワーカーを止める

走行中のジョブが旧バージョンのコードに依存している可能性があるため、停止して入れ替えます。

```bash
sudo systemctl stop fleximo-queue.service
# キューが捌けるのを待つ場合: php artisan queue:work --once などで状況確認
```

### 5. 新しいタグをチェックアウト

```bash
git fetch --tags
git checkout v0.3.0
```

> **本番に対して `git pull origin main` を直接行わないでください**。常にリリースタグに固定することで、未リリースのコミットが入り込むのを防げます。

### 6. 依存をインストールしてビルド

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
```

### 7. `.env` に新規変数を追記

CHANGELOG で追加されている `.env` 変数があれば、ここで追記します。
[環境変数リファレンス](../reference/configuration.md) も合わせて確認してください。

### 8. マイグレーション

```bash
php artisan migrate --force
```

> **本番では `migrate:fresh` / `migrate:refresh` / `db:wipe` を絶対に実行しないでください**（CLAUDE.md 参照）。スキーマ変更は新規マイグレーションのみで前進させます。

### 9. キャッシュ再構築

```bash
php artisan config:clear
php artisan route:clear
php artisan view:clear

php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 10. キューワーカー再起動とメンテナンス解除

```bash
sudo systemctl start fleximo-queue.service
php artisan up
```

### 11. 動作確認

[`deploy-production.md`](./deploy-production.md) の「動作確認」と同じ項目を一通り確認します。

- 顧客側で注文 → 決済 → KDS に流れる
- テナント管理画面でメニューが表示される
- `storage/logs/laravel.log` にスタックトレースが出ていない

## ロールバック

新バージョンで問題が出た場合は、原則として **コードを戻して DB を復元** します。

### コードのみで戻せるケース

マイグレーションを伴わないリリース（PATCH リリースの一部）であれば、コードだけ戻せば動きます。

```bash
php artisan down
git checkout v0.2.1   # 旧バージョンのタグ
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan config:cache && php artisan route:cache && php artisan view:cache
sudo systemctl restart fleximo-queue.service
php artisan up
```

### マイグレーションを伴うケース

新バージョンで `ALTER TABLE` などが走っている場合、コードだけ戻すとアプリが新スキーマを期待しないテーブルにアクセスして壊れます。**事前ダンプから DB を復元するのが安全**です。

手順は [バックアップとリストア](./backup-and-restore.md#4-リストア) を参照してください。

> **`php artisan migrate:rollback` は安易に使わないでください**。down マイグレーションが意図通りに動かないケース（カラム削除、データ変換等）があり、本番で打つと不可逆な状態を作る恐れがあります。ダンプからの復元を第一選択にしてください。

## メジャーアップグレード（v0.x → v1.0、v1.x → v2.x）

メジャーアップグレードでは追加の手順が必要になることがあります。リリースノートに **Migration Guide** が記載されている場合は、必ずそれを先に読んでください。

一般的な追加チェックポイント:

- 削除された ENV 変数 / 機能フラグ
- DB スキーマの非互換変更
- 外部 API（fincode 等）のバージョン要件変更
- 必要な PHP / Node.js / MariaDB のバージョン変更

メジャーアップグレードはステージング環境で 1 度通してから本番に適用してください。

## 関連

- [リリースを作成する](./release.md)
- [本番環境にデプロイする](./deploy-production.md)
- [バックアップとリストア](./backup-and-restore.md)
- [シークレットをローテーションする](./rotate-secrets.md)
- [`CHANGELOG.md`](../../CHANGELOG.md)
