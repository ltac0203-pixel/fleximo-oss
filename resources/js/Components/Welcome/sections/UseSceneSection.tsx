import { useScenes } from "@/Components/Welcome/data";

const sceneCaptions = ["移動しながら準備", "席から落ち着いて注文", "受け取り直前だけ動く"] as const;

function UseSceneSection() {
    return (
        <section className="relative mt-28 sm:mt-32">
            <div className="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
                <div>
                    <div className="mx-auto flex items-center gap-3 md:mx-0">
                        <div className="h-px w-8 bg-sky-400" />
                        <p className="text-sm font-semibold uppercase tracking-[0.28em] text-sky-600">
                            Use scenes
                        </p>
                    </div>
                    <h2 className="mt-4 text-3xl font-bold text-slate-900 sm:text-4xl">
                        ランチの流れに、
                        <span className="text-sky-500">自然に溶け込む</span>
                    </h2>
                </div>
                <p className="max-w-xl text-sm leading-7 text-slate-600 sm:text-base">
                    Fleximoは、食堂に向かう前後の小さな時間をそのまま注文体験に変えます。
                    忙しい昼休みでも、手順を意識せずに使える軽さを目指しています。
                </p>
            </div>

            <div className="relative mt-10 grid gap-6 lg:grid-cols-3">
                <div className="geo-public-divider absolute left-[14%] right-[14%] top-[6.5rem] hidden h-px lg:block" />
                {useScenes.map((scene, index) => (
                    <article
                        key={scene.title}
                        className={`geo-public-panel geo-surface-interactive relative overflow-hidden px-6 py-7 sm:px-7 ${
                            index === 1 ? "lg:-translate-y-4" : "lg:translate-y-3"
                        }`}
                    >
                        <div className="geo-public-mesh absolute inset-0 opacity-80" />
                        <div className="absolute right-0 top-0 h-24 w-24 bg-gradient-to-bl from-sky-100/80 to-transparent" />
                        <div className="relative flex items-start justify-end gap-4">
                            <span className="rounded-full border border-sky-200 bg-white px-3 py-1 text-xs font-semibold text-sky-700">
                                {scene.status}
                            </span>
                        </div>
                        <div className="relative mt-6 flex items-center justify-between gap-4">
                            <span className="text-xs font-semibold uppercase tracking-[0.26em] text-sky-600">
                                0{index + 1}
                            </span>
                            <span className="text-xs font-medium text-slate-400">{sceneCaptions[index]}</span>
                        </div>
                        <h3 className="relative mt-6 text-xl font-bold text-slate-900">{scene.title}</h3>
                        <p className="relative mt-3 text-sm leading-7 text-slate-600">{scene.body}</p>
                        <div className="relative mt-6 h-px w-full bg-gradient-to-r from-sky-200 via-cyan-200 to-transparent" />
                    </article>
                ))}
            </div>
        </section>
    );
}

export default UseSceneSection;
