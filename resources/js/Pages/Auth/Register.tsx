import InputError from "@/Components/InputError";
import InputLabel from "@/Components/InputLabel";
import PrimaryButton from "@/Components/PrimaryButton";
import TextInput from "@/Components/TextInput";
import GuestLayout from "@/Layouts/GuestLayout";
import { Head, Link, useForm } from "@inertiajs/react";
import { FormEventHandler } from "react";

export default function Register() {
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
            <Head title="新規登録" />

            <div className="mb-6">
                <div className="flex items-center gap-2">
                    <div className="h-px w-8 bg-sky-400" />
                    <p className="text-xs font-medium uppercase tracking-widest text-sky-600">新規登録</p>
                </div>
                <h2 className="mt-2 text-2xl font-bold text-ink">新規登録</h2>
            </div>

            <form onSubmit={submit}>
                <div>
                    <InputLabel htmlFor="name" value="お名前" />

                    <TextInput
                        id="name"
                        name="name"
                        value={data.name}
                        placeholder="例：山田 太郎"
                        aria-invalid={!!errors.name}
                        aria-describedby={errors.name ? "name-error" : undefined}
                        className="mt-1 block w-full"
                        autoComplete="name"
                        isFocused={true}
                        onChange={(e) => setData("name", e.target.value)}
                        required
                    />

                    <InputError id="name-error" message={errors.name} className="mt-2" />
                </div>

                <div className="mt-4">
                    <InputLabel htmlFor="email" value="メールアドレス" />

                    <TextInput
                        id="email"
                        type="email"
                        name="email"
                        value={data.email}
                        placeholder="例：taro@example.com"
                        aria-invalid={!!errors.email}
                        aria-describedby={errors.email ? "email-error" : undefined}
                        className="mt-1 block w-full"
                        autoComplete="username"
                        onChange={(e) => setData("email", e.target.value)}
                        required
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
                        placeholder="8文字以上で入力"
                        aria-invalid={!!errors.password}
                        aria-describedby={errors.password ? "password-error" : undefined}
                        className="mt-1 block w-full"
                        autoComplete="new-password"
                        onChange={(e) => setData("password", e.target.value)}
                        required
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
                        placeholder="確認のため再入力"
                        aria-invalid={!!errors.password_confirmation}
                        aria-describedby={errors.password_confirmation ? "password_confirmation-error" : undefined}
                        className="mt-1 block w-full"
                        autoComplete="new-password"
                        onChange={(e) => setData("password_confirmation", e.target.value)}
                        required
                    />

                    <InputError
                        id="password_confirmation-error"
                        message={errors.password_confirmation}
                        className="mt-2"
                    />
                </div>

                <div className="mt-4 flex items-center justify-end">
                    <Link
                        href={route("login")}
                        className="rounded-md text-sm text-ink-light underline hover:text-ink focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2"
                    >
                        すでに登録済みですか？
                    </Link>

                    <PrimaryButton className="ms-4" disabled={processing} isBusy={processing}>
                        登録する
                    </PrimaryButton>
                </div>
            </form>
        </GuestLayout>
    );
}
