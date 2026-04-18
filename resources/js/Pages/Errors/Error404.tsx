import { Head } from "@inertiajs/react";
import { router } from "@inertiajs/react";
import ErrorLayout from "@/Components/Error/ErrorLayout";
import PrimaryButton from "@/Components/PrimaryButton";
import SecondaryButton from "@/Components/SecondaryButton";
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
                <PrimaryButton onClick={handleGoHome}>ホームに戻る</PrimaryButton>
                {auth?.user && <SecondaryButton onClick={handleGoDashboard}>ダッシュボードへ</SecondaryButton>}
            </ErrorLayout>
        </>
    );
}
