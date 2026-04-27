# Changelog

本ファイルは Fleximo の主な変更点を記録します。フォーマットは [Keep a Changelog](https://keepachangelog.com/ja/1.1.0/) に準拠し、バージョニングは [Semantic Versioning](https://semver.org/lang/ja/) に従います。

セクション種別:

- `Added` 新機能
- `Changed` 既存機能の変更
- `Deprecated` 将来削除予定の機能
- `Removed` 削除された機能
- `Fixed` バグ修正
- `Security` 脆弱性修正

## [Unreleased]

### Added

- 多言語化（i18n）基盤を導入。`APP_LOCALE` で `ja` / `en` を切り替え可能に。バリデーションメッセージ・認証メッセージ・メール認証・Enum ラベル（OrderStatus / PaymentStatus / PaymentMethod）・フロント共通レイアウト・認証画面・エラーページ（403/404/419/429/500/503）を `lang/{ja,en}/` および `resources/js/i18n/locales/{ja,en}/` で多言語化。`HandleInertiaRequests` が `locale` / `fallbackLocale` を共有し、`<html lang>` と `<meta og:locale>` も locale に追従。詳細は [`docs/how-to/i18n-setup.md`](./docs/how-to/i18n-setup.md)
- `APP_TIMEZONE` 環境変数を追加（`config/app.php` の `timezone` を env 化）。`OrderNumberGenerator` の `Asia/Tokyo` ハードコードも `config('app.timezone')` 参照に変更
- `lang/` ディレクトリ（`php artisan lang:publish` で生成された英語版を起点に日本語版を整備）
- `resources/js/i18n/` ディレクトリ（`react-i18next` + `i18next-resources-to-backend` による Vite 動的 import 対応）

### Changed

- `TenantStatsRepository` を `app/Services/Stats/Queries/` 配下の 8 つの Query Object に分解するファサードに縮小（398 行 → 105 行）。`TenantDashboardService::getCustomerInsights` の生 Eloquent クエリも `CustomerInsightsQuery` に抽出。public シグネチャは完全維持
- `TenantDashboardService` のキャッシュキー組み立てを `DashboardCacheKeys` に、TTL 判定を `StatsCacheResolver` に分離。`Cache::remember` 直接呼び出しをゼロ化し、キー 7 本とTTL は完全保持（ハードコード比較テストで検証）
- 顧客導線（メニュー閲覧・カート・チェックアウト・注文履歴）/ テナントスタッフ画面 / 管理画面 / KDS / onboarding は **次 PR 以降で段階的に翻訳化**。本 PR では基盤と共通レイアウト・Auth・Errors のみが ja/en 切替に対応する（`resources/js/i18n/locales/{ja,en}/customer.json` は次 PR で消費する scaffolding として同梱）

### Removed

- 注文完了時・注文キャンセル時の顧客向けメール送信（`OrderCompletedMail` / `OrderCancelledMail` および `SendOrderNotificationEmail` リスナー、対応する Blade テンプレートと `lang/{ja,en}/mail.php` の翻訳キーを削除）
- 不審ログイン検知時のメール通知（`SuspiciousLoginNotification` を削除）。`LoginAnomalyDetector` による検知と AuditLog への記録は引き続き有効。あわせて `LoginAnomalyDetector` から通知クールダウン関連の `shouldNotify` / `markNotified` / `markNotifiedAll` メソッドと `config/login_anomaly.php` の `notification_cooldown_minutes` を削除

### Fixed

-

### Security

-

## [0.1.0] - 2026-04-18

初回 OSS リリース（MVP）。

### Added

- マルチテナント型モバイルオーダー基盤（テナント / テナント管理者 / テナントスタッフ / 顧客）
- 顧客向け横断店舗検索とテナント単位カート
- fincode 決済（クレジットカード・PayPay）と Webhook による注文確定
- スタッフ向け KDS（Kitchen Display System）
- テナント別の分析ダッシュボード
- 特商法表記・プライバシーポリシー等のテンプレート（`config/legal.php`）
- OSS 基本ドキュメント: `CONTRIBUTING.md`, `CODE_OF_CONDUCT.md`（Contributor Covenant v2.1 準拠）, `SECURITY.md`
- README にバッジ・Hero 画像・スクリーンショット（`https://fleximo.jp` 実環境キャプチャ）を追加
- `docs/assets/` ディレクトリと画像ファイル命名規則

### Changed

- **BREAKING**: 最小 PHP 要件を `^8.2` から `^8.3` に引き上げ（PHPUnit 12 への更新に伴うもの）
- PHPUnit を `^11.5.3` から `^12.5.22` に更新
- CI (test / coverage workflow) の `php-version` を `8.3` に変更

### Security

- Dependabot alert #20 (PHPUnit Argument injection via newline in PHP INI values / GHSA) を解消
- Dependabot alert #5, #6 (picomatch Method Injection in POSIX Character Classes / GHSA) を解消

[Unreleased]: https://github.com/ltac0203-pixel/fleximo-oss/compare/v0.1.0...HEAD
[0.1.0]: https://github.com/ltac0203-pixel/fleximo-oss/releases/tag/v0.1.0
