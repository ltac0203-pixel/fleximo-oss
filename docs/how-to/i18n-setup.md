# How-to: 表示言語（i18n）を切り替える

Fleximo は **`APP_LOCALE` で運営者が表示言語を一括決定する** モデルを採用しています。エンドユーザーが画面上で言語を切り替える UI は提供しません。これは「セルフホスト型 OSS が、運営者の運用ポリシー（メニュー言語と UI 言語をそろえる、特定地域に最適化する など）に合わせて起動するだけで完結する」設計のためです。

本リポジトリには `ja`（日本語）と `en`（English）の翻訳が同梱されています。追加言語を導入する手順も後述します。

---

## 表示言語を切り替える（運営者向け）

`.env` の `APP_LOCALE` を変更し、設定キャッシュをクリアします。

```bash
# .env
APP_LOCALE=en              # ja または en
APP_FALLBACK_LOCALE=en     # 翻訳が見つからなかった場合のフォールバック
APP_TIMEZONE=Asia/Tokyo    # 注文番号生成・日付表示等で利用
```

```bash
php artisan config:clear
php artisan view:clear
```

切り替わる範囲:

- バリデーションメッセージ（`lang/{locale}/validation.php`）
- 認証関連メッセージ（`lang/{locale}/auth.php`、`passwords.php`）
- 注文完了 / キャンセルメール、メールアドレス確認メール（`lang/{locale}/mail.php`）
- 注文ステータス・決済ステータス・決済方法ラベル（`lang/{locale}/enums.php`）
- フロントエンド: 共通レイアウト、認証画面、エラーページ（`resources/js/i18n/locales/{locale}/*.json`）
- HTML `<html lang>` 属性、`<meta property="og:locale">`、SEO description

**現時点でのスコープ外**（本セッションでは未翻訳。順次対応中）:

- 顧客導線の画面本体（メニュー閲覧・カート・チェックアウト・注文履歴）
- テナントスタッフ / 管理者 / KDS 画面
- onboarding / help コンテンツ

これらは実装上、現在の `APP_LOCALE` に関わらず日本語表示のままです。

---

## 翻訳辞書のディレクトリ構成

```
lang/
├── en/
│   ├── auth.php
│   ├── common.php       # SEO description 等の Blade 用共通キー
│   ├── enums.php        # OrderStatus / PaymentStatus / PaymentMethod
│   ├── mail.php         # OrderCompleted / OrderCancelled / VerifyEmail
│   ├── pagination.php
│   ├── passwords.php
│   └── validation.php   # バリデーション + custom セクション
└── ja/
    └── （同じ構成）

resources/js/i18n/
├── index.ts             # i18next 初期化
└── locales/
    ├── en/
    │   ├── auth.json    # ログイン / 登録 / メール認証
    │   ├── common.json  # ヘッダー / フッター / 共通アクション
    │   ├── customer.json # 顧客導線（次PR で消費）
    │   └── errors.json  # 403/404/419/429/500/503
    └── ja/
        └── （同じ構成）
```

サーバー側は `__('mail.verify_email.subject')` のドット記法で参照します。フロントは `useTranslation('errors')` のように namespace を取り、`t('404.title')` で参照します。

---

## 翻訳キーの命名規約

- **構造**: `<screen_or_module>.<element>.<state>` のドット区切り
- **大文字小文字**: `snake_case`
- **可変部分**: `:placeholder`（PHP 側）または `{{placeholder}}`（i18next）
- **`_html` サフィックス**: 翻訳値に HTML タグを含めて `{!! __('...') !!}` で出力するキーには `_html` を付け、エスケープが必要かをひと目で判別できるようにする

---

## 言語を追加する（例: `fr`）

1. **サーバー辞書を作成**
   ```bash
   cp -r lang/en lang/fr
   # lang/fr/*.php の値を翻訳
   ```

2. **フロント辞書を作成**
   ```bash
   cp -r resources/js/i18n/locales/en resources/js/i18n/locales/fr
   # resources/js/i18n/locales/fr/*.json を翻訳
   ```

3. **コード側のサポートロケールに追加**

   `resources/js/types/common.ts`:
   ```ts
   export type LocaleCode = "ja" | "en" | "fr";
   ```

   `resources/js/i18n/index.ts`:
   ```ts
   const SUPPORTED_LOCALES: readonly LocaleCode[] = ["ja", "en", "fr"] as const;
   ```

   `app/Providers/AppServiceProvider.php`:
   ```php
   $locale = app()->getLocale();
   \Carbon\Carbon::setLocale(in_array($locale, ['ja', 'fr'], true) ? $locale : 'en');
   ```

   `resources/views/app.blade.php` の `og:locale`:
   ```blade
   <meta property="og:locale" content="{{ match (app()->getLocale()) {
       'ja' => 'ja_JP',
       'fr' => 'fr_FR',
       default => 'en_US',
   } }}">
   ```

4. **`.env` を変更してテスト**
   ```
   APP_LOCALE=fr
   APP_FALLBACK_LOCALE=en
   ```

   `php artisan config:clear` の後、画面が翻訳されることを確認します。

---

## エンドユーザー個別の言語切替を実装したい場合（参考）

Fleximo の標準フローではユーザー切替 UI は提供しませんが、独自実装する場合の典型的な拡張ポイント:

- `users` テーブルに `locale` カラムを追加し、ログイン後 middleware で `App::setLocale($user->locale)`
- ヘッダーに `<select>` を置き、 `Accept-Language` または cookie で永続化
- 未ログインユーザーは `Accept-Language` ヘッダから推定

これらは fork または独自パッチでの対応となります。
