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

-

### Changed

-

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
