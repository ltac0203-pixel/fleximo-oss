import { test, expect, type Page, type Locator } from '@playwright/test'
import { loginAsTenantAdmin } from './helpers/auth'

// 同一プロセス内で複数回走らせても一覧に残骸が残って衝突しないよう、商品名に Date.now() を含める。
const TEST_ITEM_NAME = `E2Eテスト商品_${Date.now()}`
const TEST_ITEM_PRICE = 999
const UPDATED_PRICE = 1199

// 商品一覧は useWindowVirtualizer で仮想化されているため、ビューポート外のカードは
// DOMに存在しない。最下部までスクロールして対象カードを描画させる。
async function scrollToItem(page: Page, itemName: string): Promise<Locator> {
  const heading = page.locator(`h4:has-text("${itemName}")`)
  for (let i = 0; i < 8; i++) {
    if ((await heading.count()) > 0) {
      break
    }
    await page.evaluate(() => window.scrollBy(0, window.innerHeight))
    await page.waitForTimeout(150)
  }
  await expect(heading).toBeVisible()
  return heading
}

function cardOf(page: Page, itemName: string): Locator {
  // ItemCard のルート div をクラスで特定し、その中の h4 でフィルタする。
  // 単に `div` を起点にすると親コンテナまで巻き込んで a/button が複数解決してしまう。
  return page
    .locator('div.bg-white.border')
    .filter({ has: page.locator(`h4:has-text("${itemName}")`) })
    .first()
}

test.describe.serial('テナント管理者によるメニュー商品CRUDフロー', () => {
  test.beforeEach(async ({ page }) => {
    await loginAsTenantAdmin(page)
  })

  test('管理者が商品を新規作成できる', async ({ page }) => {
    await page.goto('/tenant/menu/items/create')
    await page.waitForLoadState('networkidle')

    // 商品名・価格・カテゴリ（必須・1件以上）を入力する。
    await page.fill('#name', TEST_ITEM_NAME)
    await page.fill('#price', String(TEST_ITEM_PRICE))
    // 既存シードのカテゴリ「ドリンク」をひとつ選択（StoreMenuItemRequest が category_ids min:1 を要求）。
    await page.getByRole('checkbox', { name: 'ドリンク' }).check()

    await page.click('button[type="submit"]:has-text("作成")')

    // 作成後は商品一覧へ遷移し、新しい商品が表示される。
    await expect(page).toHaveURL(/\/tenant\/menu\/items$/)
    await scrollToItem(page, TEST_ITEM_NAME)
  })

  test('管理者が作成した商品を編集できる', async ({ page }) => {
    await page.goto('/tenant/menu/items')
    await page.waitForLoadState('networkidle')
    await scrollToItem(page, TEST_ITEM_NAME)

    // 一覧から該当商品のカードを特定し、その「編集」リンクを開く。
    await cardOf(page, TEST_ITEM_NAME).locator('a:has-text("編集")').click()

    await expect(page).toHaveURL(/\/tenant\/menu\/items\/\d+\/edit/)

    // 価格を更新して保存。
    await page.fill('#price', String(UPDATED_PRICE))
    await page.click('button[type="submit"]:has-text("更新")')

    await expect(page).toHaveURL(/\/tenant\/menu\/items$/)
    await scrollToItem(page, TEST_ITEM_NAME)

    // 更新後の価格（1,199円）が一覧カードに反映される。
    await expect(cardOf(page, TEST_ITEM_NAME)).toContainText('1,199')
  })

  test('管理者が商品を削除できる', async ({ page }) => {
    await page.goto('/tenant/menu/items')
    await page.waitForLoadState('networkidle')
    await scrollToItem(page, TEST_ITEM_NAME)

    await cardOf(page, TEST_ITEM_NAME).locator('button:has-text("削除")').click()

    // 削除確認モーダル内の「削除」ボタン（ダイアログの role=dialog 配下）を押す。
    const dialog = page.locator('[role="dialog"]')
    await dialog.locator('button:has-text("削除")').click()

    // 一覧から該当商品が消える（仮想化の有無に関わらず、DOMから無くなる）。
    await expect(page.locator(`h4:has-text("${TEST_ITEM_NAME}")`)).toHaveCount(0)
  })
})
