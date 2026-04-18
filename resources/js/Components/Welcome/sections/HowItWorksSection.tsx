import { howItWorksSteps } from "@/Components/Welcome/data";

const processHighlights = ["写真つきメニュー", "PayPay / カード対応"] as const;

function HowItWorksSection() {
    return (
        <section className="relative mt-32">
            <div className="geo-public-shell-soft -mx-2 px-4 py-20 sm:-mx-0 sm:px-8">
                <div className="absolute inset-0 bg-grid-pattern opacity-[0.03]" />
                <div className="geo-public-orb-cyan absolute -right-16 top-0 h-64 w-64 blur-3xl" />
                <div className="text-center">
                    <div className="mx-auto flex items-center justify-center gap-3">
                        <div className="h-px w-8 bg-sky-400" />
                        <p className="text-sm font-semibold uppercase tracking-[0.28em] text-sky-600">
                            How it works
                        </p>
                    </div>
                    <h2 className="mt-4 text-3xl font-bold text-slate-900 sm:text-4xl">
                        <span className="text-sky-500">3ステップ</span>
                        で注文完了
                    </h2>
                    <p className="mx-auto mt-4 max-w-2xl text-sm leading-7 text-slate-600 sm:text-base">
                        使い始めるまでの手順は短く、迷いやすいポイントは画面側で整理。
                        ピーク時のテンポを崩さないように設計しています。
                    </p>
                    <div className="mt-6 flex flex-wrap justify-center gap-3">
                        {processHighlights.map((highlight) => (
                            <span
                                key={highlight}
                                className="rounded-full border border-sky-200 bg-white/80 px-4 py-2 text-sm font-medium text-sky-700 shadow-sm"
                            >
                                {highlight}
                            </span>
                        ))}
                    </div>
                </div>

                <div className="relative mt-12">
                    <div className="geo-public-divider absolute left-[16%] right-[16%] top-14 hidden h-px lg:block" />
                    <div className="grid gap-8 lg:grid-cols-3">
                        {howItWorksSteps.map((item, index) => (
                            <div key={item.step} className={index === 1 ? "lg:pt-8" : ""}>
                                <div
                                    className={`group relative h-full px-7 py-8 ${
                                        index === 1 ? "geo-public-panel-accent" : "geo-public-panel"
                                    }`}
                                >
                                    <div className="absolute -right-4 top-3 text-7xl font-bold leading-none text-sky-100">
                                        {item.step}
                                    </div>
                                    <div className="relative flex items-center gap-4">
                                        <div className="flex h-16 w-16 items-center justify-center rounded-full bg-sky-500 text-xl font-bold text-white shadow-geo-cyan">
                                            {item.step}
                                        </div>
                                        <div className="flex flex-col">
                                            <span className="text-xs font-semibold uppercase tracking-[0.28em] text-sky-600">
                                                Step {item.step}
                                            </span>
                                            <span className="mt-2 h-px w-12 bg-sky-200" />
                                        </div>
                                    </div>
                                    <h3 className="relative mt-6 text-xl font-bold text-slate-900">
                                        {item.title}
                                    </h3>
                                    <p className="relative mt-3 text-sm leading-7 text-slate-600">
                                        {item.body}
                                    </p>
                                    <div className="relative mt-6 h-px w-full bg-gradient-to-r from-sky-200 via-cyan-200 to-transparent" />
                                </div>
                            </div>
                        ))}
                    </div>
                </div>
            </div>
        </section>
    );
}

export default HowItWorksSection;
