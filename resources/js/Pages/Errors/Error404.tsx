import { Head } from "@inertiajs/react";
import { router } from "@inertiajs/react";
import ErrorLayout from "@/Components/Error/ErrorLayout";
import Button from "@/Components/UI/Button";
import { ErrorPageProps } from "@/types";

export default function Error404({ auth }: ErrorPageProps) {
    const handleGoHome = () => {
        router.visit("/");
    };

    const handleGoDashboard = () => {
        router.visit(route("dashboard"));
    };

    return (
        <>
            <Head title="ページが見つかりません" />

            <ErrorLayout
                errorCode={404}
                title="ページが見つかりません"
                message="お探しのページは存在しないか、移動した可能性があります。"
            >
                <Button variant="primary" onClick={handleGoHome}>
                    ホームに戻る
                </Button>
                {auth?.user && (
                    <Button variant="secondary" type="button" onClick={handleGoDashboard}>
                        ダッシュボードへ
                    </Button>
                )}
            </ErrorLayout>
        </>
    );
}
