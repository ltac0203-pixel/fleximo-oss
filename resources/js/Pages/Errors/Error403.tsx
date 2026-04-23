import { Head, router } from "@inertiajs/react";
import ErrorLayout from "@/Components/Error/ErrorLayout";
import Button from "@/Components/UI/Button";
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
                        <Button variant="primary" onClick={handleLogout}>
                            ログアウトする
                        </Button>
                        <Button variant="secondary" type="button" onClick={handleGoBack}>
                            前のページへ
                        </Button>
                    </>
                ) : isAuthenticated ? (
                    <>
                        <Button variant="primary" onClick={handleGoHome}>
                            ホームに戻る
                        </Button>
                        <Button variant="secondary" type="button" onClick={handleLogout}>
                            ログアウト
                        </Button>
                    </>
                ) : (
                    <>
                        <Button variant="primary" onClick={handleGoHome}>
                            ホームに戻る
                        </Button>
                        <Button variant="secondary" type="button" onClick={handleGoBack}>
                            前のページへ
                        </Button>
                    </>
                )}
            </ErrorLayout>
        </>
    );
}
