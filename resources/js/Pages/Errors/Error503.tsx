import { Head } from "@inertiajs/react";
import ErrorLayout from "@/Components/Error/ErrorLayout";
import PrimaryButton from "@/Components/PrimaryButton";
import { ErrorPageProps } from "@/types";

export default function Error503(_props: ErrorPageProps) {
    const handleReload = () => {
        window.location.reload();
    };

    return (
        <>
            <Head title="メンテナンス中" />

            <ErrorLayout
                errorCode={503}
                title="ただいまメンテナンス中です"
                message="サービスの改善作業を行っています。しばらくしてから再度アクセスしてください。"
            >
                <PrimaryButton onClick={handleReload}>再読み込み</PrimaryButton>
            </ErrorLayout>
        </>
    );
}
