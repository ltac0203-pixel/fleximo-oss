import { ctaHighlights } from "@/Components/ForBusiness/data";
import { INVERTED_BUTTON_PRIMARY, INVERTED_BUTTON_SECONDARY } from "@/constants/buttonStyles";
import { Link } from "@inertiajs/react";

function CtaSection() {
    return (
        <div className="mt-24">
            <div className="geo-public-band p-8 md:p-12 lg:p-14">
                <div className="absolute inset-0 bg-grid-pattern opacity-10" />
                <div className="geo-public-orb-ice absolute -left-8 top-8 h-48 w-48 blur-3xl" />
                <div className="geo-public-orb-cyan absolute right-[-5rem] top-0 h-72 w-72 blur-3xl" />

                <div className="relative grid gap-10 lg:grid-cols-[minmax(0,1fr)_minmax(0,0.92fr)] lg:items-center">
                    <div>
                        <h2 className="text-3xl font-bold text-white md:text-4xl">まずは無料で試してみませんか？</h2>
                        <p className="mt-4 max-w-xl text-base text-white/80">
                            最短数日で導入完了。解約はいつでも可能です。
                            リスクゼロで、あなたのお店にモバイルオーダーを。
                        </p>
                        <div className="mt-6 flex flex-wrap gap-3 text-sm text-white/80">
                            {ctaHighlights.map((highlight) => (
                                <span
                                    key={highlight}
                                    className="rounded-full border border-white/25 bg-white/10 px-4 py-2 font-medium"
                                >
                                    {highlight}
                                </span>
                            ))}
                        </div>
                    </div>

                    <div className="border border-white/15 bg-white/10 p-6 backdrop-blur-sm sm:p-8">
                        <p className="text-xs font-semibold uppercase tracking-[0.28em] text-white/70">
                            Start with a light setup
                        </p>
                        <div className="mt-8 flex flex-wrap gap-4">
                            <Link href={route("tenant-application.create")} className={INVERTED_BUTTON_PRIMARY}>
                                無料でテナント申請
                            </Link>
                            <Link href={route("contact.index")} className={INVERTED_BUTTON_SECONDARY}>
                                お問い合わせ
                            </Link>
                        </div>
                        <p className="mt-5 text-sm leading-7 text-white/75">
                            店舗運営に合うかどうかを、まずは低いハードルで確かめられます。
                        </p>
                    </div>
                </div>
            </div>
        </div>
    );
}

export default CtaSection;
