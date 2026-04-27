import { Head, router } from "@inertiajs/react";
import { useTranslation } from "react-i18next";
import ErrorLayout from "@/Components/Error/ErrorLayout";
import Button from "@/Components/UI/Button";
import { ErrorPageProps } from "@/types";

export default function Error419(_props: ErrorPageProps) {
    const { t } = useTranslation("errors");

    const handleReload = () => {
        window.location.reload();
    };

    const handleGoHome = () => {
        router.visit("/");
    };

    return (
        <>
            <Head title={t("419.title")} />

            <ErrorLayout errorCode={419} title={t("419.title")} message={t("419.message")}>
                <Button variant="primary" onClick={handleReload}>
                    {t("actions.reload")}
                </Button>
                <Button variant="secondary" type="button" onClick={handleGoHome}>
                    {t("actions.back_home")}
                </Button>
            </ErrorLayout>
        </>
    );
}
