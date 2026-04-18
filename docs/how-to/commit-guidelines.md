# How-to: コミットメッセージを書く

Fleximo では「後からログを辿れること」を重視します。1コミット＝1意図、1行目で意図が伝わるメッセージを
目指してください。

本プロジェクトは [Conventional Commits 1.0.0](https://www.conventionalcommits.org/ja/v1.0.0/) に準拠します。

## フォーマット

```
<type>(<scope>)?(!)?: <subject>

<body: 任意、なぜ変更したかを書く>

<footer: 任意、BREAKING CHANGE や Refs を書く>
```

- `scope` は省略可。変更範囲を示す（例: `kds`, `payment`, `auth`）
- 破壊的変更は `type` の後に `!` を付け、フッターに `BREAKING CHANGE: <内容>` を書く
- `BREAKING CHANGE` を含むコミットは MAJOR バンプ対象（0.x 期間は MINOR バンプ）になる

### プレフィックス

| プレフィックス | 用途                                                       |
| -------------- | ---------------------------------------------------------- |
| `feat`         | 新機能の追加                                               |
| `fix`          | バグ修正                                                   |
| `refactor`     | 挙動を変えない内部構造の変更                               |
| `perf`         | パフォーマンス改善                                         |
| `docs`         | ドキュメントのみの変更                                     |
| `test`         | テストの追加・修正                                         |
| `chore`        | ビルド設定・依存更新など、コード本体に影響しない雑務       |
| `ci`           | CI 設定の変更                                              |
| `style`        | フォーマッタによる整形のみ                                 |

### 件名（1行目）

- 50文字以内
- 末尾にピリオドを付けない
- 日本語・英語どちらでも可（プロジェクト内で統一する）
- 命令形（「〜を追加」「〜を修正」）

### 本文

- 空行を1つ挟んでから書く
- **何をしたか** ではなく **なぜそうしたか** を書く（diff を見れば「何」は分かる）
- 1行72文字程度で改行する

## 良い例

```
fix(payment): fincode Webhook の署名検証でタイムスタンプ許容幅を 5 分に広げる

fincode 側のリトライが 3 分後に来るケースがあり、従来の 1 分制限では
正常な Webhook まで弾いていた。fincode 公式ドキュメントの推奨値
（±5 分）に合わせる。
```

```
feat(kds): 並び替えドラッグ操作を追加
```

```
refactor(order)!: OrderService の status 引数を enum に変更

BREAKING CHANGE: OrderService::updateStatus() の第2引数が string から
OrderStatus enum に変更されました。呼び出し側を OrderStatus::from() で
変換してください。
```

## 悪い例

- `fix: バグ修正` — 何の修正か伝わらない
- `update` — プレフィックスなし、内容も不明
- `WIP` — 共有ブランチに残さない（作業中なら自分のブランチにのみ）

## ブランチ運用

GitHub Flow を採用。`main` は常にデプロイ可能な状態を保ち、作業はすべて目的別プレフィックス付きブランチで行う。

- `main` への直 push は禁止。PR 経由のみ
- ブランチ名は type に対応: `feat/<slug>`, `fix/<slug>`, `docs/<slug>`, `chore/<slug>`, `refactor/<slug>`
- PR 作成時に lint / test / build がすべて通っていること

## 関連

- [リリース手順](./release.md)
- [GitHub: Fleximo Issues / PRs](https://github.com/ltac0203-pixel/fleximo-oss)
