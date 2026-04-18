import Checkbox from "@/Components/Checkbox";
import InputError from "@/Components/InputError";
import InputLabel from "@/Components/InputLabel";
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
            <div>
                <InputLabel htmlFor={`${idPrefix}name`} value="名前" required={!isEdit} />
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
                <InputError id={`${idPrefix}name-error`} message={errors.name} className="mt-2" />
            </div>

            <div>
                <InputLabel htmlFor={`${idPrefix}email`} value="メールアドレス" required={!isEdit} />
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
                <InputError id={`${idPrefix}email-error`} message={errors.email} className="mt-2" />
            </div>

            <div>
                <InputLabel
                    htmlFor={`${idPrefix}password`}
                    value={isEdit ? "パスワード（変更する場合のみ）" : "パスワード"}
                    required={!isEdit}
                />
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
                <InputError id={`${idPrefix}password-error`} message={errors.password} className="mt-2" />
            </div>

            {!isEdit && (
                <div>
                    <InputLabel htmlFor="password_confirmation" value="パスワード（確認）" required />
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
                    <InputError
                        id="password_confirmation-error"
                        message={errors.password_confirmation}
                        className="mt-2"
                    />
                </div>
            )}

            <div>
                <InputLabel htmlFor={`${idPrefix}phone`} value="電話番号（任意）" />
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
                <InputError id={`${idPrefix}phone-error`} message={errors.phone} className="mt-2" />
            </div>

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
