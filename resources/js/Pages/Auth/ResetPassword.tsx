import InputError from "@/Components/InputError";
import InputLabel from "@/Components/InputLabel";
import Button from "@/Components/UI/Button";
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

            <div className="mb-6">
                <div className="flex items-center gap-2">
                    <div className="h-px w-8 bg-sky-400" />
                    <p className="text-xs font-medium uppercase tracking-widest text-sky-600">新しいパスワード</p>
                </div>
                <h2 className="mt-2 text-2xl font-bold text-ink">新しいパスワードを設定</h2>
            </div>

            <form onSubmit={submit}>
                <div>
                    <InputLabel htmlFor="email" value="メールアドレス" />

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

                    <InputError id="email-error" message={errors.email} className="mt-2" />
                </div>

                <div className="mt-4">
                    <InputLabel htmlFor="password" value="新しいパスワード" />

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

                    <InputError id="password-error" message={errors.password} className="mt-2" />
                </div>

                <div className="mt-4">
                    <InputLabel htmlFor="password_confirmation" value="パスワード（確認）" />

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

                    <InputError
                        id="password_confirmation-error"
                        message={errors.password_confirmation}
                        className="mt-2"
                    />
                </div>

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
