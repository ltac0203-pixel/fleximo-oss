# How-to: シークレットをローテーションする

このガイドは、Fleximo の各種シークレット（API キー・パスワード・署名鍵）を安全に差し替える手順をまとめたものです。

## ゴール

- 漏洩・退職・定期ローテーション等の理由で、稼働中のシークレットを無停止に近い形で入れ替えられる
- 旧シークレットの失効と新シークレットの有効化が確実に同期する

## 共通の流れ

ローテーションは原則として **「新しい鍵を発行 → アプリ側で受け入れ可能にする → 旧鍵を失効」** の順で進めます。順序を逆にすると決済・ログインなどが落ちます。

```
[発行]  → [.env 更新]  → [プロセス再起動]  → [動作確認]  → [旧鍵失効]
```

> **本番でシークレットを変更したら必ず PHP-FPM とキューワーカーを再起動してください**。`config:cache` 済みの環境では再起動なしに新しい値が反映されません。

```bash
php artisan config:clear
php artisan config:cache
sudo systemctl reload php8.3-fpm
sudo systemctl restart fleximo-queue.service
```

---

## 1. fincode の API キー

### いつローテートするか

- 漏洩の疑いがあるとき（GitHub にコミット、ログに出力、退職者が知っている等）
- fincode 側で「キーの再発行」を実施したとき
- 定期ローテーションの社内ポリシーに従うとき

### 手順

1. **fincode 管理画面で新しい API キーを発行する**
   - シークレットキーとパブリックキー（公開鍵）の両方が必要
   - 旧キーは「すぐには無効化しない」ことを確認

2. **`.env` を更新する**

   ```env
   FINCODE_API_KEY=<new-secret-key>
   VITE_FINCODE_PUBLIC_KEY=<new-public-key>
   ```

   `VITE_FINCODE_PUBLIC_KEY` は **Vite がビルド時にバンドルする** ため、`.env` を書き換えただけでは反映されません。必ずフロントを再ビルドしてください。

   ```bash
   npm ci
   npm run build
   ```

3. **アプリを再起動して反映**

   ```bash
   php artisan config:clear && php artisan config:cache
   sudo systemctl reload php8.3-fpm
   sudo systemctl restart fleximo-queue.service
   ```

4. **テスト決済で確認**
   - テストカードで顧客側から注文 → `PAID` になる
   - `storage/logs/laravel.log` に `[fincode]` 由来のエラーが出ない

5. **fincode 管理画面で旧キーを無効化**

### 注意点

- `FINCODE_SHOP_ID` は通常変わらない（運営者のショップ ID）。テナント別の `fincode_shop_id` も変える必要はない
- ブラウザのキャッシュに古い `assets/*.js` が残っていると旧 public key が使われ続けるため、必ず `npm run build` 後にデプロイし、CDN を使っている場合はキャッシュ purge を行う

---

## 2. fincode の Webhook 署名シークレット

### 手順

1. **fincode 管理画面で Webhook を再登録または「シークレット再発行」を行う**
   - 旧シークレットでの署名は即座に検証エラーになるため、できるだけアプリ側の更新と同時に行う
2. **新しいシークレットを `.env` に設定**

   ```env
   FINCODE_WEBHOOK_SECRET=<new-webhook-secret>
   ```

3. **アプリ再起動**（共通の流れに従う）

4. **Webhook が受理されているか確認**

   ```bash
   tail -f storage/logs/laravel.log | grep -i fincode
   ```

   テスト決済を 1 件流して `webhook received` のログが出ることを確認します。

> **`FINCODE_WEBHOOK_SECRET` を空にしないでください**。空の場合、Fleximo は Webhook を全て拒否します（署名検証必須のため）。

---

## 3. データベースのパスワード

### 手順

1. **新しいユーザーを発行 / 既存ユーザーのパスワードを更新**

   推奨は「新ユーザーを並行運用 → 旧ユーザー削除」の順。MariaDB の例:

   ```sql
   -- 新規ユーザー発行
   CREATE USER 'fleximo_new'@'127.0.0.1' IDENTIFIED BY '<strong-password>';
   GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, INDEX, DROP, REFERENCES, EXECUTE
     ON fleximo.* TO 'fleximo_new'@'127.0.0.1';
   FLUSH PRIVILEGES;
   ```

2. **`.env` を更新**

   ```env
   DB_USERNAME=fleximo_new
   DB_PASSWORD=<strong-password>
   ```

3. **アプリ再起動**

4. **動作確認**
   - 顧客側ページが表示される
   - `php artisan tinker` で `DB::select('select 1')` が通る

5. **旧ユーザー削除**

   ```sql
   DROP USER 'fleximo_old'@'127.0.0.1';
   ```

> **既存ユーザーのパスワード変更だけで済ます場合**は、SQL 実行と `.env` 更新の間にダウンタイムが発生します。短時間ならメンテナンスモード `php artisan down` を挟むのが確実です。

---

## 4. Redis のパスワード

Redis は本番では認証必須です（`requirepass` を設定）。

### 手順

1. **`/etc/redis/redis.conf` で `requirepass <new>` を更新し、`systemctl restart redis-server`**
   - 再起動の瞬間にセッション・キャッシュが揮発する点に注意
   - キューに残っているジョブは消える可能性があるため、事前にメンテナンスモードに入れることを推奨
2. **`.env` を更新**

   ```env
   REDIS_PASSWORD=<new-password>
   ```

3. **アプリ再起動**

4. **動作確認**
   - ログイン状態が初期化されるので、テストアカウントで再ログインできることを確認

---

## 5. APP_KEY

`APP_KEY` は **暗号化に使う対称鍵** です。Laravel は以下を APP_KEY で暗号化します:

- セッション Cookie
- `Crypt::encrypt()` で暗号化したカラム
- 一部のキャッシュ
- パスワードリセットトークン等の署名 URL

> **APP_KEY を不用意に変更すると、既存のセッション・暗号化済みデータが復号できなくなります**。漏洩時など本当に必要なときだけ実施してください。

### いつローテートするか

- `APP_KEY` がリポジトリにコミットされた、ログに出た等の漏洩時
- 退職者が `.env` を持ち出した可能性があるとき

### 手順

1. **暗号化されたカラムが DB にあるか確認**
   - 通常、Fleximo では機密データは fincode 側に保管され、ローカル DB に `Crypt::encrypt()` の結果は保存していません
   - もし独自に暗号化カラムを追加していれば、ローテーション前に **旧鍵で復号 → 新鍵で再暗号化** のスクリプトが必要です

2. **新しい鍵を生成（コピーだけ。`.env` には**まだ**書き込まない）**

   ```bash
   php artisan key:generate --show
   # base64:xxxxxxxx... が出る
   ```

3. **メンテナンスモードに入る**

   ```bash
   php artisan down --retry=60
   ```

4. **`.env` の `APP_KEY` を新しい値で上書き**

5. **キャッシュ全消去・再構築・再起動**

   ```bash
   php artisan config:clear
   php artisan cache:clear
   php artisan config:cache
   sudo systemctl reload php8.3-fpm
   sudo systemctl restart fleximo-queue.service
   ```

6. **メンテナンスモード解除**

   ```bash
   php artisan up
   ```

7. **動作確認**
   - 全ユーザーが強制ログアウトされる（セッションが復号できないため）
   - 新規ログインが正常にできる
   - パスワードリセットメール等の署名 URL が機能する

---

## 6. SMTP / メール送信のパスワード

### 手順

1. メール配信プロバイダで SMTP パスワードを再発行
2. `.env` を更新

   ```env
   MAIL_USERNAME=...
   MAIL_PASSWORD=...
   ```

3. アプリ再起動
4. テストメールを送信して到達確認

   ```bash
   php artisan tinker
   >>> Mail::raw('rotation test', fn($m) => $m->to('you@example.com')->subject('test'));
   ```

---

## チェックリスト

ローテーション後、以下を必ず確認してください。

- [ ] `.env` のバックアップを更新した（[バックアップ手順](./backup-and-restore.md) 参照）
- [ ] 旧シークレットを発行元（fincode 管理画面、SMTP プロバイダ等）で **失効** した
- [ ] Git リポジトリ・チャットツール・ドキュメント等に旧シークレットが残っていないか確認した
- [ ] 漏洩起因のローテーションの場合、関係者・必要なら fincode サポートに連絡した

## 関連

- [環境変数リファレンス](../reference/configuration.md)
- [fincode 決済を設定する](./configure-fincode.md)
- [バックアップとリストア](./backup-and-restore.md)
- [本番環境にデプロイする](./deploy-production.md)
