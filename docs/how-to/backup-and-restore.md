# How-to: バックアップとリストア

このガイドは、本番運用中の Fleximo を **「データを失わずに復旧できる」状態** に保つための最低限の手順をまとめたものです。

## ゴール

- データベースを定期的にダンプし、別ストレージに退避できる
- アップロード画像（`storage/app/public/`）も含めてバックアップできる
- 障害発生時にダンプから直近の状態に戻せる

## バックアップ対象

| 対象                  | 保存場所                                  | 重要度 | 備考                                                 |
| --------------------- | ----------------------------------------- | ------ | ---------------------------------------------------- |
| データベース          | MariaDB / MySQL                           | 必須   | 注文・決済・テナント設定など全ての永続データ         |
| アップロード画像      | `storage/app/public/tenants/{tenant_id}/` | 必須   | メニュー画像。失うと再登録が必要                     |
| `.env`                | プロジェクト直下                          | 必須   | 認証情報を含む。Git では管理されていない             |
| Redis                 | メモリ（永続化任意）                      | 任意   | キャッシュ・セッション・キュー。失っても再構築可能   |
| `storage/logs/`       | プロジェクト配下                          | 任意   | ログ。監査要件があれば保管                           |

> **Redis のキューに未処理ジョブが残っている場合の注意**: ダンプ取得時にキューを止めないと、復旧後に同じジョブが二重処理される可能性があります。長時間ジョブが詰まっているサーバーでは、バックアップ前に `php artisan queue:pause`（または `queue:work` プロセス停止）を検討してください。

## 1. データベースのバックアップ

### 手動ダンプ

```bash
mysqldump \
  --single-transaction \
  --quick \
  --routines \
  --triggers \
  --default-character-set=utf8mb4 \
  -u "$DB_USERNAME" -p"$DB_PASSWORD" \
  "$DB_DATABASE" \
  | gzip > "/var/backups/fleximo/db-$(date +%Y%m%d-%H%M%S).sql.gz"
```

- `--single-transaction` で InnoDB のテーブルロックを避け、サービス継続中に取得可能
- `gzip` で 1/5〜1/10 程度に圧縮される

### cron で日次ダンプ

`/etc/cron.d/fleximo-backup` に登録します。

```cron
# 毎日 03:00 に DB をダンプし、14 世代を残す
0 3 * * * fleximo /usr/local/bin/fleximo-backup-db.sh >> /var/log/fleximo-backup.log 2>&1
```

`/usr/local/bin/fleximo-backup-db.sh`:

```bash
#!/usr/bin/env bash
set -euo pipefail

cd /var/www/fleximo-oss
set -a; source .env; set +a

DEST=/var/backups/fleximo
mkdir -p "$DEST"

mysqldump \
  --single-transaction --quick --routines --triggers \
  --default-character-set=utf8mb4 \
  -u "$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" \
  | gzip > "$DEST/db-$(date +%Y%m%d).sql.gz"

# 14 日より古いものを削除
find "$DEST" -name 'db-*.sql.gz' -mtime +14 -delete
```

権限と所有者に注意してください（`.env` を読むのでアプリと同じユーザーで実行）。

### オフサイト保管

ローカルディスクのみのバックアップは、サーバー丸ごと飛ぶと役に立ちません。S3 互換ストレージなど別ロケーションに転送することを強く推奨します。

```bash
aws s3 cp "/var/backups/fleximo/db-$(date +%Y%m%d).sql.gz" s3://your-bucket/fleximo/db/
```

## 2. アップロード画像のバックアップ

`storage/app/public/` 配下を rsync または tar で退避します。

```bash
tar -czf "/var/backups/fleximo/storage-$(date +%Y%m%d).tar.gz" \
  -C /var/www/fleximo-oss storage/app/public
```

差分転送なら rsync が効率的です。

```bash
rsync -av --delete \
  /var/www/fleximo-oss/storage/app/public/ \
  /mnt/backup/fleximo/storage/
```

## 3. `.env` のバックアップ

平文の認証情報が含まれるため、**世代管理付きで保管しつつ、保管先のアクセス権限を厳格に**設定してください。

```bash
install -m 600 /var/www/fleximo-oss/.env "/var/backups/fleximo/env-$(date +%Y%m%d).env"
```

可能なら GPG 等で暗号化してから外部に転送してください。

```bash
gpg --symmetric --cipher-algo AES256 -o "/var/backups/fleximo/env-$(date +%Y%m%d).env.gpg" \
  /var/www/fleximo-oss/.env
```

## 4. リストア

### データベース

> **本番 DB に対して `migrate:fresh` / `migrate:refresh` / `db:wipe` を実行しないでください**（CLAUDE.md 参照）。リストアはダンプの取り込みで行います。

ダンプを取り込む先のスキーマが既存の場合、空にしてから流し込みます。本番では新規 DB を作成してそちらに流し込み、アプリが向ける DB を切り替える運用が安全です。

```bash
# 1) リストア先 DB を作成（既存 DB は触らない）
mysql -u root -p -e \
  "CREATE DATABASE fleximo_restore CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 2) ダンプを流し込む
gunzip -c /var/backups/fleximo/db-20260420.sql.gz \
  | mysql -u root -p fleximo_restore

# 3) 動作確認後、.env の DB_DATABASE を切り替えてアプリを再起動
#    旧 DB はしばらく残しておき、問題がなければ後日削除
```

### アップロード画像

```bash
tar -xzf /var/backups/fleximo/storage-20260420.tar.gz -C /var/www/fleximo-oss/
php artisan storage:link  # public/storage が壊れていれば貼り直す
```

### `.env`

```bash
gpg -d /var/backups/fleximo/env-20260420.env.gpg > /var/www/fleximo-oss/.env
chmod 600 /var/www/fleximo-oss/.env
```

その後 PHP-FPM とキューワーカーを再起動して反映します。

```bash
sudo systemctl reload php8.3-fpm
sudo systemctl restart fleximo-queue.service
```

## 5. リストアの動作確認

復旧後は最低限以下を確認してください。

- 顧客側のトップページが表示される
- 既存テナントのメニュー画像が表示される（`/storage/tenants/...` への 200 応答）
- テスト注文が決済まで通る（実際の課金が走らないようテスト環境で先に検証）
- KDS に注文が流れる
- `storage/logs/laravel.log` にスタックトレースが出ていない

## 6. リハーサルの推奨

バックアップは取れているのにリストアが失敗する事例は珍しくありません。**四半期に 1 回はステージング環境で実際にリストアを実行**して、所要時間と手順の正確さを確認してください。

## 関連

- [本番環境にデプロイする](./deploy-production.md)
- [シークレットをローテーションする](./rotate-secrets.md)
- [アップグレード手順](./upgrade.md)
