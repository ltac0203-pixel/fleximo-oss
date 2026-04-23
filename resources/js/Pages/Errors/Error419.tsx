import { Head, router } from "@inertiajs/react";
import ErrorLayout from "@/Components/Error/ErrorLayout";
import Button from "@/Components/UI/Button";
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
                <Button variant="primary" onClick={handleReload}>
                    再読み込み
                </Button>
                <Button variant="secondary" type="button" onClick={handleGoHome}>
                    ホームに戻る
                </Button>
            </ErrorLayout>
        </>
    );
}
