import InputError from "@/Components/InputError";
import InputLabel from "@/Components/InputLabel";
import TextInput from "@/Components/TextInput";

interface FormData {
    name: string;
    is_active: boolean;
}

interface FormErrors {
    name?: string;
    is_active?: string;
}

interface CategoryFormProps {
    formData: FormData;
    errors: FormErrors;
    onChange: (data: FormData) => void;
}

export default function CategoryForm({ formData, errors, onChange }: CategoryFormProps) {
    return (
        <div className="space-y-4">
            <div>
                <InputLabel htmlFor="name" value="カテゴリ名" />
                <TextInput
                    id="name"
                    value={formData.name}
                    aria-invalid={!!errors.name}
                    aria-describedby={errors.name ? "name-error" : undefined}
                    onChange={(e) => onChange({ ...formData, name: e.target.value })}
                    className="mt-1 block w-full"
                    required
                    isFocused
                />
                <InputError id="name-error" message={errors.name} className="mt-2" />
            </div>

            <div className="flex items-center">
                <input
                    id="is_active"
                    type="checkbox"
                    checked={formData.is_active}
                    aria-invalid={!!errors.is_active}
                    aria-describedby={errors.is_active ? "is_active-error" : undefined}
                    onChange={(e) => onChange({ ...formData, is_active: e.target.checked })}
                    className="h-4 w-4 text-primary-dark focus:ring-primary border-edge-strong rounded"
                />
                <label htmlFor="is_active" className="ml-2 block text-sm text-ink">
                    販売中にする
                </label>
                <InputError id="is_active-error" message={errors.is_active} className="mt-2" />
            </div>
        </div>
    );
}
