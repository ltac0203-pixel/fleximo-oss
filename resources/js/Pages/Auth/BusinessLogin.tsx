import Checkbox from "@/Components/Checkbox";
import InputError from "@/Components/InputError";
import InputLabel from "@/Components/InputLabel";
import PrimaryButton from "@/Components/PrimaryButton";
import TextInput from "@/Components/TextInput";
import GuestLayout from "@/Layouts/GuestLayout";
import { PageProps } from "@/types";
import { Head, Link, useForm, usePage } from "@inertiajs/react";
import { FormEventHandler } from "react";

export default function BusinessLogin({ status, canResetPassword }: { status?: string; canResetPassword: boolean }) {
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
            <Head title="事業者ログイン" />

            <div className="mb-6">
                <Link
                    href={route("home")}
                    className="mb-3 inline-flex rounded-md text-sm text-ink-light underline hover:text-ink focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2"
                >
                    認証ページトップに戻る
                </Link>
                <h2 className="text-2xl font-bold text-ink">事業者ログイン</h2>
                <p className="mt-2 text-sm text-ink-light">
                    テナント管理者・スタッフおよびシステム管理者の方専用のログインページです
                </p>
            </div>

            {status && <div className="mb-4 text-sm font-medium text-green-600">{status}</div>}

            {customerLoginUrl && (
                <div className="mb-4 rounded-md bg-sky-50 p-4">
                    <p className="text-sm text-sky-700">
                        一般のお客様は
                        <Link href={customerLoginUrl} className="font-medium underline hover:text-sky-900">
                            通常のログインページ
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

            </form>
        </GuestLayout>
    );
}
