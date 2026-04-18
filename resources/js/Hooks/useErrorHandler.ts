import type { ApiResponse } from "@/api";
import { logger } from "@/Utils/logger";
import type { ToastState } from "@/Hooks/useToast";
import { router } from "@inertiajs/react";
import { useCallback } from "react";

export interface HandleApiErrorOptions {
    loginRedirectPath?: string | false;
    forbiddenMessage?: string | false;
    serverErrorMessage?: string | false;
}

export function useErrorHandler(params?: { showToast?: (state: ToastState) => string }) {
    const showToast = params?.showToast;

    const handleApiError = useCallback(
        <E = unknown>(
            response: ApiResponse<unknown, E>,
            context: string,
            options?: HandleApiErrorOptions,
        ): E | undefined => {
            const { status, error, errorData } = response;
            if (!error) return undefined;

            logger.error(context, error, { status });

            if (status === 401) {
                const path = options?.loginRedirectPath;
                if (path !== false) router.visit(path ?? "/login");
                return undefined;
            }

            if (status === 403) {
                const msg = options?.forbiddenMessage;
                if (msg !== false && showToast) {
                    showToast({
                        type: "error",
                        message: typeof msg === "string" ? msg : "アクセス権限がありません",
                    });
                }
                return undefined;
            }

            if (status === 419) {
                if (showToast) {
                    showToast({
                        type: "error",
                        message: "セッションの有効期限が切れました。ページを再読み込みします。",
                    });
                }
                window.location.reload();
                return undefined;
            }

            if (status === 422) {
                return errorData ?? undefined;
            }

            if (status >= 500) {
                const msg = options?.serverErrorMessage;
                if (msg !== false && showToast) {
                    showToast({
                        type: "error",
                        message: typeof msg === "string" ? msg : "サーバーエラーが発生しました",
                    });
                }
                return undefined;
            }

            if (showToast) showToast({ type: "error", message: error });
            return undefined;
        },
        [showToast],
    );

    return { handleApiError };
}
