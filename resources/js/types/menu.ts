import type { PageProps } from "./common";
import type { Tenant, TenantPageProps } from "./tenant";

export interface MenuCategory {
    id: number;
    name: string;
    sort_order: number;
    is_active: boolean;
}

export interface Option {
    id: number;
    option_group_id: number;
    name: string;
    price: number;
    sort_order: number;
    is_active: boolean;
}

export interface OptionGroup {
    id: number;
    name: string;
    required: boolean;
    min_select: number;
    max_select: number;
    sort_order: number;
    is_active: boolean;
    options?: Option[];
}

export interface NutritionInfo {
    energy?: number | null;
    protein?: number | null;
    fat?: number | null;
    carbohydrate?: number | null;
    salt?: number | null;
}

export interface MenuItem {
    id: number;
    name: string;
    description: string | null;
    price: number;
    is_active: boolean;
    is_sold_out: boolean;
    available_from: string | null;
    available_until: string | null;
    available_days: number;
    sort_order: number;
    categories?: MenuCategory[];
    option_groups?: OptionGroup[];
    allergens?: number;
    allergen_advisories?: number;
    allergen_labels?: string[];
    advisory_labels?: string[];
    allergen_note?: string | null;
    nutrition_info?: NutritionInfo | null;
}

export interface MenuCategoriesIndexProps extends TenantPageProps {
    categories: MenuCategory[];
}

export interface MenuItemsIndexProps extends TenantPageProps {
    items: MenuItem[];
    categories: MenuCategory[];
}

export interface MenuItemCreateProps extends TenantPageProps {
    categories: MenuCategory[];
    optionGroups: OptionGroup[];
}

export interface MenuItemEditProps extends TenantPageProps {
    item: MenuItem;
    categories: MenuCategory[];
    optionGroups: OptionGroup[];
}

export interface OptionGroupsIndexProps extends TenantPageProps {
    optionGroups: OptionGroup[];
}

export interface OptionGroupCreateProps extends TenantPageProps {}

export interface OptionGroupEditProps extends TenantPageProps {
    optionGroup: OptionGroup;
}

export interface CustomerMenuOption {
    id: number;
    name: string;
    price: number;
}

export interface CustomerMenuOptionGroup {
    id: number;
    name: string;
    required: boolean;
    min_select: number;
    max_select: number;
    options: CustomerMenuOption[];
}

export interface CustomerMenuItem {
    id: number;
    name: string;
    description: string | null;
    price: number;
    is_sold_out: boolean;
    is_available: boolean;
    available_from: string | null;
    available_until: string | null;
    available_days: number;
    option_groups: CustomerMenuOptionGroup[];
    allergens: number;
    allergen_advisories: number;
    allergen_labels: string[];
    advisory_labels: string[];
    allergen_note: string | null;
    nutrition_info: NutritionInfo | null;
}

export interface CustomerMenuCategory {
    id: number;
    name: string;
    sort_order: number;
    items: CustomerMenuItem[];
}

export interface CustomerMenuResponse {
    categories: CustomerMenuCategory[];
}

export interface CustomerMenuPageProps extends PageProps {
    tenant: Tenant;
    menu: CustomerMenuResponse;
}
