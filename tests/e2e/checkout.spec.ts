import { test, expect } from '@playwright/test'
import { loginAsCustomer } from './helpers/auth'
import { TEST_TENANT_SLUG } from './constants'

test.describe('チェックアウトフロー', () => {
  test.beforeEach(async ({ page }) => {
    // 顧客としてログイン
    await loginAsCustomer(page)
  })

  test('顧客がメニューから商品を選択しチェックアウトまで進める', async ({ page }) => {
    // メニューページに移動
    await page.goto(`/order/tenant/${TEST_TENANT_SLUG}/menu`)

    // メニューが表示されるまで待機
    await page.waitForLoadState('networkidle')

    // 最初のメニューアイテムをクリック（アイテム詳細モーダルを開く）
    const firstMenuItem = page.locator('[data-testid="menu-item"], .cursor-pointer').first()
    await firstMenuItem.click()

    // モーダル内の「カートに追加」ボタンをクリック
    const addToCartButton = page.locator('button:has-text("カートに追加")')
    await expect(addToCartButton).toBeVisible()
    await addToCartButton.click()

    // カートページに移動
    await page.goto('/order/cart')

    // カートに商品が入っていることを確認（CartSummaryが表示される）
    await expect(page.locator('text=注文手続きへ')).toBeVisible()

    // 注文手続きへボタンをクリック
    await page.locator('text=注文手続きへ').first().click()

    // チェックアウトページにリダイレクト
    await expect(page).toHaveURL(/\/order\/checkout/)
  })

  test('カート内の商品数量を変更できる', async ({ page }) => {
    // メニューページに移動して商品を追加
    await page.goto(`/order/tenant/${TEST_TENANT_SLUG}/menu`)
    await page.waitForLoadState('networkidle')

    // メニューアイテムをクリックしてモーダルを開く
    const firstMenuItem = page.locator('[data-testid="menu-item"], .cursor-pointer').first()
    await firstMenuItem.click()

    // カートに追加
    await page.locator('button:has-text("カートに追加")').click()

    // カートページに移動
    await page.goto('/order/cart')
    await page.waitForLoadState('networkidle')

    // 数量増加ボタンをクリック
    const increaseButton = page.locator('button[aria-label="増やす"]').first()
    await expect(increaseButton).toBeVisible()
    await increaseButton.click()

    // 数量が2に更新されることを確認
    await expect(page.locator('text=2')).toBeVisible()
  })

  test('カートから商品を削除できる', async ({ page }) => {
    // メニューページに移動して商品を追加
    await page.goto(`/order/tenant/${TEST_TENANT_SLUG}/menu`)
    await page.waitForLoadState('networkidle')

    // メニューアイテムをクリックしてモーダルを開く
    const firstMenuItem = page.locator('[data-testid="menu-item"], .cursor-pointer').first()
    await firstMenuItem.click()

    // カートに追加
    await page.locator('button:has-text("カートに追加")').click()

    // カートページに移動
    await page.goto('/order/cart')
    await page.waitForLoadState('networkidle')

    // 削除ボタンをクリック
    const removeButton = page.locator('button[aria-label="削除"]').first()
    await expect(removeButton).toBeVisible()
    await removeButton.click()

    // カートが空になったメッセージが表示されることを確認
    await expect(page.locator('text=カートに商品がありません')).toBeVisible()
  })

  test('空のカートで注文できない', async ({ page }) => {
    // 空のカートページに移動
    await page.goto('/order/cart')
    await page.waitForLoadState('networkidle')

    // 「注文手続きへ」ボタンが表示されないことを確認（CartSummaryが描画されない）
    await expect(page.locator('text=注文手続きへ')).not.toBeVisible()

    // 空カートメッセージが表示されることを確認
    await expect(page.locator('text=カートに商品がありません')).toBeVisible()
  })

  test('チェックアウトページの構成要素を確認できる', async ({ page }) => {
    // メニューページに移動して商品を追加
    await page.goto(`/order/tenant/${TEST_TENANT_SLUG}/menu`)
    await page.waitForLoadState('networkidle')

    // メニューアイテムをクリックしてモーダルを開く
    const firstMenuItem = page.locator('[data-testid="menu-item"], .cursor-pointer').first()
    await firstMenuItem.click()

    // カートに追加
    await page.locator('button:has-text("カートに追加")').click()

    // カートページ経由でチェックアウトへ
    await page.goto('/order/cart')
    await page.waitForLoadState('networkidle')
    await page.locator('text=注文手続きへ').first().click()

    // チェックアウトページに到達
    await expect(page).toHaveURL(/\/order\/checkout/)

    // ページヘッダー「お支払い」が表示されることを確認
    await expect(page.locator('h1:has-text("お支払い")')).toBeVisible()

    // 「を支払う」ボタンが存在することを確認
    await expect(page.locator('button:has-text("を支払う")')).toBeVisible()
  })
})
