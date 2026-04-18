import { primaryFeatures, secondaryFeatures } from "@/Components/ForBusiness/data";

const featureCaptions = ["注文導線", "調理導線", "決済導線"] as const;

function FeaturesSection() {
    return (
        <section id="features" className="mt-24 scroll-mt-24">
            <div>
                <div className="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
                    <div>
                        <div className="flex items-center gap-2">
                            <div className="h-px w-8 bg-sky-400" />
                            <p className="text-xs font-medium uppercase tracking-widest text-sky-600">Features</p>
                        </div>
                        <h2 className="mt-4 text-3xl font-bold text-slate-900">Fleximoの主要機能</h2>
                    </div>
                    <p className="max-w-md text-sm text-slate-600">
                        注文・調理・決済・分析まで一気通貫。店舗運営に必要な機能がすべて揃っています。
                    </p>
                </div>
            </div>

            <div className="mt-10 grid gap-6 md:grid-cols-3">
                {primaryFeatures.map((feature, index) => (
                    <div key={feature.title}>
                        <div
                            className={`group h-full p-8 ${
                                index === 0 ? "geo-public-panel-accent" : "geo-public-panel"
                            }`}
                        >
                            <div className="flex items-center justify-end gap-4">
                                <span className="text-sm font-semibold text-slate-300">0{index + 1}</span>
                            </div>
                            <h3 className="mt-6 text-xl font-bold text-slate-900">{feature.title}</h3>
                            <p className="mt-3 text-sm leading-relaxed text-slate-600">{feature.body}</p>
                            <div className="mt-6 h-px w-full bg-gradient-to-r from-sky-200 via-cyan-200 to-transparent" />
                            <p className="mt-6 text-xs font-semibold uppercase tracking-[0.28em] text-sky-600">
                                {featureCaptions[index]}
                            </p>
                        </div>
                    </div>
                ))}
            </div>

            <div className="mt-6 grid gap-6 sm:grid-cols-3">
                {secondaryFeatures.map((feature) => (
                    <div key={feature.title}>
                        <div className="geo-public-panel-soft group h-full p-6">
                            <h3 className="text-base font-semibold text-slate-900">{feature.title}</h3>
                            <p className="mt-2 text-sm leading-relaxed text-slate-600">{feature.body}</p>
                        </div>
                    </div>
                ))}
            </div>
        </section>
    );
}

export default FeaturesSection;
