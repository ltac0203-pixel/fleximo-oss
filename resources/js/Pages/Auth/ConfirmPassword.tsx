import InputError from "@/Components/InputError";
import InputLabel from "@/Components/InputLabel";
import Button from "@/Components/UI/Button";
import TextInput from "@/Components/TextInput";
import GuestLayout from "@/Layouts/GuestLayout";
import { Head, useForm } from "@inertiajs/react";
import { FormEventHandler } from "react";

export default function ConfirmPassword() {
    const { data, setData, post, processing, errors, reset } = useForm({
        password: "",
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        post(route("password.confirm"), {
            onFinish: () => reset("password"),
        });
    };

    return (
        <GuestLayout>
            <Head title="パスワード確認" />

            <div className="mb-6">
                <div className="flex items-center gap-2">
                    <div className="h-px w-8 bg-sky-400" />
                    <p className="text-xs font-medium uppercase tracking-widest text-sky-600">パスワード確認</p>
                </div>
                <h2 className="mt-2 text-2xl font-bold text-ink">パスワード確認</h2>
            </div>

            <div className="mb-4 text-sm text-ink-light">
                このページはセキュアなエリアです。続行するにはパスワードを確認してください。
            </div>

            <form onSubmit={submit}>
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
                        isFocused={true}
                        onChange={(e) => setData("password", e.target.value)}
                    />

                    <InputError id="password-error" message={errors.password} className="mt-2" />
                </div>

                <div className="mt-4 flex items-center justify-end">
                    <Button
                        variant="primary"
                        className="ms-4"
                        disabled={processing}
                        isBusy={processing}
                    >
                        確認する
                    </Button>
                </div>
            </form>
        </GuestLayout>
    );
}
