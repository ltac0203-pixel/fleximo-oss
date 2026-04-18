import SeoHead from "@/Components/SeoHead";
import { useSeo } from "@/Hooks/useSeo";
import GuestLayout from "@/Layouts/GuestLayout";
import { PageProps } from "@/types";
import type { SeoMetadata, StructuredData } from "@/types/seo";
import { Link } from "@inertiajs/react";

interface CompletePageProps extends PageProps {
    seo?: Partial<SeoMetadata>;
    structuredData?: StructuredData | StructuredData[];
}

export default function Complete({ seo, structuredData }: CompletePageProps) {
    const { generateMetadata } = useSeo();
    const metadata = generateMetadata(
        seo ?? {
            title: "加盟店申し込み完了",
            description: "Fleximoの加盟店申し込み完了ページです。",
            noindex: true,
        },
    );

    return (
        <>
            <SeoHead metadata={metadata} structuredData={structuredData} />

            <GuestLayout>
                <div className="text-center">
                    <div className="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-green-100">
                        <svg
                            className="h-8 w-8 text-green-600"
                            fill="none"
                            viewBox="0 0 24 24"
                            strokeWidth="1.5"
                            stroke="currentColor"
                        >
                            <path strokeLinecap="round" strokeLinejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                        </svg>
                    </div>

                    <h1 className="mt-4 text-xl font-bold text-ink">お申し込みありがとうございます</h1>

                    <p className="mt-4 text-ink-light">
                        テナント申し込みを受け付けました。
                        <br />
                        ご入力いただいたメールアドレス宛に確認メールをお送りしました。
                    </p>

                    <div className="mt-8 bg-surface p-6 text-left">
                        <h2 className="text-sm font-semibold text-ink">今後の流れ</h2>
                        <ol className="mt-3 space-y-3 text-sm text-ink-light">
                            <li className="flex gap-3">
                                <span className="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-cyan-100 text-xs font-medium text-cyan-700">
                                    1
                                </span>
                                <span>当社にて申し込み内容を審査いたします</span>
                            </li>
                            <li className="flex gap-3">
                                <span className="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-cyan-100 text-xs font-medium text-cyan-700">
                                    2
                                </span>
                                <span>審査完了後、結果をメールにてお知らせします</span>
                            </li>
                            <li className="flex gap-3">
                                <span className="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-cyan-100 text-xs font-medium text-cyan-700">
                                    3
                                </span>
                                <span>承認された場合は、ログイン情報をお送りします</span>
                            </li>
                        </ol>
                        <p className="mt-4 text-sm text-muted">審査には通常1〜3営業日程度お時間をいただきます。</p>
                    </div>

                    <div className="mt-8">
                        <Link href="/" className="text-sm font-medium text-primary-dark hover:text-primary-dark">
                            トップページに戻る
                        </Link>
                    </div>
                </div>
            </GuestLayout>
        </>
    );
}
