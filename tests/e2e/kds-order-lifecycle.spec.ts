import { test, expect, type Locator, type Page } from '@playwright/test'
import { loginAsStaff } from './helpers/auth'

// E2ETestSeederがPaid状態で投入する注文コード。
// Acceptedで投入される既存テスト用注文（E001）とは別注文として作成されているため、
// 本specのカード操作はすべて order_code でスコープし、staff-order-management.spec.ts と
// 並列実行されても干渉しないようにする。
// orders.order_code は char(4) なので 4 文字に収める。
const ORDER_CODE = 'E002'

function orderCard(page: Page): Locator {
  return page.locator('div.border-l-4').filter({ hasText: ORDER_CODE })
}

test.describe.serial('KDS注文ライフサイクル（Paid→Completed）', () => {
  test.beforeEach(async ({ page }) => {
    await loginAsStaff(page)
    await page.goto('/tenant/kds')
    await page.waitForLoadState('networkidle')
  })

  test('Paid注文を受付してAcceptedに遷移できる', async ({ page }) => {
    const card = orderCard(page)
    await expect(card).toBeVisible()

    // Paidカードに「受付」「却下」のペアが表示されている。「受付」を押す。
    await card.locator('button:has-text("受付")').click()

    // Accepted状態のアクションボタン（調理開始）が同カードに現れるまで待つ。
    await expect(card.locator('button:has-text("調理開始")')).toBeVisible()
  })

  test('Accepted注文を調理開始してInProgressに遷移できる', async ({ page }) => {
    const card = orderCard(page)
    await expect(card.locator('button:has-text("調理開始")')).toBeVisible()
    await card.locator('button:has-text("調理開始")').click()

    await expect(card.locator('button:has-text("準備完了")')).toBeVisible()
  })

  test('InProgress注文を準備完了してReadyに遷移できる', async ({ page }) => {
    const card = orderCard(page)
    await expect(card.locator('button:has-text("準備完了")')).toBeVisible()
    await card.locator('button:has-text("準備完了")').click()

    await expect(card.locator('button:has-text("受け渡し完了")')).toBeVisible()
  })

  test('Ready注文を受け渡し完了してKDSから消える', async ({ page }) => {
    const card = orderCard(page)
    await expect(card.locator('button:has-text("受け渡し完了")')).toBeVisible()
    await card.locator('button:has-text("受け渡し完了")').click()

    // Completedになったカードは KDS の対象外になり、画面から消える。
    await expect(orderCard(page)).toHaveCount(0)
  })
})
