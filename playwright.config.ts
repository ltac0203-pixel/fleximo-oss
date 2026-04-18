import { defineConfig, devices } from "@playwright/test";

// Playwright設定
// @see https://playwright.dev/docs/test-configuration
export default defineConfig({
    testDir: "./tests/e2e",
    globalSetup: "./tests/e2e/global-setup.ts",

    /* 並列実行の設定 */
    fullyParallel: true,

    /* CIで失敗した場合はリトライしない */
    forbidOnly: !!process.env.CI,

    /* CIではリトライを1回に制限 */
    retries: process.env.CI ? 1 : 0,

    /* 並列ワーカー数 */
    workers: process.env.CI ? 2 : undefined,

    /* レポーター */
    reporter: [["html"], ["list"], ...(process.env.CI ? [["github"] as const] : [])],

    /* 共通の設定 */
    use: {
        /* ベースURL */
        baseURL: process.env.APP_URL || "http://localhost:8000",

        /* トレースの設定（失敗時のみ） */
        trace: "on-first-retry",

        /* スクリーンショット（失敗時のみ） */
        screenshot: "only-on-failure",

        /* ビデオ録画（失敗時のみ） */
        video: "retain-on-failure",
    },

    /* テスト実行前にLaravelサーバーを起動 */
    webServer: {
        command: "php artisan serve",
        url: "http://localhost:8000",
        reuseExistingServer: !process.env.CI,
        timeout: 120 * 1000,
    },

    /* プロジェクト設定（複数ブラウザでテスト） */
    projects: [
        {
            name: "chromium",
            use: { ...devices["Desktop Chrome"] },
        },

        /* モバイルブラウザのテスト（オプション） */
        // {
        //   name: 'Mobile Chrome',
        //   use: { ...devices['Pixel 5'] },
        // },
        // {
        //   name: 'Mobile Safari',
        //   use: { ...devices['iPhone 12'] },
        // },

        /* 他のブラウザのテスト（オプション） */
        // {
        //   name: 'firefox',
        //   use: { ...devices['Desktop Firefox'] },
        // },
        // {
        //   name: 'webkit',
        //   use: { ...devices['Desktop Safari'] },
        // },
    ],
});
