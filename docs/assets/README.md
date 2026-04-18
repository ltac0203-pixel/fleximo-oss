# docs/assets

README や GitHub 上で参照する画像を格納する場所です。

## 命名規則

| ファイル | 用途 | 推奨サイズ |
| --- | --- | --- |
| `hero.png` | README 最上部の Hero 画像（管理画面 + 顧客画面のモック） | 1600×900 |
| `screenshot-customer.png` | 顧客向け注文画面のスクリーンショット | 1280×720 |
| `screenshot-kds.png` | KDS（Kitchen Display System）画面 | 1280×720 |
| `screenshot-dashboard.png` | テナント管理ダッシュボード | 1280×720 |
| `demo.gif` | 注文〜決済〜KDS 反映までの 10〜30 秒デモ | 幅 1000px 以内 |

## 最適化

- PNG は `pngquant` / `oxipng` で可逆圧縮する
- GIF は `gifsicle -O3` で最適化し、1MB 以下に抑える
- サイズが大きい場合は Git LFS ではなく外部 CDN（GitHub Release 添付 or 画像ホスティング）経由で参照する
