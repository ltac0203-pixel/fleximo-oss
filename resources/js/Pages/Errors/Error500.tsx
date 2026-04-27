import { Head, router } from "@inertiajs/react";
import { useTranslation } from "react-i18next";
import ErrorLayout from "@/Components/Error/ErrorLayout";
import Button from "@/Components/UI/Button";
import { ErrorPageProps } from "@/types";

export default function Error500({ status }: ErrorPageProps) {
    const { t } = useTranslation("errors");

    const handleReload = () => {
        window.location.reload();
    };

    const handleGoHome = () => {
        router.visit("/");
    };

    return (
        <>
            <Head title={t("500.title")} />

            <ErrorLayout errorCode={status} title={t("500.title")} message={t("500.message")}>
                <Button variant="primary" onClick={handleGoHome}>
                    {t("actions.back_home")}
                </Button>
                <Button variant="secondary" type="button" onClick={handleReload}>
                    {t("actions.reload")}
                </Button>
            </ErrorLayout>
        </>
    );
}
