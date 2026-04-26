import { Head, router } from "@inertiajs/react";
import { useTranslation } from "react-i18next";
import ErrorLayout from "@/Components/Error/ErrorLayout";
import Button from "@/Components/UI/Button";
import { ErrorPageProps } from "@/types";

function isTenantRole(role?: string): boolean {
    return role === "tenant_admin" || role === "tenant_staff";
}

export default function Error403(props: ErrorPageProps) {
    const { t } = useTranslation("errors");
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
            <Head title={t("403.title")} />

            <ErrorLayout errorCode={403} title={t("403.title")} message={t("403.message")}>
                {isTenantRole(role) ? (
                    <>
                        <Button variant="primary" onClick={handleLogout}>
                            {t("actions.logout")}
                        </Button>
                        <Button variant="secondary" type="button" onClick={handleGoBack}>
                            {t("actions.back_previous")}
                        </Button>
                    </>
                ) : isAuthenticated ? (
                    <>
                        <Button variant="primary" onClick={handleGoHome}>
                            {t("actions.back_home")}
                        </Button>
                        <Button variant="secondary" type="button" onClick={handleLogout}>
                            {t("actions.logout")}
                        </Button>
                    </>
                ) : (
                    <>
                        <Button variant="primary" onClick={handleGoHome}>
                            {t("actions.back_home")}
                        </Button>
                        <Button variant="secondary" type="button" onClick={handleGoBack}>
                            {t("actions.back_previous")}
                        </Button>
                    </>
                )}
            </ErrorLayout>
        </>
    );
}
