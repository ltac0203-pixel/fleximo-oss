import AuthHeader from "@/Components/UI/AuthHeader";
import Button from "@/Components/UI/Button";
import FormField from "@/Components/UI/FormField";
import TextInput from "@/Components/TextInput";
import GuestLayout from "@/Layouts/GuestLayout";
import { Head, Link, useForm } from "@inertiajs/react";
import { FormEventHandler } from "react";
import { useTranslation } from "react-i18next";

export default function Register() {
    const { t } = useTranslation("auth");
    const { data, setData, post, processing, errors, reset } = useForm({
        name: "",
        email: "",
        password: "",
        password_confirmation: "",
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        post(route("register"), {
            onFinish: () => reset("password", "password_confirmation"),
        });
    };

    return (
        <GuestLayout>
            <Head title={t("register.title")} />

            <AuthHeader eyebrow={t("register.eyebrow")} title={t("register.title")} />

            <form onSubmit={submit}>
                <FormField label={t("register.name_label")} htmlFor="name" error={errors.name}>
                    <TextInput
                        id="name"
                        name="name"
                        value={data.name}
                        placeholder={t("register.name_placeholder")}
                        aria-invalid={!!errors.name}
                        aria-describedby={errors.name ? "name-error" : undefined}
                        className="mt-1 block w-full"
                        autoComplete="name"
                        isFocused={true}
                        onChange={(e) => setData("name", e.target.value)}
                        required
                    />
                </FormField>

                <FormField label={t("register.email_label")} htmlFor="email" error={errors.email} className="mt-4">
                    <TextInput
                        id="email"
                        type="email"
                        name="email"
                        value={data.email}
                        placeholder={t("register.email_placeholder")}
                        aria-invalid={!!errors.email}
                        aria-describedby={errors.email ? "email-error" : undefined}
                        className="mt-1 block w-full"
                        autoComplete="username"
                        onChange={(e) => setData("email", e.target.value)}
                        required
                    />
                </FormField>

                <FormField label={t("register.password_label")} htmlFor="password" error={errors.password} className="mt-4">
                    <TextInput
                        id="password"
                        type="password"
                        name="password"
                        value={data.password}
                        placeholder={t("register.password_placeholder")}
                        aria-invalid={!!errors.password}
                        aria-describedby={errors.password ? "password-error" : undefined}
                        className="mt-1 block w-full"
                        autoComplete="new-password"
                        onChange={(e) => setData("password", e.target.value)}
                        required
                    />
                </FormField>

                <FormField
                    label={t("register.password_confirmation_label")}
                    htmlFor="password_confirmation"
                    error={errors.password_confirmation}
                    className="mt-4"
                >
                    <TextInput
                        id="password_confirmation"
                        type="password"
                        name="password_confirmation"
                        value={data.password_confirmation}
                        placeholder={t("register.password_confirmation_placeholder")}
                        aria-invalid={!!errors.password_confirmation}
                        aria-describedby={errors.password_confirmation ? "password_confirmation-error" : undefined}
                        className="mt-1 block w-full"
                        autoComplete="new-password"
                        onChange={(e) => setData("password_confirmation", e.target.value)}
                        required
                    />
                </FormField>

                <div className="mt-4 flex items-center justify-end">
                    <Link
                        href={route("login")}
                        className="rounded-md text-sm text-ink-light underline hover:text-ink focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2"
                    >
                        {t("register.have_account")}
                    </Link>

                    <Button
                        variant="primary"
                        className="ms-4"
                        disabled={processing}
                        isBusy={processing}
                    >
                        {t("register.submit")}
                    </Button>
                </div>
            </form>
        </GuestLayout>
    );
}
