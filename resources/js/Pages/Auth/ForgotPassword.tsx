import InputError from "@/Components/InputError";
import Button from "@/Components/UI/Button";
import TextInput from "@/Components/TextInput";
import GuestLayout from "@/Layouts/GuestLayout";
import { PageProps } from "@/types";
import { Head, Link, useForm } from "@inertiajs/react";
import { FormEventHandler } from "react";

interface ForgotPasswordProps extends PageProps {
    status?: string;
}

export default function ForgotPassword({ status }: ForgotPasswordProps) {
    const { data, setData, post, processing, errors } = useForm({
        email: "",
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        post(route("password.email"));
    };

    return (
        <GuestLayout>
            <Head title="パスワード再設定" />

            <div className="mb-6">
                <div className="flex items-center gap-2">
                    <div className="h-px w-8 bg-sky-400" />
                    <p className="text-xs font-medium uppercase tracking-widest text-sky-600">パスワード再設定</p>
                </div>
                <h2 className="mt-2 text-2xl font-bold text-ink">パスワード再設定</h2>
            </div>

            <div className="mb-4 text-sm text-ink-light">
                パスワードを忘れてしまいましたか？問題ありません。メールアドレスを入力すると、パスワード再設定用のリンクをお送りします。
            </div>

            {status && (
                <div className="mb-4 border border-green-200 bg-green-50 p-3 text-sm font-medium text-green-700">
                    {status}
                </div>
            )}

            <form onSubmit={submit}>
                <TextInput
                    id="email"
                    type="email"
                    name="email"
                    value={data.email}
                    aria-invalid={!!errors.email}
                    aria-describedby={errors.email ? "email-error" : undefined}
                    className="mt-1 block w-full"
                    isFocused={true}
                    onChange={(e) => setData("email", e.target.value)}
                />

                <InputError id="email-error" message={errors.email} className="mt-2" />

                <div className="mt-4 flex items-center justify-between">
                    <Link
                        href={route("login")}
                        className="rounded-md text-sm text-ink-light underline hover:text-ink focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2"
                    >
                        ログインに戻る
                    </Link>
                    <Button variant="primary" disabled={processing} isBusy={processing}>
                        パスワード再設定リンクを送信
                    </Button>
                </div>
            </form>
        </GuestLayout>
    );
}
