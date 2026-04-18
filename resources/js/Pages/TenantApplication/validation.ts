import {
    BusinessTypeOption,
    TenantApplicationFormData,
    TenantApplicationFormErrors,
    TenantApplicationFormField,
} from "@/Pages/TenantApplication/types";

const EMAIL_REGEX = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

const STEP1_FIELD_ORDER: TenantApplicationFormField[] = [
    "tenant_name",
    "business_type",
    "applicant_name",
    "applicant_email",
    "applicant_phone",
    "password",
    "password_confirmation",
];

export interface Step1ValidationResult {
    isValid: boolean;
    errors: TenantApplicationFormErrors;
    firstErrorField: TenantApplicationFormField | null;
}

export function validateTenantApplicationStep1(data: TenantApplicationFormData): Step1ValidationResult {
    const errors: TenantApplicationFormErrors = {};

    if (!data.tenant_name.trim()) {
        errors.tenant_name = "店舗名を入力してください";
    }

    if (!data.business_type) {
        errors.business_type = "業種を選択してください";
    }

    if (!data.applicant_name.trim()) {
        errors.applicant_name = "お名前を入力してください";
    }

    if (!data.applicant_email.trim()) {
        errors.applicant_email = "メールアドレスを入力してください";
    } else if (!EMAIL_REGEX.test(data.applicant_email)) {
        errors.applicant_email = "有効なメールアドレスを入力してください";
    }

    if (!data.applicant_phone.trim()) {
        errors.applicant_phone = "電話番号を入力してください";
    }

    if (!data.password.trim()) {
        errors.password = "パスワードを入力してください";
    } else if (data.password.length < 8) {
        errors.password = "パスワードは8文字以上で入力してください";
    }

    if (!data.password_confirmation.trim()) {
        errors.password_confirmation = "パスワード（確認）を入力してください";
    } else if (data.password !== data.password_confirmation) {
        errors.password_confirmation = "パスワードが一致しません";
    }

    const firstErrorField = STEP1_FIELD_ORDER.find((field) => !!errors[field]) ?? null;

    return {
        isValid: Object.keys(errors).length === 0,
        errors,
        firstErrorField,
    };
}

export function getBusinessTypeLabel(value: string, businessTypes: BusinessTypeOption[]): string {
    const type = businessTypes.find((option) => option.value === value);
    return type?.label ?? value;
}
