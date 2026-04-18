import { PaymentMethod, SavedCard } from "@/types";
import { Dispatch, SetStateAction, useCallback, useMemo, useState } from "react";

interface UseCheckoutFormParams {
    savedCards: SavedCard[];
}

export interface UseCheckoutFormReturn {
    paymentMethod: PaymentMethod | null;
    setPaymentMethod: Dispatch<SetStateAction<PaymentMethod | null>>;
    savedCardId: number | null;
    setSavedCardId: Dispatch<SetStateAction<number | null>>;
    saveCard: boolean;
    setSaveCard: Dispatch<SetStateAction<boolean>>;
    saveAsDefault: boolean;
    setSaveAsDefault: Dispatch<SetStateAction<boolean>>;
}

function resolveSetStateAction<T>(value: SetStateAction<T>, currentValue: T): T {
    return typeof value === "function" ? (value as (prevState: T) => T)(currentValue) : value;
}

export function useCheckoutForm({ savedCards }: UseCheckoutFormParams): UseCheckoutFormReturn {
    const defaultCard = savedCards.find((c) => c.is_default);
    const defaultSavedCardId = defaultCard?.id ?? (savedCards.length > 0 ? savedCards[0].id : null);

    const [paymentMethod, setPaymentMethod] = useState<PaymentMethod | null>(
        savedCards.length > 0 ? "saved_card" : null,
    );
    const [saveCard, setSaveCard] = useState(false);
    const [saveAsDefaultRequested, setSaveAsDefaultRequested] = useState(false);
    const [selectedSavedCardId, setSelectedSavedCardId] = useState<number | null>(defaultSavedCardId);

    const savedCardId = useMemo(() => {
        if (paymentMethod !== "saved_card") {
            return null;
        }

        if (selectedSavedCardId !== null && savedCards.some((card) => card.id === selectedSavedCardId)) {
            return selectedSavedCardId;
        }

        return defaultSavedCardId;
    }, [defaultSavedCardId, paymentMethod, savedCards, selectedSavedCardId]);

    const saveAsDefault = saveCard ? saveAsDefaultRequested : false;

    const handleSetSaveCard = useCallback<Dispatch<SetStateAction<boolean>>>((value) => {
        setSaveCard((currentValue) => {
            const nextValue = resolveSetStateAction(value, currentValue);

            if (!nextValue) {
                setSaveAsDefaultRequested(false);
            }

            return nextValue;
        });
    }, []);

    const handleSetSaveAsDefault = useCallback<Dispatch<SetStateAction<boolean>>>((value) => {
        setSaveAsDefaultRequested((currentValue) => resolveSetStateAction(value, currentValue));
    }, []);

    return {
        paymentMethod,
        setPaymentMethod,
        savedCardId,
        setSavedCardId: setSelectedSavedCardId,
        saveCard,
        setSaveCard: handleSetSaveCard,
        saveAsDefault,
        setSaveAsDefault: handleSetSaveAsDefault,
    };
}
