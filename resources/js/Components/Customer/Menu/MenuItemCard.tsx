import { memo, useCallback, type MutableRefObject } from "react";
import { CustomerMenuItem } from "@/types";
import { formatPrice } from "@/Utils/formatPrice";
import AllergenBadge from "./AllergenBadge";

interface MenuItemCardProps {
    item: CustomerMenuItem;
    onItemClickRef: MutableRefObject<(item: CustomerMenuItem) => void>;
}

function hasSameOptionGroups(previousItem: CustomerMenuItem, nextItem: CustomerMenuItem): boolean {
    if (previousItem.option_groups.length !== nextItem.option_groups.length) {
        return false;
    }

    for (let groupIndex = 0; groupIndex < previousItem.option_groups.length; groupIndex++) {
        const previousGroup = previousItem.option_groups[groupIndex];
        const nextGroup = nextItem.option_groups[groupIndex];

        if (
            previousGroup.id !== nextGroup.id ||
            previousGroup.name !== nextGroup.name ||
            previousGroup.required !== nextGroup.required ||
            previousGroup.min_select !== nextGroup.min_select ||
            previousGroup.max_select !== nextGroup.max_select ||
            previousGroup.options.length !== nextGroup.options.length
        ) {
            return false;
        }

        for (let optionIndex = 0; optionIndex < previousGroup.options.length; optionIndex++) {
            const previousOption = previousGroup.options[optionIndex];
            const nextOption = nextGroup.options[optionIndex];

            if (
                previousOption.id !== nextOption.id ||
                previousOption.name !== nextOption.name ||
                previousOption.price !== nextOption.price
            ) {
                return false;
            }
        }
    }

    return true;
}

function hasSameItem(previousItem: CustomerMenuItem, nextItem: CustomerMenuItem): boolean {
    if (previousItem === nextItem) {
        return true;
    }

    return (
        previousItem.id === nextItem.id &&
        previousItem.name === nextItem.name &&
        previousItem.description === nextItem.description &&
        previousItem.price === nextItem.price &&
        previousItem.is_sold_out === nextItem.is_sold_out &&
        previousItem.is_available === nextItem.is_available &&
        previousItem.available_from === nextItem.available_from &&
        previousItem.available_until === nextItem.available_until &&
        previousItem.available_days === nextItem.available_days &&
        previousItem.allergens === nextItem.allergens &&
        previousItem.allergen_advisories === nextItem.allergen_advisories &&
        previousItem.allergen_note === nextItem.allergen_note &&
        hasSameOptionGroups(previousItem, nextItem)
    );
}

function areMenuItemCardPropsEqual(previousProps: MenuItemCardProps, nextProps: MenuItemCardProps): boolean {
    return previousProps.onItemClickRef === nextProps.onItemClickRef && hasSameItem(previousProps.item, nextProps.item);
}

type OverlayVariant = "soldOut" | "unavailable";

const OVERLAY_VARIANTS: Record<OverlayVariant, { bg: string; label: string; labelClass: string }> = {
    soldOut: {
        bg: "bg-slate-900/25 backdrop-blur-[1px]",
        labelClass: "border border-rose-300/70 bg-rose-600 px-3 py-1 text-sm font-bold text-white",
        label: "売切",
    },
    unavailable: {
        bg: "bg-slate-900/20",
        labelClass: "border border-edge/80 bg-slate-700 px-2.5 py-1 text-xs font-bold text-white",
        label: "時間外",
    },
};

function ItemOverlay({ variant }: { variant: OverlayVariant }) {
    const v = OVERLAY_VARIANTS[variant];
    return (
        <div className={`absolute inset-0 flex items-center justify-center ${v.bg}`} aria-hidden="true">
            <span className={v.labelClass}>{v.label}</span>
        </div>
    );
}

function MenuItemCard({ item, onItemClickRef }: MenuItemCardProps) {
    const isDisabled = item.is_sold_out || !item.is_available;
    const hasOptions = item.option_groups.length > 0;
    const hasAllergenInfo = item.allergens > 0 || item.allergen_advisories > 0;
    const formattedPrice = formatPrice(item.price);

    const handleClick = useCallback(() => {
        if (!isDisabled) {
            onItemClickRef.current(item);
        }
    }, [item, onItemClickRef, isDisabled]);

    return (
        <button
            onClick={handleClick}
            disabled={isDisabled}
            aria-label={`${item.name} ${formattedPrice}${hasOptions ? " オプションあり" : ""}${item.is_sold_out ? " 売切" : ""}${
                !item.is_sold_out && !item.is_available ? " 時間外のため注文できません" : ""
            }`}
            className={`geo-surface relative flex h-[126px] w-full flex-col items-start border p-2.5 text-left select-none sm:p-3.5 lg:h-[128px] lg:p-4 ${
                isDisabled
                    ? "cursor-not-allowed border-edge bg-surface-dim/80 opacity-80"
                    : "geo-hover-frame border-edge bg-white hover:shadow-geo-sky"
            }`}
        >
            {!isDisabled && (
                <>
                    <span
                        aria-hidden="true"
                        className="absolute top-0 left-0 h-0.5 w-full bg-gradient-to-r from-sky-500 via-cyan-400 to-sky-500"
                    />
                    <span
                        aria-hidden="true"
                        className="absolute top-2 right-2 h-2.5 w-2.5 border border-cyan-200/80 bg-cyan-100/70"
                    />
                </>
            )}

            <span
                aria-hidden="true"
                className={`line-clamp-2 text-sm leading-tight font-semibold sm:text-base ${isDisabled ? "text-muted-light" : "text-ink"}`}
            >
                {item.name}
            </span>

            {item.description && (
                <span
                    aria-hidden="true"
                    className={`mt-1 line-clamp-1 text-xs leading-relaxed ${isDisabled ? "text-muted-light" : "text-muted"}`}
                >
                    {item.description}
                </span>
            )}

            <span
                aria-hidden="true"
                className={`mt-1 text-base font-bold sm:text-lg ${isDisabled ? "text-muted-light" : "text-sky-700"}`}
            >
                {formattedPrice}
            </span>

            <div className="mt-auto flex w-full flex-wrap items-center gap-1.5 pt-2" aria-hidden="true">
                {hasOptions && (
                    <span
                        className={`border px-1.5 py-0.5 text-[11px] font-medium ${
                            isDisabled
                                ? "border-edge bg-surface-dim text-muted-light"
                                : "border-cyan-200 bg-cyan-50 text-cyan-700"
                        }`}
                    >
                        オプションあり
                    </span>
                )}
                {hasAllergenInfo && (
                    <AllergenBadge
                        allergens={item.allergens}
                        allergenAdvisories={item.allergen_advisories}
                        mode="compact"
                    />
                )}
                {!item.is_sold_out && !item.is_available && (
                    <span className="border border-edge bg-surface-dim px-1.5 py-0.5 text-[11px] font-medium text-ink-light">
                        時間外
                    </span>
                )}
            </div>

            {item.is_sold_out && <ItemOverlay variant="soldOut" />}
            {!item.is_sold_out && !item.is_available && <ItemOverlay variant="unavailable" />}
        </button>
    );
}

export default memo(MenuItemCard, areMenuItemCardPropsEqual);
