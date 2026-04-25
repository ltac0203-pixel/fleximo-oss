import CardRegistrationForm from "@/Components/Customer/Cards/CardRegistrationForm";
import SavedCardList from "@/Components/Customer/Cards/SavedCardList";
import { useFincode } from "@/Hooks/useFincode";
import { useCardManagement } from "@/Hooks/useCardManagement";
import { CardsIndexProps } from "@/types";
import { Head, Link } from "@inertiajs/react";
import ConfirmDialog from "@/Components/UI/ConfirmDialog";
import { useEffect, useState } from "react";

export default function CardsIndex({ tenant, cards: initialCards, fincodePublicKey, isProduction }: CardsIndexProps) {
    const [confirmDeleteId, setConfirmDeleteId] = useState<number | null>(null);
    const [isDefaultCard, setIsDefaultCard] = useState(true);

    const fincode = useFincode({
        publicKey: fincodePublicKey,
        isProduction,
    });

    const { cards, registerCard, deleteCard, isRegistering, deletingId, error, successMessage } = useCardManagement({
        tenantId: tenant.id,
        initialCards,
        createToken: fincode.createToken,
        clearForm: fincode.clearForm,
    });

    const handleDeleteCard = (cardId: number) => {
        setConfirmDeleteId(null);
        void deleteCard(cardId);
    };

    useEffect(() => {
        if (successMessage) {
            setIsDefaultCard(true);
        }
    }, [successMessage]);

    return (
        <>
            <Head title={`カード管理 - ${tenant.name}`} />

            <div className="min-h-screen bg-slate-50">
                {/* 戻る導線を固定し、モバイルでも迷わず前画面へ戻れるようにする。 */}
                <header className="fixed top-0 left-0 right-0 z-30 bg-white border-b border-slate-200">
                    <div className="h-14 px-4 flex items-center justify-between max-w-lg lg:max-w-5xl mx-auto">
                        <Link
                            href={route("order.menu", { tenant: tenant.slug })}
                            className="text-slate-500 hover:text-slate-700"
                        >
                            <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                    strokeWidth={2}
                                    d="M15 19l-7-7 7-7"
                                />
                            </svg>
                        </Link>
                        <h1 className="text-lg font-semibold text-slate-900">カード管理</h1>
                        <div className="w-6" />
                    </div>
                </header>

                {/* 固定ヘッダーと重ならないよう余白を取り、可読性を維持する。 */}
                <main className="pt-14 pb-8 max-w-lg lg:max-w-5xl mx-auto px-4">
                    <div className="space-y-6 pt-4">
                        {/* 店舗単位管理であることを明示し、カード利用先の誤認を防ぐ。 */}
                        <div className="text-center">
                            <span className="text-sm text-slate-500">{tenant.name} のカード情報</span>
                        </div>

                        {/* 非同期成功をその場で返し、操作完了を即時に伝える。 */}
                        {successMessage && (
                            <div className="p-4 bg-green-50 border border-green-200 ">
                                <div className="flex items-center gap-2">
                                    <svg
                                        className="w-5 h-5 text-green-500"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            strokeLinecap="round"
                                            strokeLinejoin="round"
                                            strokeWidth={2}
                                            d="M5 13l4 4L19 7"
                                        />
                                    </svg>
                                    <span className="text-sm text-green-700">{successMessage}</span>
                                </div>
                            </div>
                        )}

                        {/* 入力や通信失敗を可視化し、再試行判断をしやすくする。 */}
                        {error && (
                            <div className="p-4 bg-red-50 border border-red-200 ">
                                <div className="flex items-center gap-2">
                                    <svg
                                        className="w-5 h-5 text-red-500"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            strokeLinecap="round"
                                            strokeLinejoin="round"
                                            strokeWidth={2}
                                            d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                        />
                                    </svg>
                                    <span className="text-sm text-red-600">{error}</span>
                                </div>
                            </div>
                        )}

                        {/* 既存カード管理を上段に置き、追加前に現状を確認できるようにする。 */}
                        <SavedCardList
                            cards={cards}
                            onDelete={(cardId) => setConfirmDeleteId(cardId)}
                            deletingId={deletingId}
                        />

                        {/* 一覧の直後に配置し、管理フローを一画面で完結させる。 */}
                        <CardRegistrationForm
                            isReady={fincode.isReady}
                            isLoading={fincode.isLoading}
                            error={fincode.error}
                            isSubmitting={isRegistering}
                            isDefault={isDefaultCard}
                            onDefaultChange={setIsDefaultCard}
                            onMount={fincode.mountUI}
                            onUnmount={fincode.unmountUI}
                            onSubmit={() => {
                                void registerCard({ isDefault: isDefaultCard });
                            }}
                        />

                        <ConfirmDialog
                            show={confirmDeleteId !== null}
                            onClose={() => setConfirmDeleteId(null)}
                            onConfirm={() => {
                                if (confirmDeleteId !== null) {
                                    handleDeleteCard(confirmDeleteId);
                                }
                            }}
                            title="カード削除"
                            confirmLabel="削除"
                            tone="danger"
                            processing={deletingId !== null}
                        >
                            <p className="mt-2 text-sm text-muted">このカードを削除しますか？</p>
                        </ConfirmDialog>

                        {/* 運用ルールを事前に示し、問い合わせや誤操作を減らす。 */}
                        <div className="text-xs text-slate-500 space-y-1">
                            <p>・カード情報は各店舗ごとに管理されます</p>
                            <p>・登録したカードは次回以降のお支払いで選択できます</p>
                        </div>
                    </div>
                </main>
            </div>
        </>
    );
}
