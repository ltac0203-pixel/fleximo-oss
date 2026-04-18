import { api } from "@/api";
import { router } from "@inertiajs/react";
import { useState } from "react";

interface UseDeleteActionParams {
    apiEndpoint: string;
    reloadOnly: string[];
    successMessage: string;
    onSuccess?: (message: string) => void;
    onClose?: () => void;
}

interface UseDeleteActionReturn {
    executeDelete: () => Promise<void>;
    processing: boolean;
    error: string;
    resetError: () => void;
}

export function useDeleteAction({
    apiEndpoint,
    reloadOnly,
    successMessage,
    onSuccess,
    onClose,
}: UseDeleteActionParams): UseDeleteActionReturn {
    const [processing, setProcessing] = useState(false);
    const [error, setError] = useState("");

    const executeDelete = async () => {
        setProcessing(true);
        setError("");

        try {
            const { error: deleteError } = await api.delete(apiEndpoint);

            if (deleteError) {
                setError("削除に失敗しました。もう一度お試しください。");
                return;
            }

            router.reload({ only: reloadOnly });
            onSuccess?.(successMessage);
            onClose?.();
        } catch {
            setError("削除に失敗しました。もう一度お試しください。");
        } finally {
            setProcessing(false);
        }
    };

    const resetError = () => {
        setError("");
    };

    return { executeDelete, processing, error, resetError };
}
