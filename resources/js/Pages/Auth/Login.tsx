import Checkbox from "@/Components/Checkbox";
import InputError from "@/Components/InputError";
import InputLabel from "@/Components/InputLabel";
import PrimaryButton from "@/Components/PrimaryButton";
import TextInput from "@/Components/TextInput";
import GuestLayout from "@/Layouts/GuestLayout";
import { PageProps } from "@/types";
import { Head, Link, useForm, usePage } from "@inertiajs/react";
import { FormEventHandler } from "react";

export default function Login({ status, canResetPassword }: { status?: string; canResetPassword: boolean }) {
    const { businessLoginUrl } = usePage<
        PageProps & {
            businessLoginUrl?: string;
        }
    >().props;
    const { data, setData, post, processing, errors, reset } = useForm({
        email: "",
        password: "",
        remember: false as boolean,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        post(route("login"), {
            onFinish: () => reset("password"),
        });
    };

    return (
        <GuestLayout>
            <Head title="ログイン" />

            <div className="mb-6">
                <Link
                    href={route("home")}
                    className="mb-3 inline-flex rounded-md text-sm text-ink-light underline hover:text-ink focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2"
                >
                    認証ページトップに戻る
                </Link>
                <div className="flex items-center gap-2">
                    <div className="h-px w-8 bg-sky-400" />
                    <p className="text-xs font-medium uppercase tracking-widest text-sky-600">ログイン</p>
                </div>
                <h2 className="mt-2 text-2xl font-bold text-ink">ログイン</h2>
            </div>

            {status && (
                <div className="mb-4 border border-green-200 bg-green-50 p-3 text-sm font-medium text-green-700">
                    {status}
                </div>
            )}

            {businessLoginUrl && (
                <div className="mb-4 border border-sky-200 bg-sky-50 p-3">
                    <p className="text-sm text-sky-700">
                        事業者の方は
                        <Link href={businessLoginUrl} className="font-medium underline hover:text-sky-900">
                            事業者向けログインページ
                        </Link>
                        をご利用ください。
                    </p>
                </div>
            )}

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
                        isFocused={true}
                        onChange={(e) => setData("email", e.target.value)}
                    />

                    <InputError id="email-error" message={errors.email} className="mt-2" />
                </div>

                <div className="mt-4">
                    <InputLabel htmlFor="password" value="パスワード" />

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

                    <InputError id="password-error" message={errors.password} className="mt-2" />
                </div>

                <div className="mt-4 block">
                    <label className="flex items-center">
                        <Checkbox
                            name="remember"
                            checked={data.remember}
                            onChange={(e) => setData("remember", (e.target.checked || false) as false)}
                        />
                        <span className="ms-2 text-sm text-ink-light">ログイン状態を保持する</span>
                    </label>
                </div>

                <div className="mt-4 flex items-center justify-end">
                    {canResetPassword && (
                        <Link
                            href={route("password.request")}
                            className="rounded-md text-sm text-ink-light underline hover:text-ink focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2"
                        >
                            パスワードをお忘れですか？
                        </Link>
                    )}

                    <PrimaryButton className="ms-4" disabled={processing} isBusy={processing}>
                        ログイン
                    </PrimaryButton>
                </div>

                <div className="mt-6 text-center">
                    <span className="text-sm text-ink-light">アカウントをお持ちでないですか？ </span>
                    <Link
                        href={route("register")}
                        className="rounded-md text-sm text-sky-600 underline hover:text-sky-800 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2"
                    >
                        新規登録
                    </Link>
                </div>
            </form>
        </GuestLayout>
    );
}
