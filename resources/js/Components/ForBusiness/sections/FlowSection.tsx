import { flowSteps } from "@/Components/ForBusiness/data";

function FlowSection() {
    return (
        <div className="mt-24">
            <div className="geo-public-shell-soft px-6 py-8 sm:px-8 sm:py-10">
                <div className="flex flex-col gap-6 md:flex-row md:items-start md:justify-between">
                    <div>
                        <div className="flex items-center gap-2">
                            <div className="h-px w-8 bg-sky-400" />
                            <p className="text-xs font-medium uppercase tracking-widest text-sky-600">How it Works</p>
                        </div>
                        <h2 className="mt-4 text-3xl font-bold text-slate-900">導入の流れ</h2>
                    </div>
                    <p className="max-w-md text-sm text-slate-600">シンプルな4ステップで導入完了。複雑な手続きは不要です。</p>
                </div>
                <div className="relative mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                    <div className="geo-public-divider absolute left-[12%] right-[12%] top-7 hidden h-px lg:block" />
                    {flowSteps.map((step, index) => (
                        <div key={step.number}>
                            <div className={`p-6 ${index === 1 ? "geo-public-panel-accent" : "geo-public-panel"}`}>
                                <div className="flex h-14 w-14 items-center justify-center rounded-full border border-sky-200 bg-sky-50">
                                    <span className="text-lg font-bold text-sky-600">{step.number}</span>
                                </div>
                                <h3 className="mt-4 text-lg font-semibold text-slate-900">{step.title}</h3>
                                <p className="mt-2 text-sm leading-relaxed text-slate-600">{step.body}</p>
                                <p className="mt-3 text-xs font-semibold text-sky-600">{step.time}</p>
                            </div>
                        </div>
                    ))}
                </div>
            </div>
        </div>
    );
}

export default FlowSection;
