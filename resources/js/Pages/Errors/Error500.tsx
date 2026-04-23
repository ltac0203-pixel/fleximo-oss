import { Head, router } from "@inertiajs/react";
import ErrorLayout from "@/Components/Error/ErrorLayout";
import Button from "@/Components/UI/Button";
import { ErrorPageProps } from "@/types";

export default function Error500({ status }: ErrorPageProps) {
    const handleReload = () => {
        window.location.reload();
    };

    const handleGoHome = () => {
        router.visit("/");
    };

    return (
        <>
            <Head title="サーバーエラー" />

            <ErrorLayout
                errorCode={status}
                title="サーバーエラーが発生しました"
                message="一時的な問題が発生しています。しばらくしてから再度お試しください。"
            >
                <Button variant="primary" onClick={handleGoHome}>
                    ホームに戻る
                </Button>
                <Button variant="secondary" type="button" onClick={handleReload}>
                    再読み込み
                </Button>
            </ErrorLayout>
        </>
    );
}
