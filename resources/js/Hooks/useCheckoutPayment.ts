import { api } from "@/api";
import { CheckoutApiResponse, FinalizePaymentApiResponse, PaymentMethod } from "@/types";
import { normalizeErrorMessage } from "@/Utils/errorHelpers";
import { logger } from "@/Utils/logger";
import { router } from "@inertiajs/react";
import { useEffect, useRef, useState } from "react";
import { useFincode } from "./useFincode";
import { useLatest } from "./useLatest";

function isSafeRedirectUrl(url: string): boolean {
    try {
        const parsed = new URL(url, window.location.origin);
        return parsed.protocol === "https:" || parsed.protocol === "http:";
    } catch {
        return false;
    }
}

interface UseCheckoutPaymentParams {
    cartId: number;
    fincodePublicKey: string;
    isProduction: boolean;
    paymentMethod: PaymentMethod | null;
    savedCardId: number | null;
    saveCard: boolean;
    saveAsDefault: boolean;
}

export function useCheckoutPayment({
    cartId,
    fincodePublicKey,
    isProduction,
    paymentMethod,
    savedCardId,
    saveCard,
    saveAsDefault,
}: UseCheckoutPaymentParams) {
    const [isProcessing, setIsProcessing] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const isMountedRef = useRef(true);
    useEffect(() => {
        return () => { isMountedRef.current = false; };
    }, []);

    // async関数内のstale closure問題を避けるため、ref化する。
    const savedCardIdRef = useLatest(savedCardId);
    const saveCardRef = useLatest(saveCard);
    const saveAsDefaultRef = useLatest(saveAsDefault);

    const fincode = useFincode({
        publicKey: fincodePublicKey,
        isProduction,
    });

    const handleFinalizeResponse = (finalizeResponse: FinalizePaymentApiResponse, orderId: number) => {
        if (finalizeResponse.data.requires_3ds_redirect) {
            const redirectUrl = finalizeResponse.data.redirect_url!;
            if (!isSafeRedirectUrl(redirectUrl)) {
                logger.error("Unsafe 3DS redirect URL blocked", null, { orderId });
                router.visit(route("order.checkout.failed", { order: orderId }));
                return;
            }
            window.location.href = redirectUrl;
            return;
        }

        if (finalizeResponse.data.order) {
            router.visit(
                route("order.checkout.complete", {
                    order: finalizeResponse.data.order.id,
                }),
            );
        } else {
            router.visit(route("order.checkout.failed", { order: orderId }));
        }
    };

    const handleCardCheckout = async () => {
        const currentSavedCardId = savedCardIdRef.current;
        const currentSaveCard = saveCardRef.current;
        const currentSaveAsDefault = saveAsDefaultRef.current;
        const useSavedCard = currentSavedCardId !== null;

        const { data: checkoutResponse, error: checkoutError } = await api.post<CheckoutApiResponse>(
            "/api/customer/checkout",
            {
                cart_id: cartId,
                payment_method: "card",
                ...(useSavedCard ? { card_id: currentSavedCardId } : {}),
            },
        );

        if (checkoutError || !checkoutResponse) {
            throw new Error(normalizeErrorMessage(checkoutError, "決済処理に失敗しました"));
        }

        const { payment, order } = checkoutResponse.data;

        // 登録済みカードは再トークン化せず確定へ進み、決済フローを短縮する。
        if (!payment.requires_token) {
            try {
                const { data: finalizeResponse, error: finalizeError } = await api.post<FinalizePaymentApiResponse>(
                    "/api/customer/payments/finalize",
                    { payment_id: payment.id },
                );

                if (finalizeError || !finalizeResponse) {
                    throw new Error(normalizeErrorMessage(finalizeError, "決済の確定に失敗しました"));
                }

                handleFinalizeResponse(finalizeResponse, order.id);
            } catch (e) {
                logger.error("Checkout finalize failed (saved card)", e, {
                    cartId,
                    paymentId: payment.id,
                });
                router.visit(route("order.checkout.failed", { order: order.id }));
            }
            return;
        }

        // 新規入力カードは直接送信せずトークン化して秘匿情報露出を避ける。
        let token: string;
        try {
            token = await fincode.createToken();
        } catch (e) {
            logger.error("Checkout tokenization failed", e, {
                cartId,
                paymentMethod: "card",
            });
            router.visit(route("order.checkout.failed", { order: order.id }));
            return;
        }

        try {
            const { data: finalizeResponse, error: finalizeError } = await api.post<FinalizePaymentApiResponse>(
                "/api/customer/payments/finalize",
                {
                    payment_id: payment.id,
                    token,
                    ...(currentSaveCard ? { save_card: true, save_as_default: currentSaveAsDefault } : {}),
                },
            );

            if (finalizeError || !finalizeResponse) {
                throw new Error(normalizeErrorMessage(finalizeError, "決済の確定に失敗しました"));
            }

            handleFinalizeResponse(finalizeResponse, order.id);
        } catch (e) {
            logger.error("Checkout finalize failed", e, {
                cartId,
                paymentId: payment.id,
            });
            router.visit(route("order.checkout.failed", { order: order.id }));
        }
    };

    const handlePayPayCheckout = async () => {
        const { data: checkoutResponse, error: checkoutError } = await api.post<CheckoutApiResponse>(
            "/api/customer/checkout",
            {
                cart_id: cartId,
                payment_method: "paypay",
            },
        );

        if (checkoutError || !checkoutResponse) {
            throw new Error(normalizeErrorMessage(checkoutError, "決済処理に失敗しました"));
        }

        const { payment } = checkoutResponse.data;

        if (!payment.requires_redirect || !payment.redirect_url) {
            throw new Error("PayPay決済のリダイレクトURLを取得できませんでした");
        }

        if (!isSafeRedirectUrl(payment.redirect_url)) {
            throw new Error("不正なリダイレクト先が検出されました");
        }

        window.location.href = payment.redirect_url;
    };

    const handleCheckout = async () => {
        if (paymentMethod === null) return;
        setIsProcessing(true);
        setError(null);

        try {
            if (paymentMethod === "new_card" || paymentMethod === "saved_card") {
                await handleCardCheckout();
            } else {
                await handlePayPayCheckout();
            }
        } catch (e) {
            logger.error("Checkout failed", e, { cartId, paymentMethod });
            if (isMountedRef.current) {
                const fallbackMessage = "決済処理に失敗しました";
                const rawMessage = e instanceof Error ? e.message : null;
                setError(normalizeErrorMessage(rawMessage, fallbackMessage));
                setIsProcessing(false);
            }
        }
    };

    const isCheckoutDisabled = isProcessing
        || paymentMethod === null
        || (paymentMethod === "new_card" && !fincode.isReady)
        || (paymentMethod === "saved_card" && savedCardId === null);

    return {
        isProcessing,
        error,
        isCheckoutDisabled,
        fincode,
        handleCheckout,
    };
}
