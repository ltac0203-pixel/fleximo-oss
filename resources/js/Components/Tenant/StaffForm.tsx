import Checkbox from "@/Components/Checkbox";
import InputError from "@/Components/InputError";
import FormField from "@/Components/UI/FormField";
import TextInput from "@/Components/TextInput";

interface FormData {
    name: string;
    email: string;
    password: string;
    password_confirmation: string;
    phone: string;
    is_active: boolean;
}

interface FormErrors {
    name?: string;
    email?: string;
    password?: string;
    password_confirmation?: string;
    phone?: string;
    is_active?: string;
}

interface StaffFormProps {
    formData: FormData;
    errors: FormErrors;
    onChange: (data: FormData) => void;
    isEdit?: boolean;
}

export default function StaffForm({ formData, errors, onChange, isEdit = false }: StaffFormProps) {
    const idPrefix = isEdit ? "edit-" : "";

    return (
        <div className="space-y-4">
            <FormField
                label="名前"
                htmlFor={`${idPrefix}name`}
                required={!isEdit}
                error={errors.name}
            >
                <TextInput
                    id={`${idPrefix}name`}
                    value={formData.name}
                    placeholder={!isEdit ? "例：山田 太郎" : undefined}
                    aria-invalid={!!errors.name}
                    aria-describedby={errors.name ? `${idPrefix}name-error` : undefined}
                    onChange={(e) => onChange({ ...formData, name: e.target.value })}
                    className="mt-1 block w-full"
                    required
                    isFocused
                />
            </FormField>

            <FormField
                label="メールアドレス"
                htmlFor={`${idPrefix}email`}
                required={!isEdit}
                error={errors.email}
            >
                <TextInput
                    id={`${idPrefix}email`}
                    type="email"
                    value={formData.email}
                    placeholder={!isEdit ? "例：taro@example.com" : undefined}
                    aria-invalid={!!errors.email}
                    aria-describedby={errors.email ? `${idPrefix}email-error` : undefined}
                    onChange={(e) => onChange({ ...formData, email: e.target.value })}
                    className="mt-1 block w-full"
                    required
                />
            </FormField>

            <FormField
                label={isEdit ? "パスワード（変更する場合のみ）" : "パスワード"}
                htmlFor={`${idPrefix}password`}
                required={!isEdit}
                error={errors.password}
            >
                <TextInput
                    id={`${idPrefix}password`}
                    type="password"
                    value={formData.password}
                    aria-invalid={!!errors.password}
                    aria-describedby={errors.password ? `${idPrefix}password-error` : undefined}
                    onChange={(e) => onChange({ ...formData, password: e.target.value })}
                    className="mt-1 block w-full"
                    required={!isEdit}
                    autoComplete="new-password"
                />
            </FormField>

            {!isEdit && (
                <FormField
                    label="パスワード（確認）"
                    htmlFor="password_confirmation"
                    required
                    error={errors.password_confirmation}
                >
                    <TextInput
                        id="password_confirmation"
                        type="password"
                        value={formData.password_confirmation}
                        aria-invalid={!!errors.password_confirmation}
                        aria-describedby={
                            errors.password_confirmation ? "password_confirmation-error" : undefined
                        }
                        onChange={(e) => onChange({ ...formData, password_confirmation: e.target.value })}
                        className="mt-1 block w-full"
                        required
                        autoComplete="new-password"
                    />
                </FormField>
            )}

            <FormField
                label="電話番号（任意）"
                htmlFor={`${idPrefix}phone`}
                error={errors.phone}
            >
                <TextInput
                    id={`${idPrefix}phone`}
                    type="tel"
                    value={formData.phone}
                    placeholder={!isEdit ? "例：090-1234-5678" : undefined}
                    aria-invalid={!!errors.phone}
                    aria-describedby={errors.phone ? `${idPrefix}phone-error` : undefined}
                    onChange={(e) => onChange({ ...formData, phone: e.target.value })}
                    className="mt-1 block w-full"
                />
            </FormField>

            {isEdit && (
                <div>
                    <label className="flex items-center">
                        <Checkbox
                            id="edit-is-active"
                            checked={formData.is_active}
                            aria-invalid={!!errors.is_active}
                            aria-describedby={errors.is_active ? "edit-is-active-error" : undefined}
                            onChange={(e) => onChange({ ...formData, is_active: e.target.checked })}
                        />
                        <span className="ms-2 text-sm text-ink-light">有効</span>
                    </label>
                    <InputError id="edit-is-active-error" message={errors.is_active} className="mt-2" />
                </div>
            )}
        </div>
    );
}
