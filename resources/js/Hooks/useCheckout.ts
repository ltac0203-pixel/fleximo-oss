import { SavedCard } from "@/types";
import { useCheckoutForm } from "./useCheckoutForm";
import { useCheckoutPayment } from "./useCheckoutPayment";

interface UseCheckoutParams {
    cartId: number;
    tenantId: number;
    fincodePublicKey: string;
    isProduction: boolean;
    savedCards: SavedCard[];
}

export function useCheckout({ cartId, tenantId: _tenantId, fincodePublicKey, isProduction, savedCards }: UseCheckoutParams) {
    const form = useCheckoutForm({ savedCards });
    const payment = useCheckoutPayment({
        cartId,
        fincodePublicKey,
        isProduction,
        paymentMethod: form.paymentMethod,
        savedCardId: form.savedCardId,
        saveCard: form.saveCard,
        saveAsDefault: form.saveAsDefault,
    });

    return { ...form, ...payment };
}
