import FormActions from "@/Components/FormActions";
import InputError from "@/Components/InputError";
import InputLabel from "@/Components/InputLabel";
import PrimaryButton from "@/Components/PrimaryButton";
import TextInput from "@/Components/TextInput";
import { Transition } from "@headlessui/react";
import { useForm } from "@inertiajs/react";
import { FormEventHandler, useRef } from "react";

export default function UpdatePasswordForm({ className = "" }: { className?: string }) {
    const passwordInput = useRef<HTMLInputElement>(null);
    const currentPasswordInput = useRef<HTMLInputElement>(null);

    const { data, setData, errors, put, reset, processing, recentlySuccessful } = useForm({
        current_password: "",
        password: "",
        password_confirmation: "",
    });

    const updatePassword: FormEventHandler = (e) => {
        e.preventDefault();

        put(route("password.update"), {
            preserveScroll: true,
            onSuccess: () => reset(),
            onError: (errors) => {
                if (errors.password) {
                    reset("password", "password_confirmation");
                    passwordInput.current?.focus();
                }

                if (errors.current_password) {
                    reset("current_password");
                    currentPasswordInput.current?.focus();
                }
            },
        });
    };

    return (
        <section className={className}>
            <header>
                <div className="h-px w-8 bg-sky-400 mb-3"></div>
                <h2 className="text-lg font-medium text-slate-900">パスワード変更</h2>

                <p className="mt-1 text-sm text-slate-600">
                    安全のため、長くて推測されにくいパスワードを使用してください。
                </p>
            </header>

            <form onSubmit={updatePassword} className="mt-6 space-y-6">
                <div>
                    <InputLabel htmlFor="current_password" value="現在のパスワード" />

                    <TextInput
                        id="current_password"
                        ref={currentPasswordInput}
                        value={data.current_password}
                        aria-invalid={!!errors.current_password}
                        aria-describedby={errors.current_password ? "current_password-error" : undefined}
                        onChange={(e) => setData("current_password", e.target.value)}
                        type="password"
                        className="mt-1 block w-full"
                        autoComplete="current-password"
                    />

                    <InputError id="current_password-error" message={errors.current_password} className="mt-2" />
                </div>

                <div>
                    <InputLabel htmlFor="password" value="新しいパスワード" />

                    <TextInput
                        id="password"
                        ref={passwordInput}
                        value={data.password}
                        aria-invalid={!!errors.password}
                        aria-describedby={errors.password ? "password-error" : undefined}
                        onChange={(e) => setData("password", e.target.value)}
                        type="password"
                        className="mt-1 block w-full"
                        autoComplete="new-password"
                    />

                    <InputError id="password-error" message={errors.password} className="mt-2" />
                </div>

                <div>
                    <InputLabel htmlFor="password_confirmation" value="パスワード（確認）" />

                    <TextInput
                        id="password_confirmation"
                        value={data.password_confirmation}
                        aria-invalid={!!errors.password_confirmation}
                        aria-describedby={errors.password_confirmation ? "password_confirmation-error" : undefined}
                        onChange={(e) => setData("password_confirmation", e.target.value)}
                        type="password"
                        className="mt-1 block w-full"
                        autoComplete="new-password"
                    />

                    <InputError
                        id="password_confirmation-error"
                        message={errors.password_confirmation}
                        className="mt-2"
                    />
                </div>

                <FormActions
                    leftSlot={
                        <Transition
                            show={recentlySuccessful}
                            enter="transition ease-in-out"
                            enterFrom="opacity-0"
                            leave="transition ease-in-out"
                            leaveTo="opacity-0"
                        >
                            <p className="text-sm text-slate-600">更新しました。</p>
                        </Transition>
                    }
                >
                    <PrimaryButton disabled={processing} isBusy={processing}>
                        更新
                    </PrimaryButton>
                </FormActions>
            </form>
        </section>
    );
}
