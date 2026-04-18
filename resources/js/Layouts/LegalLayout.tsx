import { PropsWithChildren } from "react";
import { Link, usePage } from "@inertiajs/react";
import LegalNav from "@/Components/Legal/LegalNav";
import GradientBackground from "@/Components/GradientBackground";
import SkipToContentLink, { MAIN_CONTENT_ID } from "@/Components/SkipToContentLink";
import type { LegalConfig, SiteConfig } from "@/types/common";

interface LegalLayoutProps extends PropsWithChildren {
    title: string;
    lastUpdated: string;
}

export default function LegalLayout({ children, title, lastUpdated }: LegalLayoutProps) {
    const { legal, siteConfig } = usePage<{ legal: LegalConfig; siteConfig: SiteConfig }>().props;

    return (
        <div className="relative min-h-screen bg-white">
            <SkipToContentLink />
            <GradientBackground />

            {/* ヘッダー を明示し、実装意図の誤読を防ぐ。 */}
            <header className="relative border-b border-slate-200 bg-white">
                <div className="mx-auto max-w-7xl px-4 py-4 sm:px-6 lg:px-8">
                    <div className="flex items-center justify-between">
                        <Link
                            href={route("home")}
                            className="font-orbitron text-2xl font-bold tracking-tight text-slate-900 hover:text-sky-600"
                        >
                            {siteConfig.name}
                        </Link>
                        <Link
                            href={route("contact.index")}
                            className="border border-sky-500 bg-sky-500 px-4 py-2 text-sm font-medium text-white hover:bg-sky-600"
                        >
                            お問い合わせ
                        </Link>
                    </div>
                </div>
            </header>

            {/* メインコンテンツ を明示し、実装意図の誤読を防ぐ。 */}
            <main id={MAIN_CONTENT_ID} tabIndex={-1} className="relative py-12">
                <div className="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                    <div className="border border-slate-200 bg-white p-8 sm:p-12">
                        {/* タイトル を明示し、実装意図の誤読を防ぐ。 */}
                        <div className="mb-8 border-b border-slate-200 pb-6">
                            <h1 className="text-3xl font-bold text-slate-900 sm:text-4xl">{title}</h1>
                            <p className="mt-2 text-sm text-slate-500">最終更新日: {lastUpdated}</p>
                        </div>

                        {/* コンテンツ を明示し、実装意図の誤読を防ぐ。 */}
                        <div className="space-y-8">{children}</div>

                        {/* ページ間ナビゲーション を明示し、実装意図の誤読を防ぐ。 */}
                        <div className="mt-12 border-t border-slate-200 pt-8">
                            <LegalNav />
                        </div>
                    </div>
                </div>
            </main>

            {/* フッター を明示し、実装意図の誤読を防ぐ。 */}
            <footer className="relative border-t border-slate-200 bg-white">
                <div className="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                    <div className="flex flex-col items-center justify-between gap-4 sm:flex-row">
                        <div className="flex flex-col items-center gap-2 sm:items-start">
                            {legal.websiteUrl ? (
                                <a
                                    href={legal.websiteUrl}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="text-sm text-slate-600 hover:text-sky-600"
                                >
                                    会社概要
                                </a>
                            ) : null}
                            <address className="text-xs text-slate-500 not-italic">
                                {legal.companyName}
                                {legal.address ? (
                                    <>
                                        <br />
                                        〒{legal.postalCode} {legal.address}
                                        {legal.addressExtra ? ` ${legal.addressExtra}` : ""}
                                    </>
                                ) : null}
                            </address>
                        </div>
                        <p className="text-sm text-slate-500">
                            &copy; {new Date().getFullYear()} {siteConfig.name}. All Rights Reserved.
                        </p>
                    </div>
                </div>
            </footer>
        </div>
    );
}
