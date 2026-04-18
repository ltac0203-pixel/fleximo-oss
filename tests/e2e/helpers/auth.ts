import { Page, expect } from '@playwright/test';
import { TEST_CUSTOMER, TEST_STAFF, TEST_ADMIN } from '../constants';

export async function loginAsCustomer(page: Page) {
  await page.goto('/login');
  await page.fill('input[name="email"]', TEST_CUSTOMER.email);
  await page.fill('input[name="password"]', TEST_CUSTOMER.password);
  await page.click('button[type="submit"]');
  await expect(page).toHaveURL(/\/customer/);
}

export async function loginAsStaff(page: Page) {
  await page.goto('/for-business/login');
  await page.fill('input[name="email"]', TEST_STAFF.email);
  await page.fill('input[name="password"]', TEST_STAFF.password);
  await page.click('button[type="submit"]');
  await expect(page).toHaveURL(/\/tenant/);
}

export async function loginAsTenantAdmin(page: Page) {
  await page.goto('/for-business/login');
  await page.fill('input[name="email"]', TEST_ADMIN.email);
  await page.fill('input[name="password"]', TEST_ADMIN.password);
  await page.click('button[type="submit"]');
  await expect(page).toHaveURL(/\/tenant/);
}
