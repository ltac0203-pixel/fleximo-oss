import { BusinessHourRange, MenuCategory, OptionGroup, TenantBusinessHour } from "@/types";

export interface FormData {
    name: string;
    description: string;
    price: number | "";
    is_active: boolean;
    available_from: string | null;
    available_until: string | null;
    available_days: number;
    category_ids: number[];
    option_group_ids: number[];
    allergens: number;
    allergen_advisories: number;
    allergen_note: string;
    nutrition_info: {
        energy: number | "";
        protein: number | "";
        fat: number | "";
        carbohydrate: number | "";
        salt: number | "";
    };
}

export interface FormErrors {
    name?: string;
    description?: string;
    price?: string;
    is_active?: string;
    available_from?: string;
    available_until?: string;
    available_days?: string;
    category_ids?: string;
    option_group_ids?: string;
    allergens?: string;
    allergen_advisories?: string;
    allergen_note?: string;
    'nutrition_info.energy'?: string;
    'nutrition_info.protein'?: string;
    'nutrition_info.fat'?: string;
    'nutrition_info.carbohydrate'?: string;
    'nutrition_info.salt'?: string;
}

export interface ItemFormProps {
    formData: FormData;
    errors: FormErrors;
    categories: MenuCategory[];
    optionGroups: OptionGroup[];
    onChange: (data: FormData) => void;
    todayBusinessHours?: BusinessHourRange[] | null;
    businessHours?: TenantBusinessHour[] | null;
    onActiveToggleRequest?: (newValue: boolean) => void;
}
