import InputError from "@/Components/InputError";
import InputLabel from "@/Components/InputLabel";
import PrimaryButton from "@/Components/PrimaryButton";
import TextInput from "@/Components/TextInput";
import {
    BusinessTypeOption,
    TenantApplicationFormData,
    TenantApplicationFormErrors,
    TenantApplicationFormField,
} from "@/Pages/TenantApplication/types";

interface InputFormStepProps {
    data: TenantApplicationFormData;
    setData: (key: TenantApplicationFormField, value: string) => void;
    errors: TenantApplicationFormErrors;
    businessTypes: BusinessTypeOption[];
    onNext: () => void;
    processing: boolean;
}

export default function InputFormStep({
    data,
    setData,
    errors,
    businessTypes,
    onNext,
    processing,
}: InputFormStepProps) {
    return (
        <div>
            <div className="absolute left-[-9999px]" aria-hidden="true">
                <label htmlFor="website">ウェブサイト</label>
                <input
                    type="text"
                    id="website"
                    name="website"
                    value={data.website}
                    onChange={(e) => setData("website", e.target.value)}
                    tabIndex={-1}
                    autoComplete="off"
                />
            </div>

            <div className="space-y-5">
                <div className="border-b border-edge pb-5">
                    <h2 className="text-base font-semibold text-ink">店舗情報</h2>
                    <p className="mt-1 text-sm text-muted">登録を希望する店舗の情報をご入力ください。</p>

                    <div className="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-2">
                        <div>
                            <InputLabel htmlFor="tenant_name" value="店舗名" />
                            <TextInput
                                id="tenant_name"
                                type="text"
                                name="tenant_name"
                                value={data.tenant_name}
                                aria-invalid={!!errors.tenant_name}
                                aria-describedby={errors.tenant_name ? "tenant_name-error" : undefined}
                                className="mt-1 block w-full"
                                isFocused={true}
                                onChange={(e) => setData("tenant_name", e.target.value)}
                            />
                            <InputError id="tenant_name-error" message={errors.tenant_name} className="mt-2" />
                        </div>

                        <div>
                            <InputLabel htmlFor="business_type" value="業種" />
                            <select
                                id="business_type"
                                name="business_type"
                                value={data.business_type}
                                aria-invalid={!!errors.business_type}
                                aria-describedby={errors.business_type ? "business_type-error" : undefined}
                                className="mt-1 block w-full rounded-md border border-edge-strong px-3 py-2 focus:border-primary focus:outline-none"
                                onChange={(e) => setData("business_type", e.target.value)}
                            >
                                <option value="">選択してください</option>
                                {businessTypes.map((type) => (
                                    <option key={type.value} value={type.value}>
                                        {type.label}
                                    </option>
                                ))}
                            </select>
                            <InputError id="business_type-error" message={errors.business_type} className="mt-2" />
                        </div>

                        <div className="lg:col-span-2">
                            <InputLabel htmlFor="tenant_address" value="住所（任意）" />
                            <TextInput
                                id="tenant_address"
                                type="text"
                                name="tenant_address"
                                value={data.tenant_address}
                                aria-invalid={!!errors.tenant_address}
                                aria-describedby={errors.tenant_address ? "tenant_address-error" : undefined}
                                className="mt-1 block w-full"
                                onChange={(e) => setData("tenant_address", e.target.value)}
                            />
                            <InputError id="tenant_address-error" message={errors.tenant_address} className="mt-2" />
                        </div>
                    </div>
                </div>

                <div className="border-b border-edge pb-5">
                    <h2 className="text-base font-semibold text-ink">申請者情報</h2>
                    <p className="mt-1 text-sm text-muted">審査結果のご連絡先として使用します。</p>

                    <div className="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-2">
                        <div>
                            <InputLabel htmlFor="applicant_name" value="お名前" />
                            <TextInput
                                id="applicant_name"
                                type="text"
                                name="applicant_name"
                                value={data.applicant_name}
                                aria-invalid={!!errors.applicant_name}
                                aria-describedby={errors.applicant_name ? "applicant_name-error" : undefined}
                                className="mt-1 block w-full"
                                autoComplete="name"
                                onChange={(e) => setData("applicant_name", e.target.value)}
                            />
                            <InputError id="applicant_name-error" message={errors.applicant_name} className="mt-2" />
                        </div>

                        <div>
                            <InputLabel htmlFor="applicant_phone" value="電話番号" />
                            <TextInput
                                id="applicant_phone"
                                type="tel"
                                name="applicant_phone"
                                value={data.applicant_phone}
                                aria-invalid={!!errors.applicant_phone}
                                aria-describedby={errors.applicant_phone ? "applicant_phone-error" : undefined}
                                className="mt-1 block w-full"
                                autoComplete="tel"
                                onChange={(e) => setData("applicant_phone", e.target.value)}
                            />
                            <InputError id="applicant_phone-error" message={errors.applicant_phone} className="mt-2" />
                        </div>

                        <div className="lg:col-span-2">
                            <InputLabel htmlFor="applicant_email" value="メールアドレス" />
                            <TextInput
                                id="applicant_email"
                                type="email"
                                name="applicant_email"
                                value={data.applicant_email}
                                aria-invalid={!!errors.applicant_email}
                                aria-describedby={errors.applicant_email ? "applicant_email-error" : undefined}
                                className="mt-1 block w-full"
                                autoComplete="email"
                                onChange={(e) => setData("applicant_email", e.target.value)}
                            />
                            <InputError id="applicant_email-error" message={errors.applicant_email} className="mt-2" />
                        </div>
                    </div>
                </div>

                <div className="pb-4">
                    <h2 className="text-base font-semibold text-ink">パスワード設定</h2>
                    <p className="mt-1 text-sm text-muted">ログイン時に使用するパスワードを設定してください。</p>

                    <div className="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-2">
                        <div>
                            <InputLabel htmlFor="password" value="パスワード" />
                            <TextInput
                                id="password"
                                type="password"
                                name="password"
                                value={data.password}
                                aria-invalid={!!errors.password}
                                aria-describedby={errors.password ? "password-error" : undefined}
                                className="mt-1 block w-full"
                                autoComplete="new-password"
                                onChange={(e) => setData("password", e.target.value)}
                            />
                            <InputError id="password-error" message={errors.password} className="mt-2" />
                            <p className="mt-1 text-xs text-muted">8文字以上で入力してください</p>
                        </div>

                        <div>
                            <InputLabel htmlFor="password_confirmation" value="パスワード（確認）" />
                            <TextInput
                                id="password_confirmation"
                                type="password"
                                name="password_confirmation"
                                value={data.password_confirmation}
                                aria-invalid={!!errors.password_confirmation}
                                aria-describedby={
                                    errors.password_confirmation ? "password_confirmation-error" : undefined
                                }
                                className="mt-1 block w-full"
                                autoComplete="new-password"
                                onChange={(e) => setData("password_confirmation", e.target.value)}
                            />
                            <InputError
                                id="password_confirmation-error"
                                message={errors.password_confirmation}
                                className="mt-2"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <div className="mt-6">
                <PrimaryButton
                    type="button"
                    className="w-full justify-center"
                    onClick={onNext}
                    disabled={processing}
                    isBusy={processing}
                >
                    次へ
                </PrimaryButton>
            </div>

            <p className="mt-4 text-center text-sm text-muted">審査には通常1〜3営業日程度お時間をいただきます。</p>
        </div>
    );
}
