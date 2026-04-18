import { api } from "@/api";
import { ENDPOINTS } from "@/api/endpoints";
import { SavedCard } from "@/types";
import { logger } from "@/Utils/logger";
import { useCallback, useEffect, useRef, useState } from "react";

interface UseCardManagementParams {
    tenantId: number;
    initialCards: SavedCard[];
    createToken: () => Promise<string>;
    clearForm: () => void;
}

interface UseCardManagementReturn {
    cards: SavedCard[];
    registerCard: (options?: { isDefault?: boolean }) => Promise<void>;
    deleteCard: (cardId: number) => Promise<void>;
    isRegistering: boolean;
    deletingId: number | null;
    error: string | null;
    successMessage: string | null;
}

export function useCardManagement({
    tenantId,
    initialCards,
    createToken,
    clearForm,
}: UseCardManagementParams): UseCardManagementReturn {
    const [cards, setCards] = useState<SavedCard[]>(initialCards);
    const [isRegistering, setIsRegistering] = useState(false);
    const [deletingId, setDeletingId] = useState<number | null>(null);
    const [error, setError] = useState<string | null>(null);
    const [successMessage, setSuccessMessage] = useState<string | null>(null);
    const timerRef = useRef<ReturnType<typeof setTimeout> | null>(null);

    useEffect(() => {
        return () => {
            if (timerRef.current !== null) {
                clearTimeout(timerRef.current);
            }
        };
    }, []);

    const showTemporarySuccess = useCallback((message: string) => {
        setSuccessMessage(message);
        if (timerRef.current !== null) {
            clearTimeout(timerRef.current);
        }
        timerRef.current = setTimeout(() => {
            setSuccessMessage(null);
            timerRef.current = null;
        }, 3000);
    }, []);

    const registerCard = async (options?: { isDefault?: boolean }) => {
        setIsRegistering(true);
        setError(null);
        setSuccessMessage(null);

        try {
            const token = await createToken();

            const {
                data: response,
                error: registerError,
                status,
            } = await api.post<{ data: SavedCard }>(ENDPOINTS.customer.cards(tenantId), {
                token,
                is_default: options?.isDefault ?? true,
            });

            if (registerError || !response?.data) {
                const logContext = { tenantId, status };
                if (status >= 400 && status < 500) {
                    logger.warn("Card registration failed", {
                        ...logContext,
                        message: registerError,
                    });
                } else {
                    logger.error(
                        "Card registration failed",
                        new Error(registerError ?? "カード登録に失敗しました"),
                        logContext,
                    );
                }
                setError(registerError ?? "カード登録に失敗しました");
                return;
            }

            setCards((prevCards) => [response.data, ...prevCards.map((c) => ({ ...c, is_default: false }))]);
            showTemporarySuccess("カードを登録しました");
            clearForm();
        } catch (e) {
            logger.error("Card registration failed", e, { tenantId });
            setError(e instanceof Error ? e.message : "カード登録に失敗しました");
        } finally {
            setIsRegistering(false);
        }
    };

    const deleteCard = async (cardId: number) => {
        setDeletingId(cardId);
        setError(null);
        setSuccessMessage(null);

        try {
            const { error: deleteError, status } = await api.delete(ENDPOINTS.customer.card(tenantId, cardId));

            if (deleteError) {
                const logContext = { tenantId, cardId, status };
                if (status >= 400 && status < 500) {
                    logger.warn("Card deletion failed", {
                        ...logContext,
                        message: deleteError,
                    });
                } else {
                    logger.error("Card deletion failed", new Error(deleteError), logContext);
                }
                setError(deleteError);
                return;
            }

            setCards((prevCards) => prevCards.filter((c) => c.id !== cardId));
            showTemporarySuccess("カードを削除しました");
        } catch (e) {
            logger.error("Card deletion failed", e, { tenantId, cardId });
            setError(e instanceof Error ? e.message : "カード削除に失敗しました");
        } finally {
            setDeletingId(null);
        }
    };

    return { cards, registerCard, deleteCard, isRegistering, deletingId, error, successMessage };
}
