import { api } from "@/api";
import { FinalizePaymentApiResponse, PayPayCallbackProps } from "@/types";
import { logger } from "@/Utils/logger";
import ProcessingStatus from "@/Components/Loading/ProcessingStatus";
import { Head, router } from "@inertiajs/react";
import { useEffect, useState } from "react";

const POLL_INTERVAL_MS = 2000;
const MAX_WAIT_MS = 60000;
const BACKOFF_MULTIPLIER = 2;
const MAX_BACKOFF_MS = 12000;

export default function PayPayCallback({ payment, success }: PayPayCallbackProps) {
    const [status, setStatus] = useState<"processing" | "success" | "failed">("processing");
    const [error, setError] = useState<string | null>(null);

    useEffect(() => {
        let timeoutId: ReturnType<typeof setTimeout> | null = null;
        let isDisposed = false;
        const startedAt = Date.now();
        const intervalRef = { current: POLL_INTERVAL_MS };

        const failAndRedirect = (message: string) => {
            if (isDisposed) {
                return;
            }

            setStatus("failed");
            setError(message);
            router.visit(route("order.checkout.failed", { order: payment.order_id }));
        };

        const canRetry = () => Date.now() - startedAt < MAX_WAIT_MS;

        const scheduleRetry = () => {
            if (isDisposed) {
                return;
            }

            timeoutId = setTimeout(() => {
                void finalizePayment();
            }, intervalRef.current);
        };

        const finalizePayment = async () => {
            if (!success) {
                failAndRedirect("決済がキャンセルされたか、失敗しました。");
                return;
            }

            try {
                // 戻り先で確定APIを必ず実行し、外部決済との整合をサーバー正本で確定する。 を明示し、実装意図の誤読を防ぐ。
                const {
                    data: response,
                    error: finalizeError,
                    status: finalizeStatus,
                } = await api.post<FinalizePaymentApiResponse>(
                    "/api/customer/payments/finalize",
                    {
                        payment_id: payment.id,
                    },
                );

                if (finalizeError || !response) {
                    logger.warn("PayPay finalize retryable failure", {
                        paymentId: payment.id,
                        orderId: payment.order_id,
                        status: finalizeStatus,
                        error: finalizeError,
                    });

                    if (finalizeStatus === 401) {
                        failAndRedirect("セッションの有効期限が切れました。お手数ですが再度お試しください。");

                        return;
                    }

                    if (finalizeStatus === 422) {
                        failAndRedirect("PayPay決済の確定に失敗しました。決済状況をご確認のうえ、再度お試しください。");

                        return;
                    }

                    if (finalizeStatus === 429) {
                        intervalRef.current = Math.min(intervalRef.current * BACKOFF_MULTIPLIER, MAX_BACKOFF_MS);
                    }

                    if (!canRetry()) {
                        failAndRedirect(
                            "PayPayでの決済確認がタイムアウトしました。決済が完了している場合は、しばらくしてから注文履歴をご確認ください。",
                        );

                        return;
                    }

                    scheduleRetry();

                    return;
                }

                // Webhook反映待ちの未確定状態では一定時間ポーリングする。
                if (response.data.payment_pending) {
                    intervalRef.current = POLL_INTERVAL_MS;

                    if (!canRetry()) {
                        failAndRedirect(
                            "PayPayでの決済確認がタイムアウトしました。決済が完了している場合は、しばらくしてから注文履歴をご確認ください。",
                        );

                        return;
                    }

                    scheduleRetry();

                    return;
                }

                setStatus("success");

                // 成功時は即時に完了画面へ寄せ、途中状態の再読込を避ける。
                if (response.data.order) {
                    router.visit(
                        route("order.checkout.complete", {
                            order: response.data.order.id,
                        }),
                    );
                }
            } catch (e) {
                logger.error("PayPay finalize failed", e, {
                    paymentId: payment.id,
                    orderId: payment.order_id,
                });
                failAndRedirect("決済の確認中にエラーが発生しました。");
            }
        };

        void finalizePayment();

        return () => {
            isDisposed = true;
            if (timeoutId !== null) {
                clearTimeout(timeoutId);
            }
        };
    }, [payment, success]);

    return (
        <>
            <Head title="決済処理中" />

            <div className="min-h-screen bg-white flex items-center justify-center">
                <div className="max-w-md w-full mx-auto px-4">
                    <ProcessingStatus
                        status={status}
                        processingTitle="決済を確認しています"
                        failedTitle="決済に失敗しました"
                        error={error}
                    />
                </div>
            </div>
        </>
    );
}
