import type { ApiResponse } from "@/api";
import { useErrorHandler } from "@/Hooks/useErrorHandler";
import type { HandleApiErrorOptions } from "@/Hooks/useErrorHandler";
import type { ToastState } from "@/Hooks/useToast";
import { logger } from "@/Utils/logger";
import { useState } from "react";

interface SubmitOptions<T, E> {
    onSuccess?: (response: ApiResponse<T, { errors?: E }>) => void;
    logMessage: string;
    logContext?: Record<string, unknown>;
    networkErrorMessage?: string;
    errorHandlerOptions?: HandleApiErrorOptions;
}

const DEFAULT_NETWORK_ERROR_MESSAGE = "通信エラーが発生しました。もう一度お試しください。";

export function useApiFormSubmission<E extends object>(params?: {
    showToast?: (state: ToastState) => string;
}) {
    const [processing, setProcessing] = useState(false);
    const [errors, setErrors] = useState<Partial<E>>({});
    const [generalError, setGeneralError] = useState("");
    const { handleApiError } = useErrorHandler({ showToast: params?.showToast });

    const submit = async <T>(
        request: () => Promise<ApiResponse<T, { errors?: E }>>,
        options: SubmitOptions<T, E>,
    ): Promise<ApiResponse<T, { errors?: E }> | null> => {
        setProcessing(true);
        setErrors({});
        setGeneralError("");

        try {
            const response = await request();

            if (response.error) {
                const errorResult = handleApiError(
                    response,
                    options.logMessage,
                    options.errorHandlerOptions,
                );

                if (response.status === 422) {
                    const data = errorResult as { errors?: E } | undefined;
                    setErrors(data?.errors ?? {});
                    return response;
                }

                setGeneralError(response.error);
                return response;
            }

            options.onSuccess?.(response);
            return response;
        } catch (error) {
            logger.error(options.logMessage, error, options.logContext);
            setGeneralError(options.networkErrorMessage || DEFAULT_NETWORK_ERROR_MESSAGE);
            return null;
        } finally {
            setProcessing(false);
        }
    };

    return {
        processing,
        errors,
        generalError,
        setGeneralError,
        submit,
    };
}
