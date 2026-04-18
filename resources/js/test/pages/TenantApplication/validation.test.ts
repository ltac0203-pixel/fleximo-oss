import { validateTenantApplicationStep1 } from "@/Pages/TenantApplication/validation";
import { TenantApplicationFormData } from "@/Pages/TenantApplication/types";
import { describe, expect, it } from "vitest";

function createValidData(overrides: Partial<TenantApplicationFormData> = {}): TenantApplicationFormData {
    return {
        applicant_name: "山田 太郎",
        applicant_email: "taro@example.com",
        applicant_phone: "09012345678",
        tenant_name: "テスト店舗",
        tenant_address: "東京都渋谷区1-2-3",
        business_type: "restaurant",
        password: "password123",
        password_confirmation: "password123",
        website: "",
        ...overrides,
    };
}

describe("validateTenantApplicationStep1", () => {
    it("returns required field errors when mandatory fields are empty", () => {
        const result = validateTenantApplicationStep1(
            createValidData({
                tenant_name: "",
                business_type: "",
                applicant_name: "",
                applicant_email: "",
                applicant_phone: "",
                password: "",
                password_confirmation: "",
            }),
        );

        expect(result.isValid).toBe(false);
        expect(result.errors.tenant_name).toBe("店舗名を入力してください");
        expect(result.errors.business_type).toBe("業種を選択してください");
        expect(result.errors.applicant_name).toBe("お名前を入力してください");
        expect(result.errors.applicant_email).toBe("メールアドレスを入力してください");
        expect(result.errors.applicant_phone).toBe("電話番号を入力してください");
        expect(result.errors.password).toBe("パスワードを入力してください");
        expect(result.errors.password_confirmation).toBe("パスワード（確認）を入力してください");
    });

    it("returns an email validation error for invalid email format", () => {
        const result = validateTenantApplicationStep1(
            createValidData({
                applicant_email: "invalid-email",
            }),
        );

        expect(result.isValid).toBe(false);
        expect(result.errors.applicant_email).toBe("有効なメールアドレスを入力してください");
    });

    it("returns a minimum length error when password is shorter than 8 characters", () => {
        const result = validateTenantApplicationStep1(
            createValidData({
                password: "short",
                password_confirmation: "short",
            }),
        );

        expect(result.isValid).toBe(false);
        expect(result.errors.password).toBe("パスワードは8文字以上で入力してください");
    });

    it("returns a mismatch error when password and confirmation do not match", () => {
        const result = validateTenantApplicationStep1(
            createValidData({
                password_confirmation: "different-password",
            }),
        );

        expect(result.isValid).toBe(false);
        expect(result.errors.password_confirmation).toBe("パスワードが一致しません");
    });

    it("returns valid result when all fields pass validation", () => {
        const result = validateTenantApplicationStep1(createValidData());

        expect(result.isValid).toBe(true);
        expect(result.errors).toEqual({});
        expect(result.firstErrorField).toBeNull();
    });

    it("returns the first invalid field according to step order", () => {
        const result = validateTenantApplicationStep1(
            createValidData({
                tenant_name: "",
                applicant_email: "invalid-email",
            }),
        );

        expect(result.isValid).toBe(false);
        expect(result.firstErrorField).toBe("tenant_name");
    });
});
