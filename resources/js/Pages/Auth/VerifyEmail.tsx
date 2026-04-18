import PrimaryButton from "@/Components/PrimaryButton";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { PageProps } from "@/types";
import { Head, Link, useForm } from "@inertiajs/react";
import { FormEventHandler } from "react";

interface VerifyEmailProps extends PageProps {
    status?: string;
}

export default function VerifyEmail({ status }: VerifyEmailProps) {
    const { post, processing } = useForm({});

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        post(route("verification.send"));
    };

    return (
        <AuthenticatedLayout>
            <Head title="メール認証" />

            <div className="mx-auto max-w-2xl px-4 py-6">
                <div className="mb-6">
                    <div className="flex items-center gap-2">
                        <div className="h-px w-8 bg-sky-400" />
                        <p className="text-xs font-medium uppercase tracking-widest text-sky-600">メール認証</p>
                    </div>
                    <h2 className="mt-2 text-2xl font-bold text-ink">メール認証</h2>
                </div>

                <div className="mb-4 text-sm text-ink-light">
                    ご登録ありがとうございます。ご利用を開始する前に、先ほどお送りしたメール内のリンクをクリックしてメールアドレスを確認してください。メールが届いていない場合は再送します。
                </div>

                {status === "verification-link-sent" && (
                    <div className="mb-4 border border-green-200 bg-green-50 p-3 text-sm font-medium text-green-700">
                        登録時のメールアドレス宛に認証リンクを再送しました。
                    </div>
                )}

                {status === "verification-link-failed" && (
                    <div className="mb-4 border border-red-200 bg-red-50 p-3 text-sm font-medium text-red-700">
                        認証メールの送信に失敗しました。しばらくしてから再度お試しください。
                    </div>
                )}

                <form onSubmit={submit}>
                    <div className="mt-4 flex items-center justify-between">
                        <PrimaryButton disabled={processing} isBusy={processing}>
                            認証メールを再送する
                        </PrimaryButton>

                        <Link
                            href={route("logout")}
                            method="post"
                            as="button"
                            className="text-sm text-ink-light underline hover:text-ink focus:outline-none"
                        >
                            ログアウト
                        </Link>
                    </div>
                </form>
            </div>
        </AuthenticatedLayout>
    );
}
