import { conventionalPricingItems, fleximoPricingItems, trustedBrands } from "@/Components/ForBusiness/data";
import { PRIMARY_BUTTON_LARGE } from "@/constants/buttonStyles";
import { Link } from "@inertiajs/react";

export function TrustedBrandsSection() {
    return (
        <section id="proof" className="mt-24 scroll-mt-24">
            <div className="geo-public-shell-soft p-8 text-center md:p-12">
                <p className="text-sm font-semibold uppercase tracking-widest text-sky-600">Proof</p>
                <h3 className="mt-2 text-2xl font-bold text-slate-900">導入を支える安心の決済実績</h3>
                <p className="mt-3 text-sm leading-relaxed text-slate-600">
                    現場で求められる主要なキャッシュレスブランド（PayPay・各種クレジットカード）に対応し、導入のハードルを下げます。
                </p>
                <div className="mt-8 flex flex-wrap items-center justify-center gap-8 text-slate-400">
                    {trustedBrands.map((brand) => (
                        <div key={brand} className="geo-public-panel px-6 py-3">
                            <span className="text-sm font-semibold text-slate-600">{brand}</span>
                        </div>
                    ))}
                </div>
            </div>
        </section>
    );
}

function PricingSection() {
    return (
        <section id="pricing" className="mt-24 scroll-mt-24">
            <div className="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
                <div>
                    <div className="flex items-center gap-2">
                        <div className="h-px w-8 bg-sky-400" />
                        <p className="text-xs font-medium uppercase tracking-widest text-sky-600">Pricing</p>
                    </div>
                    <h2 className="mt-4 text-3xl font-bold text-slate-900">料金プラン</h2>
                </div>
                <p className="max-w-md text-sm leading-7 text-slate-600">
                    固定費や専用端末の負担を抑えながら、まずは運用に乗るかを試しやすい料金設計です。
                </p>
            </div>

            <div className="mt-10 grid gap-6 lg:grid-cols-2">
                <div className="geo-public-panel-soft p-8">
                    <p className="text-sm font-semibold text-slate-500">一般的なモバイルオーダー</p>
                    <div className="mt-4 space-y-3">
                        {conventionalPricingItems.map((item) => (
                            <div key={item.label} className="flex items-center gap-3">
                                <span className="w-5 text-center text-sm font-bold text-slate-400">X</span>
                                <span className="text-slate-600">
                                    {item.label} <strong className="text-slate-900">{item.value}</strong>
                                </span>
                            </div>
                        ))}
                    </div>
                </div>

                <div className="geo-public-panel-accent p-8">
                    <div className="flex items-center gap-2">
                        <p className="text-sm font-semibold text-sky-600">Fleximo</p>
                        <span className="rounded-full bg-sky-100 px-2 py-0.5 text-xs font-semibold text-sky-700">
                            おすすめ
                        </span>
                    </div>
                    <div className="mt-4 space-y-3">
                        {fleximoPricingItems.map((item) => (
                            <div key={item.label} className="flex items-center gap-3">
                                <span className="w-5 text-center text-sm font-bold text-sky-500">OK</span>
                                <span className="text-slate-700">
                                    {item.label} <strong className="text-sky-600">{item.value}</strong>
                                </span>
                            </div>
                        ))}
                    </div>
                    <p className="mt-6 text-xs text-slate-500">
                        ※ 決済手数料は売上に応じて発生します。詳細はお問い合わせください。
                    </p>
                    <Link href={route("tenant-application.create")} className={`mt-6 w-full ${PRIMARY_BUTTON_LARGE}`}>
                        無料でテナント申請
                    </Link>
                </div>
            </div>
        </section>
    );
}

export default PricingSection;
