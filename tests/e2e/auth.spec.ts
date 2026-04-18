import { test, expect } from '@playwright/test'
import { loginAsCustomer } from './helpers/auth'
import { TEST_CUSTOMER, TEST_STAFF } from './constants'

test.describe('認証フロー', () => {
  test('顧客がログインできる', async ({ page }) => {
    // ログインページに移動
    await page.goto('/login')

    // ログインフォームが表示されることを確認
    await expect(page.locator('h2')).toContainText('ログイン')

    // テスト用の認証情報を入力
    await page.fill('input[name="email"]', TEST_CUSTOMER.email)
    await page.fill('input[name="password"]', TEST_CUSTOMER.password)

    // ログインボタンをクリック
    await page.click('button[type="submit"]')

    // 顧客ホームにリダイレクトされることを確認
    await expect(page).toHaveURL(/\/customer/)
  })

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

  test('顧客がログアウトできる', async ({ page }) => {
    // まずログイン
    await loginAsCustomer(page)

    // 顧客ホームに到達していることを確認
    await expect(page).toHaveURL(/\/customer/)

    // ヘッダー内のログアウトボタン（Inertia Link as="button"、3番目のアイコン）をクリック
    // Customer/Home/Index.tsx: header内の最後のbutton要素（ログアウトアイコン）
    const logoutButton = page.locator('header button').last()
    await logoutButton.click()

    // トップページにリダイレクトされることを確認
    await expect(page).toHaveURL(/^\/$|\/login/)
  })
})
