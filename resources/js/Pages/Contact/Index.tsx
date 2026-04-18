import InputError from "@/Components/InputError";
import InputLabel from "@/Components/InputLabel";
import PrimaryButton from "@/Components/PrimaryButton";
import SeoHead from "@/Components/SeoHead";
import TextInput from "@/Components/TextInput";
import { useSeo } from "@/Hooks/useSeo";
import GuestLayout from "@/Layouts/GuestLayout";
import { PageProps } from "@/types";
import type { SeoMetadata, StructuredData } from "@/types/seo";
import { useForm, usePage } from "@inertiajs/react";
import { FormEventHandler } from "react";

interface ContactPageProps extends PageProps {
    seo?: Partial<SeoMetadata>;
    structuredData?: StructuredData | StructuredData[];
}

export default function Index({ seo, structuredData }: ContactPageProps) {
    const { generateMetadata } = useSeo();
    const { flash } = usePage<PageProps>().props;
    const { data, setData, post, processing, errors, reset } = useForm({
        name: "",
        email: "",
        subject: "",
        message: "",
        website: "", // ハニーポット
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        post(route("contact.store"), {
            onSuccess: () => reset(),
        });
    };

    const metadata = generateMetadata(
        seo ?? {
            title: "お問い合わせ",
            description: "Fleximoへの導入相談、サポート依頼、お問い合わせを受け付けています。",
        },
    );

    return (
        <>
            <SeoHead metadata={metadata} structuredData={structuredData} />

            <GuestLayout>
                <h1 className="mb-6 text-xl font-bold text-ink">お問い合わせ</h1>

                {flash?.success && (
                    <div className="mb-4 rounded-md border border-green-200 bg-green-50 p-4 text-sm text-green-700">
                        {flash.success}
                    </div>
                )}

                <form onSubmit={submit}>
                    {/* ハニーポット（CSSで非表示） を明示し、実装意図の誤読を防ぐ。 */}
                    <div className="absolute left-[-9999px]" aria-hidden="true">
                        <label htmlFor="website">ウェブサイト</label>
                        <input
                            type="text"
                            id="website"
                            name="website"
                            value={data.website}
                            onChange={(e) => setData("website", e.target.value)}
                            tabIndex={-1}
                            autoComplete="off"
                        />
                    </div>

                    <div>
                        <InputLabel htmlFor="name" value="お名前" />
                        <TextInput
                            id="name"
                            type="text"
                            name="name"
                            value={data.name}
                            aria-invalid={!!errors.name}
                            aria-describedby={errors.name ? "name-error" : undefined}
                            className="mt-1 block w-full"
                            autoComplete="name"
                            isFocused={true}
                            onChange={(e) => setData("name", e.target.value)}
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
                            aria-invalid={!!errors.email}
                            aria-describedby={errors.email ? "email-error" : undefined}
                            className="mt-1 block w-full"
                            autoComplete="email"
                            onChange={(e) => setData("email", e.target.value)}
                        />
                        <InputError id="email-error" message={errors.email} className="mt-2" />
                    </div>

                    <div className="mt-4">
                        <InputLabel htmlFor="subject" value="件名" />
                        <TextInput
                            id="subject"
                            type="text"
                            name="subject"
                            value={data.subject}
                            aria-invalid={!!errors.subject}
                            aria-describedby={errors.subject ? "subject-error" : undefined}
                            className="mt-1 block w-full"
                            onChange={(e) => setData("subject", e.target.value)}
                        />
                        <InputError id="subject-error" message={errors.subject} className="mt-2" />
                    </div>

                    <div className="mt-4">
                        <InputLabel htmlFor="message" value="お問い合わせ内容" />
                        <textarea
                            id="message"
                            name="message"
                            value={data.message}
                            aria-invalid={!!errors.message}
                            aria-describedby={errors.message ? "message-error" : undefined}
                            className="mt-1 block w-full rounded-md border border-edge-strong px-3 py-2 focus:border-primary focus:outline-none"
                            rows={6}
                            onChange={(e) => setData("message", e.target.value)}
                        />
                        <InputError id="message-error" message={errors.message} className="mt-2" />
                    </div>

                    <div className="mt-6">
                        <PrimaryButton className="w-full justify-center" disabled={processing} isBusy={processing}>
                            送信する
                        </PrimaryButton>
                    </div>
                </form>
            </GuestLayout>
        </>
    );
}
