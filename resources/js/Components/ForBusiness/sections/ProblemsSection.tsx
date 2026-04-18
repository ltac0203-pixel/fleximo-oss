import { problemItems } from "@/Components/ForBusiness/data";

function ProblemsSection() {
    return (
        <div className="mt-24">
            <div className="geo-public-shell-soft p-8 md:p-12">
                <div className="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
                    <div>
                        <div className="flex items-center gap-2">
                            <div className="h-px w-8 bg-sky-400" />
                            <p className="text-xs font-medium uppercase tracking-widest text-sky-600">Challenges</p>
                        </div>
                        <h2 className="mt-4 text-2xl font-bold text-slate-900 sm:text-3xl">こんな課題、ありませんか？</h2>
                    </div>
                    <p className="max-w-md text-sm leading-7 text-slate-600">
                        ピーク時の混雑、注文ミス、現金管理。現場で起きやすい負荷を、注文導線から整えていきます。
                    </p>
                </div>
                <div className="mt-10 grid gap-6 md:grid-cols-3">
                    {problemItems.map((item, index) => (
                        <div
                            key={item.question}
                            className={`geo-public-panel p-6 ${index === 1 ? "md:-translate-y-4" : ""}`}
                        >
                            <div className="flex items-center justify-between gap-4">
                                <span className="text-xs font-semibold uppercase tracking-[0.28em] text-sky-600">
                                    0{index + 1}
                                </span>
                                <span className="rounded-full border border-sky-100 bg-sky-50 px-3 py-1 text-xs font-semibold text-sky-700">
                                    現場課題
                                </span>
                            </div>
                            <p className="mt-4 text-base font-semibold text-slate-900">{item.question}</p>
                            <div className="mt-3 flex items-start gap-2">
                                <span className="mt-0.5 shrink-0 text-sm font-bold text-sky-500">OK</span>
                                <p className="text-sm leading-relaxed text-slate-600">{item.solution}</p>
                            </div>
                        </div>
                    ))}
                </div>
            </div>
        </div>
    );
}

export default ProblemsSection;
