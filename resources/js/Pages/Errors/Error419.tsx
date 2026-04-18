import { Head, router } from "@inertiajs/react";
import ErrorLayout from "@/Components/Error/ErrorLayout";
import PrimaryButton from "@/Components/PrimaryButton";
import SecondaryButton from "@/Components/SecondaryButton";
import { ErrorPageProps } from "@/types";

export default function Error419(_props: ErrorPageProps) {
    const handleReload = () => {
        window.location.reload();
    };

    const handleGoHome = () => {
        router.visit("/");
    };

    return (
        <>
            <Head title="ページの有効期限切れ" />

            <ErrorLayout
                errorCode={419}
                title="ページの有効期限が切れました"
                message="セッションの有効期限が切れました。ページを再読み込みしてから、もう一度お試しください。"
            >
                <PrimaryButton onClick={handleReload}>再読み込み</PrimaryButton>
                <SecondaryButton onClick={handleGoHome}>ホームに戻る</SecondaryButton>
            </ErrorLayout>
        </>
    );
}
