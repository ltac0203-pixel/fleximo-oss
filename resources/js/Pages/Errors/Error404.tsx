import { Head } from "@inertiajs/react";
import { router } from "@inertiajs/react";
import { useTranslation } from "react-i18next";
import ErrorLayout from "@/Components/Error/ErrorLayout";
import Button from "@/Components/UI/Button";
import { ErrorPageProps } from "@/types";

export default function Error404({ auth }: ErrorPageProps) {
    const { t } = useTranslation("errors");

    const handleGoHome = () => {
        router.visit("/");
    };

    const handleGoDashboard = () => {
        router.visit(route("dashboard"));
    };

    return (
        <>
            <Head title={t("404.title")} />

            <ErrorLayout errorCode={404} title={t("404.title")} message={t("404.message")}>
                <Button variant="primary" onClick={handleGoHome}>
                    {t("actions.back_home")}
                </Button>
                {auth?.user && (
                    <Button variant="secondary" type="button" onClick={handleGoDashboard}>
                        {t("actions.to_dashboard")}
                    </Button>
                )}
            </ErrorLayout>
        </>
    );
}
