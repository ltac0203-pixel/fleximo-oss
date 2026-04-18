import InputError from "@/Components/InputError";
import InputLabel from "@/Components/InputLabel";
import TextInput from "@/Components/TextInput";

export interface FormData {
    name: string;
    required: boolean;
    min_select: number;
    max_select: number;
    is_active: boolean;
}

export interface FormErrors {
    name?: string;
    required?: string;
    min_select?: string;
    max_select?: string;
    is_active?: string;
}

interface OptionGroupFormProps {
    formData: FormData;
    errors: FormErrors;
    onChange: (data: FormData) => void;
}

export default function OptionGroupForm({ formData, errors, onChange }: OptionGroupFormProps) {
    return (
        <div className="space-y-4">
            <div>
                <InputLabel htmlFor="name" value="グループ名" required />
                <TextInput
                    id="name"
                    value={formData.name}
                    aria-invalid={!!errors.name}
                    aria-describedby={errors.name ? "name-error" : undefined}
                    onChange={(e) => onChange({ ...formData, name: e.target.value })}
                    className="mt-1 block w-full"
                    placeholder="例: サイズ、トッピング"
                    required
                />
                <InputError id="name-error" message={errors.name} className="mt-2" />
            </div>

            <div className="flex items-center">
                <input
                    id="required"
                    type="checkbox"
                    checked={formData.required}
                    aria-invalid={!!errors.required}
                    aria-describedby={errors.required ? "required-error" : undefined}
                    onChange={(e) => onChange({ ...formData, required: e.target.checked })}
                    className="h-4 w-4 text-primary-dark focus:ring-primary border-edge-strong rounded"
                />
                <label htmlFor="required" className="ml-2 block text-sm text-ink">
                    必須にする
                </label>
                <InputError id="required-error" message={errors.required} className="mt-2" />
            </div>

            <div className="grid grid-cols-2 gap-4">
                <div>
                    <InputLabel htmlFor="min_select" value="最小選択数" />
                    <TextInput
                        id="min_select"
                        type="number"
                        min="0"
                        value={formData.min_select}
                        aria-invalid={!!errors.min_select}
                        aria-describedby={errors.min_select ? "min_select-error" : undefined}
                        onChange={(e) =>
                            onChange({
                                ...formData,
                                min_select: parseInt(e.target.value) || 0,
                            })
                        }
                        className="mt-1 block w-full"
                    />
                    <InputError id="min_select-error" message={errors.min_select} className="mt-2" />
                </div>

                <div>
                    <InputLabel htmlFor="max_select" value="最大選択数" />
                    <TextInput
                        id="max_select"
                        type="number"
                        min="1"
                        value={formData.max_select}
                        aria-invalid={!!errors.max_select}
                        aria-describedby={errors.max_select ? "max_select-error" : undefined}
                        onChange={(e) =>
                            onChange({
                                ...formData,
                                max_select: parseInt(e.target.value) || 1,
                            })
                        }
                        className="mt-1 block w-full"
                    />
                    <InputError id="max_select-error" message={errors.max_select} className="mt-2" />
                </div>
            </div>
            <p className="text-xs text-muted">最小選択数=0、最大選択数=1の場合は単一選択になります</p>

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
                    有効にする
                </label>
                <InputError id="is_active-error" message={errors.is_active} className="mt-2" />
            </div>
        </div>
    );
}
