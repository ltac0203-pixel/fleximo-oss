import { test, expect } from '@playwright/test'
import { loginAsStaff } from './helpers/auth'

test.describe('KDS注文管理フロー', () => {
  test.beforeEach(async ({ page }) => {
    // スタッフとしてログイン
    await loginAsStaff(page)

    // KDS（キッチンディスプレイシステム）画面に移動
    await page.goto('/tenant/kds')
    await page.waitForLoadState('networkidle')
  })

  test('KDS画面に3カラムが表示される', async ({ page }) => {
    // 3カラムの見出しが表示されることを確認
    await expect(page.locator('h2:has-text("受付済み")')).toBeVisible()
    await expect(page.locator('h2:has-text("調理中")')).toBeVisible()
    await expect(page.locator('h2:has-text("準備完了")')).toBeVisible()
  })

  test('KDSが各カラムの注文件数を表示する', async ({ page }) => {
    // 各カラムヘッダーに件数バッジ（span.font-mono）が表示されることを確認
    const countBadges = page.locator('span.font-mono')
    await expect(countBadges.first()).toBeVisible()

    // 3つのカラムに対して3つの件数バッジがあることを確認
    await expect(countBadges).toHaveCount(3)
  })
})

test.describe.serial('KDSステータス遷移フロー', () => {
  test.beforeEach(async ({ page }) => {
    // スタッフとしてログイン
    await loginAsStaff(page)

    // KDS（キッチンディスプレイシステム）画面に移動
    await page.goto('/tenant/kds')
    await page.waitForLoadState('networkidle')
  })

  test('スタッフが注文を受付済みから調理中に更新できる', async ({ page }) => {
    // 「受付済み」カラム内に注文があることを確認
    const acceptedColumn = page.locator('h2:has-text("受付済み")').locator('..')
    await expect(acceptedColumn).toBeVisible()

    // 「調理開始」ボタンをクリック
    const cookButton = page.locator('button:has-text("調理開始")').first()
    await expect(cookButton).toBeVisible()
    await cookButton.click()

    // 「調理中」カラムに注文が移動したことを確認
    await expect(page.locator('button:has-text("準備完了")').first()).toBeVisible()
  })

  test('スタッフが注文を調理中から準備完了に更新できる', async ({ page }) => {
    // 「準備完了」ボタンをクリック
    const readyButton = page.locator('button:has-text("準備完了")').first()
    await expect(readyButton).toBeVisible()
    await readyButton.click()

    // 「準備完了」カラムに注文が移動したことを確認（「受け渡し完了」ボタンが表示される）
    await expect(page.locator('button:has-text("受け渡し完了")').first()).toBeVisible()
  })

  test('スタッフが準備完了の注文を完了できる', async ({ page }) => {
    // 「受け渡し完了」ボタンをクリック
    const completeButton = page.locator('button:has-text("受け渡し完了")').first()
    await expect(completeButton).toBeVisible()
    await completeButton.click()

    // 注文がKDSから消えることを確認（「受け渡し完了」ボタンが非表示）
    await expect(page.locator('button:has-text("受け渡し完了")')).not.toBeVisible()
  })
})
