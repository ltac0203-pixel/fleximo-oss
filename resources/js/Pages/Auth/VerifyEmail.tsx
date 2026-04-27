import AuthHeader from "@/Components/UI/AuthHeader";
import Button from "@/Components/UI/Button";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { PageProps } from "@/types";
import { Head, Link, useForm } from "@inertiajs/react";
import { FormEventHandler } from "react";
import { useTranslation } from "react-i18next";

interface VerifyEmailProps extends PageProps {
    status?: string;
}

export default function VerifyEmail({ status }: VerifyEmailProps) {
    const { t } = useTranslation("auth");
    const { post, processing } = useForm({});

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        post(route("verification.send"));
    };

    return (
        <AuthenticatedLayout>
            <Head title={t("verify_email.title")} />

            <div className="mx-auto max-w-2xl px-4 py-6">
                <AuthHeader eyebrow={t("verify_email.eyebrow")} title={t("verify_email.title")} />

                <div className="mb-4 text-sm text-ink-light">
                    {t("verify_email.description")}
                </div>

                {status === "verification-link-sent" && (
                    <div className="mb-4 border border-green-200 bg-green-50 p-3 text-sm font-medium text-green-700">
                        {t("verify_email.status_sent")}
                    </div>
                )}

                {status === "verification-link-failed" && (
                    <div className="mb-4 border border-red-200 bg-red-50 p-3 text-sm font-medium text-red-700">
                        {t("verify_email.status_failed")}
                    </div>
                )}

                <form onSubmit={submit}>
                    <div className="mt-4 flex items-center justify-between">
                        <Button variant="primary" disabled={processing} isBusy={processing}>
                            {t("verify_email.resend")}
                        </Button>

                        <Link
                            href={route("logout")}
                            method="post"
                            as="button"
                            className="text-sm text-ink-light underline hover:text-ink focus:outline-none"
                        >
                            {t("verify_email.logout")}
                        </Link>
                    </div>
                </form>
            </div>
        </AuthenticatedLayout>
    );
}
