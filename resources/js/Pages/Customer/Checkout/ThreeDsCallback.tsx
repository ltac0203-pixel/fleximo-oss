import { api } from "@/api";
import { ThreeDsCallbackApiResponse, ThreeDsCallbackProps } from "@/types";
import { logger } from "@/Utils/logger";
import ProcessingStatus from "@/Components/Loading/ProcessingStatus";
import { Head, router } from "@inertiajs/react";
import { useEffect, useState } from "react";

export default function ThreeDsCallback({ payment, param, event }: ThreeDsCallbackProps) {
    const [status, setStatus] = useState<"processing" | "success" | "failed">("processing");
    const [error, setError] = useState<string | null>(null);

    useEffect(() => {
        let timeoutId: ReturnType<typeof setTimeout> | null = null;
        let isDisposed = false;

        const processCallback = async () => {
            if (!param) {
                setStatus("failed");
                setError("3DS認証パラメータがありません。");
                router.visit(route("order.checkout.failed", { order: payment.order_id }));
                return;
            }

            try {
                // ブラウザ復帰後に確定処理を一本化し、3DS後の状態不整合を防ぐ。
                const { data: response, error: callbackError } = await api.post<ThreeDsCallbackApiResponse>(
                    "/api/customer/payments/3ds-callback",
                    {
                        payment_id: payment.id,
                        param: param,
                        event: event || null,
                    },
                );

                if (callbackError) {
                    throw new Error(callbackError ?? "3DS認証に失敗しました。別のカードをお試しください。");
                }

                // チャレンジ認証が要求された場合、チャレンジURLへリダイレクトする
                if (response?.data?.requires_3ds_redirect && response?.data?.redirect_url) {
                    window.location.href = response.data.redirect_url;
                    return;
                }

                if (!response?.data?.order) {
                    throw new Error("3DS認証に失敗しました。別のカードをお試しください。");
                }

                if (isDisposed) return;

                setStatus("success");

                // 完了導線を即時に統一し、二重送信や画面滞留を防ぐ。
                router.visit(
                    route("order.checkout.complete", {
                        order: response.data.order.id,
                    }),
                );
            } catch (e) {
                logger.error("3DS callback failed", e, {
                    paymentId: payment.id,
                    orderId: payment.order_id,
                });

                if (isDisposed) return;

                setStatus("failed");
                setError("3DS認証に失敗しました。別のカードをお試しください。");

                // 失敗内容を短時間表示してから遷移し、原因を認識できる余地を残す。
                timeoutId = setTimeout(() => {
                    if (isDisposed) return;
                    router.visit(
                        route("order.checkout.failed", {
                            order: payment.order_id,
                        }),
                    );
                }, 2000);
            }
        };

        void processCallback();

        return () => {
            isDisposed = true;
            if (timeoutId !== null) {
                clearTimeout(timeoutId);
            }
        };
    }, [payment, param, event]);

    return (
        <>
            <Head title="3DS認証処理中" />

            <div className="min-h-screen bg-white flex items-center justify-center">
                <div className="max-w-md w-full mx-auto px-4">
                    <ProcessingStatus
                        status={status}
                        processingTitle="3DS認証を確認しています"
                        failedTitle="3DS認証に失敗しました"
                        error={error}
                    />
                </div>
            </div>
        </>
    );
}
