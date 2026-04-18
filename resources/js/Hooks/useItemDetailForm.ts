import { CartItemData, CustomerMenuItem, CustomerMenuOption } from "@/types";
import { useEffect, useMemo, useState } from "react";

interface UseItemDetailFormParams {
    show: boolean;
    item: CustomerMenuItem | null;
    onAddToCart: (data: CartItemData) => void;
    onClose: () => void;
}

export function useItemDetailForm({ show, item, onAddToCart, onClose }: UseItemDetailFormParams) {
    const [quantity, setQuantity] = useState(1);
    const [selectedOptionsByGroup, setSelectedOptionsByGroup] = useState<Record<number, number[]>>({});

    // 前回選択の持ち越しを防ぎ、別商品追加時の誤注文を避ける。
    useEffect(() => {
        if (show && item) {
            setQuantity(1);
            const initialOptions: Record<number, number[]> = {};
            item.option_groups.forEach((group) => {
                initialOptions[group.id] = [];
            });
            setSelectedOptionsByGroup(initialOptions);
        }
    }, [show, item]);

    const selectedOptionIds = useMemo(() => {
        return Object.values(selectedOptionsByGroup).flat();
    }, [selectedOptionsByGroup]);

    const selectedOptions = useMemo(() => {
        if (!item) return [];
        const options: CustomerMenuOption[] = [];
        item.option_groups.forEach((group) => {
            const selected = selectedOptionsByGroup[group.id] || [];
            selected.forEach((optionId) => {
                const option = group.options.find((o) => o.id === optionId);
                if (option) {
                    options.push(option);
                }
            });
        });
        return options;
    }, [item, selectedOptionsByGroup]);

    const isValid = useMemo(() => {
        if (!item) return false;
        return item.option_groups.every((group) => {
            const selected = selectedOptionsByGroup[group.id] || [];

            // required=true は min_select が0でも未選択を不可にして、API側の検証と揃える。
            if (group.required && selected.length === 0) {
                return false;
            }

            return selected.length >= group.min_select;
        });
    }, [item, selectedOptionsByGroup]);

    const handleOptionChange = (groupId: number, optionIds: number[]) => {
        setSelectedOptionsByGroup((prev) => ({
            ...prev,
            [groupId]: optionIds,
        }));
    };

    const handleAddToCart = () => {
        if (!item || !isValid) return;

        onAddToCart({
            menuItemId: item.id,
            quantity,
            selectedOptions: selectedOptionIds,
        });

        onClose();
    };

    return {
        quantity,
        setQuantity,
        selectedOptionsByGroup,
        selectedOptions,
        isValid,
        handleOptionChange,
        handleAddToCart,
    };
}
