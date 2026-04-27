import Checkbox from "@/Components/Checkbox";
import AuthHeader from "@/Components/UI/AuthHeader";
import Button from "@/Components/UI/Button";
import FormField from "@/Components/UI/FormField";
import TextInput from "@/Components/TextInput";
import GuestLayout from "@/Layouts/GuestLayout";
import { PageProps } from "@/types";
import { Head, Link, useForm, usePage } from "@inertiajs/react";
import { FormEventHandler } from "react";
import { Trans, useTranslation } from "react-i18next";

export default function BusinessLogin({ status }: { status?: string }) {
    const { t } = useTranslation("auth");
    const { customerLoginUrl } = usePage<
        PageProps & {
            customerLoginUrl?: string;
        }
    >().props;

    const { data, setData, post, processing, errors, reset } = useForm({
        email: "",
        password: "",
        remember: false as boolean,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        post(route("for-business.login"), {
            onFinish: () => reset("password"),
        });
    };

    return (
        <GuestLayout>
            <Head title={t("business_login.title")} />

            <AuthHeader
                title={t("business_login.title")}
                description={t("business_login.description")}
                backHref={route("home")}
                backLabel={t("business_login.back_top")}
            />

            {status && <div className="mb-4 text-sm font-medium text-green-600">{status}</div>}

            {customerLoginUrl && (
                <div className="mb-4 rounded-md bg-sky-50 p-4">
                    <p className="text-sm text-sky-700">
                        <Trans
                            t={t}
                            i18nKey="business_login.customer_hint"
                            components={{
                                1: <Link href={customerLoginUrl} className="font-medium underline hover:text-sky-900" />,
                            }}
                        />
                    </p>
                </div>
            )}

            <form onSubmit={submit}>
                <FormField label={t("business_login.email_label")} htmlFor="email" error={errors.email}>
                    <TextInput
                        id="email"
                        type="email"
                        name="email"
                        value={data.email}
                        aria-invalid={!!errors.email}
                        aria-describedby={errors.email ? "email-error" : undefined}
                        className="mt-1 block w-full"
                        autoComplete="username"
                        isFocused={true}
                        onChange={(e) => setData("email", e.target.value)}
                    />
                </FormField>

                <FormField label={t("business_login.password_label")} htmlFor="password" error={errors.password} className="mt-4">
                    <TextInput
                        id="password"
                        type="password"
                        name="password"
                        value={data.password}
                        aria-invalid={!!errors.password}
                        aria-describedby={errors.password ? "password-error" : undefined}
                        className="mt-1 block w-full"
                        autoComplete="current-password"
                        onChange={(e) => setData("password", e.target.value)}
                    />
                </FormField>

                <div className="mt-4 block">
                    <label className="flex items-center">
                        <Checkbox
                            name="remember"
                            checked={data.remember}
                            onChange={(e) => setData("remember", (e.target.checked || false) as false)}
                        />
                        <span className="ms-2 text-sm text-ink-light">{t("business_login.remember_me")}</span>
                    </label>
                </div>

                <div className="mt-4 flex items-center justify-end">
                    <Button
                        variant="primary"
                        type="submit"
                        disabled={processing}
                        isBusy={processing}
                    >
                        {t("business_login.submit")}
                    </Button>
                </div>

            </form>
        </GuestLayout>
    );
}
