import AuthHeader from "@/Components/UI/AuthHeader";
import Button from "@/Components/UI/Button";
import FormField from "@/Components/UI/FormField";
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

            <AuthHeader eyebrow="パスワード確認" title="パスワード確認" />

            <div className="mb-4 text-sm text-ink-light">
                このページはセキュアなエリアです。続行するにはパスワードを確認してください。
            </div>

            <form onSubmit={submit}>
                <FormField label="パスワード" htmlFor="password" error={errors.password} className="mt-4">
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
                </FormField>

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
