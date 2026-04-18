import InputError from "@/Components/InputError";
import InputLabel from "@/Components/InputLabel";
import TextInput from "@/Components/TextInput";
import { FormData, FormErrors } from "./types";

interface BasicInfoSectionProps {
    formData: FormData;
    errors: FormErrors;
    onChange: (data: FormData) => void;
    onActiveToggleRequest?: (newValue: boolean) => void;
}

export default function BasicInfoSection({ formData, errors, onChange, onActiveToggleRequest }: BasicInfoSectionProps) {
    return (
        <div className="space-y-4">
            <h3 className="text-lg font-medium text-ink">基本情報</h3>

            <div>
                <InputLabel htmlFor="name" value="商品名" required />
                <TextInput
                    id="name"
                    value={formData.name}
                    aria-invalid={!!errors.name}
                    aria-describedby={errors.name ? "name-error" : undefined}
                    onChange={(e) => onChange({ ...formData, name: e.target.value })}
                    className="mt-1 block w-full"
                    required
                />
                <InputError id="name-error" message={errors.name} className="mt-2" />
            </div>

            <div>
                <InputLabel htmlFor="description" value="説明（任意）" />
                <textarea
                    id="description"
                    value={formData.description}
                    aria-invalid={!!errors.description}
                    aria-describedby={errors.description ? "description-error" : undefined}
                    onChange={(e) =>
                        onChange({
                            ...formData,
                            description: e.target.value,
                        })
                    }
                    rows={3}
                    className="mt-1 block w-full rounded-md border-edge-strong focus:border-primary focus:ring-primary sm:text-sm"
                />
                <InputError id="description-error" message={errors.description} className="mt-2" />
            </div>

            <div>
                <InputLabel htmlFor="price" value="価格（税込）" required />
                <div className="mt-1 relative">
                    <TextInput
                        id="price"
                        type="number"
                        min="0"
                        step="1"
                        value={formData.price}
                        aria-invalid={!!errors.price}
                        aria-describedby={
                            ["price-help", errors.price ? "price-error" : ""].filter(Boolean).join(" ") || undefined
                        }
                        onChange={(e) =>
                            onChange({
                                ...formData,
                                price: e.target.value === "" ? "" : parseInt(e.target.value),
                            })
                        }
                        className="block w-full pr-12"
                        required
                    />
                    <div className="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                        <span className="text-muted sm:text-sm">円</span>
                    </div>
                </div>
                <p id="price-help" className="mt-1 text-xs text-muted">
                    1円単位で入力してください
                </p>
                <InputError id="price-error" message={errors.price} className="mt-2" />
            </div>

            <div className="flex items-center">
                <input
                    id="is_active"
                    type="checkbox"
                    checked={formData.is_active}
                    onChange={(e) => {
                        if (onActiveToggleRequest) {
                            onActiveToggleRequest(e.target.checked);
                        } else {
                            onChange({
                                ...formData,
                                is_active: e.target.checked,
                            });
                        }
                    }}
                    className="h-4 w-4 text-primary-dark focus:ring-primary border-edge-strong rounded"
                />
                <label htmlFor="is_active" className="ml-2 block text-sm text-ink">
                    販売中にする
                </label>
            </div>
        </div>
    );
}
