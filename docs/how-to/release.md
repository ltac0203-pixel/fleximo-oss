# How-to: リリースを作成する

Fleximo は **Git タグを真実の源（SSoT）** とするリリース管理を採用しています。`composer.json` / `package.json` には `version` フィールドを置きません。

- バージョニング: [Semantic Versioning](https://semver.org/lang/ja/) (`vMAJOR.MINOR.PATCH`)
- 変更履歴: [`CHANGELOG.md`](../../CHANGELOG.md)（[Keep a Changelog](https://keepachangelog.com/ja/1.1.0/) 形式）
- 自動化: タグを push すると GitHub Actions (`.github/workflows/release.yml`) が GitHub Release を自動作成します

## SemVer の使い分け

MVP 期間は `0.x.y` を維持します（破壊的変更を許容しやすくするため）。

| 種別      | バンプ対象 | 例              | 該当する変更                                     |
| --------- | ---------- | --------------- | ------------------------------------------------ |
| MAJOR     | `x` (0.x)  | 0.1.0 → 0.2.0   | 0.x 期間中の破壊的変更（API 互換性なし、DB 不可逆等） |
| MINOR     | `y` (0.x.y)| 0.1.0 → 0.1.1   | 機能追加・後方互換のある変更                     |
| PATCH     | -          | -               | 0.x 期間中はバンプ対象を MINOR に集約            |

1.0.0 到達後は通常の SemVer（MAJOR = 破壊的変更、MINOR = 機能追加、PATCH = バグ修正）に切り替えます。

## リリース手順

### 1. リリース対象の確認

`main` ブランチが緑（CI 通過済み）であることを確認します。

```bash
git checkout main
git pull origin main
```

### 2. CHANGELOG.md を更新

`[Unreleased]` セクションに今回のリリースで含まれる変更を集約してから、新しいバージョン枠に切り出します。

```diff
-## [Unreleased]
+## [Unreleased]
+
+### Added
+
+-
+
+## [0.2.0] - 2026-04-25

 ### Added

 - KDS にドラッグ操作で並び替えできる機能を追加
```

下部の比較リンクも更新します。

```diff
-[Unreleased]: https://github.com/ltac0203-pixel/fleximo-oss/compare/v0.1.0...HEAD
-[0.1.0]: https://github.com/ltac0203-pixel/fleximo-oss/releases/tag/v0.1.0
+[Unreleased]: https://github.com/ltac0203-pixel/fleximo-oss/compare/v0.2.0...HEAD
+[0.2.0]: https://github.com/ltac0203-pixel/fleximo-oss/compare/v0.1.0...v0.2.0
+[0.1.0]: https://github.com/ltac0203-pixel/fleximo-oss/releases/tag/v0.1.0
```

### 3. リリース PR を作成

```bash
git checkout -b chore/release-v0.2.0
git add CHANGELOG.md
git commit -m "chore: prepare release v0.2.0"
git push -u origin chore/release-v0.2.0
gh pr create --title "chore: prepare release v0.2.0" --body "Release v0.2.0"
```

PR をマージしたら `main` を最新化します。

### 4. タグ作成と push

`main` の最新コミットにアノテーション付きタグを打ちます。

```bash
git checkout main
git pull origin main

git tag -a v0.2.0 -m "Release v0.2.0"
git push origin v0.2.0
```

### 5. GitHub Release の自動作成

タグ push をトリガに `.github/workflows/release.yml` が起動し、CHANGELOG の該当セクションを Release ノートにコピーした GitHub Release が作成されます。

作成された Release は以下で確認できます。

```bash
gh release view v0.2.0
```

### 6. （任意）デプロイ

セルフホスト運用者へは「リリースタグに固定する」運用を推奨しているため、本リポジトリのリリースとセルフホスト環境のデプロイは独立しています。本番反映の手順は [`deploy-production.md`](./deploy-production.md) を参照してください。

## ロールバック

タグは安易に削除しません。問題が見つかった場合は次のパッチバージョン（例: `v0.2.1`）でロールフォワードします。

公開直後（誰も pull していない段階）に限り、削除可能です。

```bash
git tag -d v0.2.0
git push origin :refs/tags/v0.2.0
gh release delete v0.2.0 --yes
```

## 関連

- [`CHANGELOG.md`](../../CHANGELOG.md)
- [コミットメッセージガイドライン](./commit-guidelines.md)
- [本番デプロイ手順](./deploy-production.md)
