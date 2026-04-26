import { test, expect } from '@playwright/test'
import { TEST_STAFF } from './constants'

test.describe('認証フロー', () => {
  test('テナントスタッフがログインできる', async ({ page }) => {
    // 事業者ログインページに移動
    await page.goto('/for-business/login')

    // ログインフォームが表示されることを確認
    await expect(page.locator('h2')).toContainText('事業者ログイン')

    // テスト用の認証情報を入力
    await page.fill('input[name="email"]', TEST_STAFF.email)
    await page.fill('input[name="password"]', TEST_STAFF.password)

    // ログインボタンをクリック
    await page.click('button[type="submit"]')

    // テナント画面にリダイレクトされることを確認
    await expect(page).toHaveURL(/\/tenant/)
  })

  test('無効な認証情報でログインできない', async ({ page }) => {
    await page.goto('/login')

    // 無効な認証情報を入力
    await page.fill('input[name="email"]', 'invalid@example.com')
    await page.fill('input[name="password"]', 'wrongpassword')

    // ログインボタンをクリック
    await page.click('button[type="submit"]')

    // エラーメッセージが表示されることを確認
    await expect(page.locator('[role="alert"]')).toBeVisible()
  })
})
