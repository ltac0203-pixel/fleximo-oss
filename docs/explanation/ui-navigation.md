# Explanation: ナビゲーション・ボタン配置ガイドライン

Fleximo の管理画面 (Admin / Tenant) と顧客画面 (Customer) で、ナビゲーション要素とボタン配置を統一するためのガイドラインです。新しい画面を作るとき、既存画面を改修するとき、PR レビューのときの規範としてください。

目的は以下の 3 点:

- ユーザーの学習コストを下げる (どの画面でも主操作が同じ位置にある)
- 誤操作を減らす (「キャンセル」と「戻る」が混在しない)
- 実装者の判断コストを下げる (毎回「どこに何を置くか」を議論しない)

本文書は [design-principles.md](./design-principles.md) で定義した「命名とロールの一貫性」原則を UI 層に適用したものです。

## 1. ページタイトルとパンくず

### ページタイトル

- ページタイトルは **Layout の `title` / `header` props で定義する**。ページ本文内に `<h1>` を重複させない
- Tenant 画面: ヘッダーに `テナント名 / ページタイトル` 形式 (現行 `TenantLayout` 継続)
- Admin 画面: `header` props に `<PageHeader>` を渡す
- Customer 画面: 固定ヘッダーにテナント名、画面タイトルはページ先頭の `<PageHeader>` で表示

### パンくず (Breadcrumb)

- **3 階層以上の画面ではパンくずを表示する**。例: `Tenant/Menu/OptionGroups/Edit` → `メニュー > オプショングループ > 編集`
- 2 階層以下の画面では省略可 (ヘッダーの `/` 区切りで十分)
- コンポーネント: `resources/js/Components/Breadcrumb.tsx`

```tsx
<Breadcrumb
    items={[
        { label: "メニュー", href: route("tenant.menu.items.page") },
        { label: "オプショングループ", href: route("tenant.menu.option-groups.page") },
        { label: "編集" },
    ]}
/>
```

最後の要素は現在地を表すため `href` を省略する。

## 2. 戻るナビゲーション

### 命名

- **戻る・キャンセル動作の副ボタンは「キャンセル」に統一**する。「戻る」は使わない
- 確認ダイアログなど、操作を取り消す文脈も「キャンセル」

### 実装

- **ページ遷移先への戻りは `router.visit(route(...))` で明示的にルートを指定** することを標準とする
- `window.history.back()` は Customer 画面のカート/チェックアウトなど、ブラウザ履歴を尊重する動線に限って許容
- Inertia の `preserveState` / `preserveScroll` でスクロール位置・検索条件を維持したい場合は活用する

## 3. ボタン配置パターン

### (a) フォーム画面 (Create / Edit)

`resources/js/Components/FormActions.tsx` を使う:

```tsx
<FormActions>
    <Button variant="secondary" type="button" onClick={handleCancel}>
        キャンセル
    </Button>
    <Button variant="primary" type="submit" isBusy={processing}>
        更新
    </Button>
</FormActions>
```

- **右寄せ**、セカンダリ → プライマリの順 (視線の流れ: 打ち消し → 実行)
- 送信処理中の表示は `isBusy` プロップで統一
- 削除など破壊的操作を含むフォームは `leftSlot` に `variant="danger"` ボタンを置く:

```tsx
<FormActions
    leftSlot={
        <Button variant="danger" type="button" onClick={handleDelete}>
            削除する
        </Button>
    }
>
    <Button variant="secondary" type="button" onClick={handleCancel}>
        キャンセル
    </Button>
    <Button variant="primary" type="submit" isBusy={processing}>
        更新
    </Button>
</FormActions>
```

### (b) リスト画面 (Index)

`resources/js/Components/PageHeader.tsx` を使う:

```tsx
<PageHeader
    title="商品一覧"
    help={<HelpButton onClick={openHelp} />}
    actions={
        <Button variant="primary" onClick={openCreate}>
            商品を追加
        </Button>
    }
/>
```

- 左: タイトル + ヘルプボタン
- 右: プライマリアクション (新規作成など)
- フィルタ・検索 UI は PageHeader の下に別行で配置。同じ行に詰め込まない

### (c) 顧客画面の最終確定ボタン (Cart / Checkout)

- **固定フッター全幅ボタン**のパターンは **顧客画面の決済・確定動作のみ** に限定して採用
- 管理画面では使わない
- 文言は `${金額}を支払う` / `確定する` など、動作を具体的に

## 4. ボタンテキスト辞書

| 動作                       | 標準テキスト      | 使用箇所                       |
| -------------------------- | ----------------- | ------------------------------ |
| 新規作成 (フォーム submit) | `作成`            | Create 画面                    |
| 新規作成 (一覧の CTA)      | `○○を追加`        | Index 画面のヘッダー           |
| 更新 (フォーム submit)     | `更新`            | Edit 画面 (「保存」は使わない) |
| 削除                       | `削除する`        | `<Button variant="danger">`    |
| 中止                       | `キャンセル`      | `<Button variant="secondary">` |
| 確定 (決済)                | `${金額}を支払う` | Cart / Checkout                |
| 確定 (非決済)              | `確定する`        | 注文受付確定など               |
| 詳細                       | `詳細`            | テーブル内テキストリンク       |

## 5. ボタンサイズ・バリアント運用

| 要素                                                | サイズ   | 使用箇所                                                  |
| --------------------------------------------------- | -------- | --------------------------------------------------------- |
| `<Button variant="primary">` (`size="md"` デフォルト) | md       | フォームの submit、リストの CTA                           |
| `<Button variant="primary" size="sm">`              | sm       | テーブル内の行アクション                                  |
| `<Button variant="primary" size="lg">`              | lg       | 顧客画面の決済ボタン                                      |
| `<Button variant="primary" tone="outline">`         | -        | カード内の軽い CTA のみ (主操作では使わない)              |
| `<Button variant="danger">`                         | 削除専用 | 「無効化」「アーカイブ」は secondary + 確認モーダルで対応 |

## 6. アクセシビリティ

- **フォーム内の副ボタンは `type="button"` を必ず明記**する (submit の暴発を防ぐ。`<Button variant="secondary">` も `type="button"` を明示すること)
- `aria-busy` は `<Button variant="primary">` / `<Button variant="danger">` の `isBusy` プロップで自動付与される (`secondary` は `isBusy` 非対応)
- モバイルタップ領域は最小 44px × 44px (現行の `BUTTON_SIZE_CLASSES` の `md` 以上を下限とする)
- パンくずには `aria-label="パンくずリスト"` と最後の要素に `aria-current="page"` を付ける (Breadcrumb コンポーネントが自動で付与)

## 7. チェックリスト (PR レビュー時)

- [ ] 副ボタンの文言は「キャンセル」になっているか (「戻る」「取消」になっていないか)
- [ ] フォームのボタンは `FormActions` を使い、右寄せになっているか
- [ ] リスト画面のヘッダーは `PageHeader` を使っているか
- [ ] 3 階層以上の画面で `Breadcrumb` を設置しているか
- [ ] ページ本文内に `<h1>` / `<h2>` でページタイトルを重複表示していないか
- [ ] プライマリ submit に `isBusy={processing}` が付いているか
- [ ] 戻る実装は `router.visit(route(...))` か (顧客画面の履歴依存動線を除く)

## 関連

- [設計原則](./design-principles.md)
- [マルチテナント設計](./multi-tenancy.md)
