import InputError from "@/Components/InputError";
import InputLabel from "@/Components/InputLabel";
import PrimaryButton from "@/Components/PrimaryButton";
import TextInput from "@/Components/TextInput";
import { Transition } from "@headlessui/react";
import { PageProps } from "@/types";
import { Link, useForm, usePage } from "@inertiajs/react";
import { FormEventHandler } from "react";

export default function UpdateProfileInformation({
    mustVerifyEmail,
    status,
    className = "",
}: {
    mustVerifyEmail: boolean;
    status?: string;
    className?: string;
}) {
    const user = usePage<PageProps>().props.auth.user!;

    const { data, setData, patch, errors, processing, recentlySuccessful } = useForm({
        name: user.name,
        email: user.email,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        patch(route("profile.update"));
    };

    return (
        <section className={className}>
            <header>
                <div className="h-px w-8 bg-sky-400 mb-3"></div>
                <h2 className="text-lg font-medium text-slate-900">プロフィール情報</h2>

                <p className="mt-1 text-sm text-slate-600">
                    アカウントのプロフィール情報とメールアドレスを更新します。
                </p>
            </header>

            <form onSubmit={submit} className="mt-6 space-y-6">
                <div>
                    <InputLabel htmlFor="name" value="お名前" />

                    <TextInput
                        id="name"
                        className="mt-1 block w-full"
                        value={data.name}
                        aria-invalid={!!errors.name}
                        aria-describedby={errors.name ? "name-error" : undefined}
                        onChange={(e) => setData("name", e.target.value)}
                        required
                        isFocused
                        autoComplete="name"
                    />

                    <InputError id="name-error" className="mt-2" message={errors.name} />
                </div>

                <div>
                    <InputLabel htmlFor="email" value="メールアドレス" />

                    <TextInput
                        id="email"
                        type="email"
                        className="mt-1 block w-full"
                        value={data.email}
                        aria-invalid={!!errors.email}
                        aria-describedby={errors.email ? "email-error" : undefined}
                        onChange={(e) => setData("email", e.target.value)}
                        required
                        autoComplete="username"
                    />

                    <InputError id="email-error" className="mt-2" message={errors.email} />
                </div>

                {mustVerifyEmail && user.email_verified_at === null && (
                    <div>
                        <p className="mt-2 text-sm text-slate-800">
                            メールアドレスが未確認です。
                            <Link
                                href={route("verification.send")}
                                method="post"
                                as="button"
                                className="text-sm text-slate-600 underline hover:text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2"
                            >
                                認証メールを再送するにはこちらをクリックしてください。
                            </Link>
                        </p>

                        {status === "verification-link-sent" && (
                            <div className="mt-2 text-sm font-medium text-green-600">
                                認証リンクをメールアドレスに送信しました。
                            </div>
                        )}
                    </div>
                )}

                <div className="flex items-center gap-4">
                    <PrimaryButton disabled={processing} isBusy={processing}>
                        保存
                    </PrimaryButton>

                    <Transition
                        show={recentlySuccessful}
                        enter="transition ease-in-out"
                        enterFrom="opacity-0"
                        leave="transition ease-in-out"
                        leaveTo="opacity-0"
                    >
                        <p className="text-sm text-slate-600">保存しました。</p>
                    </Transition>
                </div>
            </form>
        </section>
    );
}
