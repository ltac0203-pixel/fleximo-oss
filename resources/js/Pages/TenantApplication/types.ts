export interface BusinessTypeOption {
    value: string;
    label: string;
}

export interface TenantApplicationFormData {
    applicant_name: string;
    applicant_email: string;
    applicant_phone: string;
    tenant_name: string;
    tenant_address: string;
    business_type: string;
    password: string;
    password_confirmation: string;
    website: string;
}

export type TenantApplicationFormField = keyof TenantApplicationFormData;

export type TenantApplicationFormErrors = Partial<Record<TenantApplicationFormField, string>> & Record<string, string>;

export type StepNumber = 1 | 2;
