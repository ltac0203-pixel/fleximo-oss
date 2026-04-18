import { Disclosure } from "@headlessui/react";
import { faqItems } from "@/Components/Welcome/data";

function FaqSection() {
    return (
        <section className="relative mt-32">
            <div className="text-center">
                <div className="mx-auto flex items-center justify-center gap-3">
                    <div className="h-px w-8 bg-sky-400" />
                    <p className="text-sm font-semibold uppercase tracking-[0.28em] text-sky-600">Questions</p>
                </div>
                <h2 className="mt-4 text-3xl font-bold text-slate-900 sm:text-4xl">
                    よくある
                    <span className="text-sky-500">ご質問</span>
                </h2>
                <p className="mx-auto mt-4 max-w-2xl text-sm leading-7 text-slate-600 sm:text-base">
                    はじめて使う前に気になるポイントをまとめました。
                    それでも解決しない場合はお問い合わせからお気軽にどうぞ。
                </p>
            </div>

            <div className="mx-auto mt-12 max-w-3xl space-y-4">
                {faqItems.map((faq) => (
                    <Disclosure key={faq.question}>
                        {({ open }) => (
                            <div className="geo-public-panel-soft overflow-hidden">
                                <Disclosure.Button className="flex w-full items-center justify-between gap-4 px-6 py-5 text-left">
                                    <span className="text-base font-bold text-slate-900 sm:text-lg">
                                        {faq.question}
                                    </span>
                                    <span
                                        aria-hidden="true"
                                        className={`flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-sky-200 bg-white text-sky-600 transition-transform duration-200 ${
                                            open ? "rotate-45" : ""
                                        }`}
                                    >
                                        <svg
                                            className="h-4 w-4"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                            strokeWidth={2}
                                        >
                                            <path
                                                strokeLinecap="round"
                                                strokeLinejoin="round"
                                                d="M12 4.5v15m7.5-7.5h-15"
                                            />
                                        </svg>
                                    </span>
                                </Disclosure.Button>
                                <Disclosure.Panel className="border-t border-sky-100 bg-white/60 px-6 py-5 text-sm leading-7 text-slate-600 sm:text-base">
                                    {faq.answer}
                                </Disclosure.Panel>
                            </div>
                        )}
                    </Disclosure>
                ))}
            </div>
        </section>
    );
}

export default FaqSection;
