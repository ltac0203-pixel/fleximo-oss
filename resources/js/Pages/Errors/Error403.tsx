import { Head, router } from "@inertiajs/react";
import ErrorLayout from "@/Components/Error/ErrorLayout";
import PrimaryButton from "@/Components/PrimaryButton";
import SecondaryButton from "@/Components/SecondaryButton";
import { ErrorPageProps } from "@/types";

function isTenantRole(role?: string): boolean {
    return role === "tenant_admin" || role === "tenant_staff";
}

export default function Error403(props: ErrorPageProps) {
    const role = props.auth?.user?.role;
    const isAuthenticated = !!props.auth?.user;

    const handleLogout = () => {
        router.post(route("logout"));
    };

    const handleGoHome = () => {
        router.visit("/");
    };

    const handleGoBack = () => {
        window.history.back();
    };

    return (
        <>
            <Head title="アクセス権限がありません" />

            <ErrorLayout
                errorCode={403}
                title="アクセス権限がありません"
                message="このページにアクセスする権限がありません。ログイン状態やアカウントの権限をご確認ください。"
            >
                {isTenantRole(role) ? (
                    <>
                        <PrimaryButton onClick={handleLogout}>ログアウトする</PrimaryButton>
                        <SecondaryButton onClick={handleGoBack}>前のページへ</SecondaryButton>
                    </>
                ) : isAuthenticated ? (
                    <>
                        <PrimaryButton onClick={handleGoHome}>ホームに戻る</PrimaryButton>
                        <SecondaryButton onClick={handleLogout}>ログアウト</SecondaryButton>
                    </>
                ) : (
                    <>
                        <PrimaryButton onClick={handleGoHome}>ホームに戻る</PrimaryButton>
                        <SecondaryButton onClick={handleGoBack}>前のページへ</SecondaryButton>
                    </>
                )}
            </ErrorLayout>
        </>
    );
}
