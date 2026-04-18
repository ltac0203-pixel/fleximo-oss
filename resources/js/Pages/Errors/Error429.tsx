import { Head } from "@inertiajs/react";
import ErrorLayout from "@/Components/Error/ErrorLayout";
import PrimaryButton from "@/Components/PrimaryButton";
import { ErrorPageProps } from "@/types";

export default function Error429(_props: ErrorPageProps) {
    const handleReload = () => {
        window.location.reload();
    };

    return (
        <>
            <Head title="リクエスト制限" />

            <ErrorLayout
                errorCode={429}
                title="リクエストが多すぎます"
                message="短時間に多くのリクエストが送信されました。しばらく時間をおいてから再度お試しください。"
            >
                <PrimaryButton onClick={handleReload}>再読み込み</PrimaryButton>
            </ErrorLayout>
        </>
    );
}
