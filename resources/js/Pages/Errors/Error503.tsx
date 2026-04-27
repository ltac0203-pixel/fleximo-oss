import { Head } from "@inertiajs/react";
import { useTranslation } from "react-i18next";
import ErrorLayout from "@/Components/Error/ErrorLayout";
import Button from "@/Components/UI/Button";
import { ErrorPageProps } from "@/types";

export default function Error503(_props: ErrorPageProps) {
    const { t } = useTranslation("errors");

    const handleReload = () => {
        window.location.reload();
    };

    return (
        <>
            <Head title={t("503.title")} />

            <ErrorLayout errorCode={503} title={t("503.title")} message={t("503.message")}>
                <Button variant="primary" onClick={handleReload}>
                    {t("actions.reload")}
                </Button>
            </ErrorLayout>
        </>
    );
}
