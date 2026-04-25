import AuthHeader from "@/Components/UI/AuthHeader";
import Button from "@/Components/UI/Button";
import FormField from "@/Components/UI/FormField";
import TextInput from "@/Components/TextInput";
import GuestLayout from "@/Layouts/GuestLayout";
import { PageProps } from "@/types";
import { Head, useForm } from "@inertiajs/react";
import { FormEventHandler } from "react";

interface ResetPasswordProps extends PageProps {
    token: string;
    email: string;
}

export default function ResetPassword({ token, email }: ResetPasswordProps) {
    const { data, setData, post, processing, errors, reset } = useForm({
        token: token,
        email: email,
        password: "",
        password_confirmation: "",
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        post(route("password.store"), {
            onFinish: () => reset("password", "password_confirmation"),
        });
    };

    return (
        <GuestLayout>
            <Head title="パスワードの再設定" />

            <AuthHeader eyebrow="新しいパスワード" title="新しいパスワードを設定" />

            <form onSubmit={submit}>
                <FormField label="メールアドレス" htmlFor="email" error={errors.email}>
                    <TextInput
                        id="email"
                        type="email"
                        name="email"
                        value={data.email}
                        aria-invalid={!!errors.email}
                        aria-describedby={errors.email ? "email-error" : undefined}
                        className="mt-1 block w-full"
                        autoComplete="username"
                        onChange={(e) => setData("email", e.target.value)}
                    />
                </FormField>

                <FormField label="新しいパスワード" htmlFor="password" error={errors.password} className="mt-4">
                    <TextInput
                        id="password"
                        type="password"
                        name="password"
                        value={data.password}
                        aria-invalid={!!errors.password}
                        aria-describedby={errors.password ? "password-error" : undefined}
                        className="mt-1 block w-full"
                        autoComplete="new-password"
                        isFocused={true}
                        onChange={(e) => setData("password", e.target.value)}
                    />
                </FormField>

                <FormField
                    label="パスワード（確認）"
                    htmlFor="password_confirmation"
                    error={errors.password_confirmation}
                    className="mt-4"
                >
                    <TextInput
                        id="password_confirmation"
                        type="password"
                        name="password_confirmation"
                        value={data.password_confirmation}
                        aria-invalid={!!errors.password_confirmation}
                        aria-describedby={errors.password_confirmation ? "password_confirmation-error" : undefined}
                        className="mt-1 block w-full"
                        autoComplete="new-password"
                        onChange={(e) => setData("password_confirmation", e.target.value)}
                    />
                </FormField>

                <div className="mt-4 flex items-center justify-end">
                    <Button
                        variant="primary"
                        className="ms-4"
                        disabled={processing}
                        isBusy={processing}
                    >
                        パスワードを再設定
                    </Button>
                </div>
            </form>
        </GuestLayout>
    );
}
