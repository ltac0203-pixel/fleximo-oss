import { Head } from "@inertiajs/react";
import { useTranslation } from "react-i18next";
import ErrorLayout from "@/Components/Error/ErrorLayout";
import Button from "@/Components/UI/Button";
import { ErrorPageProps } from "@/types";

export default function Error429(_props: ErrorPageProps) {
    const { t } = useTranslation("errors");

    const handleReload = () => {
        window.location.reload();
    };

    return (
        <>
            <Head title={t("429.title")} />

            <ErrorLayout errorCode={429} title={t("429.title")} message={t("429.message")}>
                <Button variant="primary" onClick={handleReload}>
                    {t("actions.reload")}
                </Button>
            </ErrorLayout>
        </>
    );
}
