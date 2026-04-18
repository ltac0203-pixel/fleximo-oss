import type { FaqEntry } from "@/Components/ForBusiness/types";
import { faqs } from "@/Components/ForBusiness/data";
import { useId, useState } from "react";

function FaqItem({ question, answer }: FaqEntry) {
    const [open, setOpen] = useState(false);
    const id = useId();
    const buttonId = `${id}-button`;
    const panelId = `${id}-panel`;

    return (
        <div className="geo-public-panel">
            <button
                id={buttonId}
                className="flex w-full items-start gap-3 p-6 text-left"
                onClick={() => setOpen(!open)}
                aria-expanded={open}
                aria-controls={panelId}
            >
                <span className="flex h-6 w-6 shrink-0 items-center justify-center border border-sky-200 bg-sky-50 text-xs font-bold text-sky-600">
                    Q
                </span>
                <span className="flex-1 text-base font-semibold text-slate-900">{question}</span>
                <span className="shrink-0 text-sm font-semibold text-slate-400">{open ? "-" : "+"}</span>
            </button>
            <div
                id={panelId}
                role="region"
                aria-labelledby={buttonId}
                className={`border-t border-slate-100 px-6 pb-6 pt-4 ${open ? "block" : "hidden"}`}
                aria-hidden={!open}
            >
                <p className="pl-9 text-sm leading-relaxed text-slate-600">{answer}</p>
            </div>
        </div>
    );
}

function FaqSection() {
    return (
        <section id="faq" className="mt-24 scroll-mt-24">
            <div className="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
                <div>
                    <div className="flex items-center gap-2">
                        <div className="h-px w-8 bg-sky-400" />
                        <p className="text-xs font-medium uppercase tracking-widest text-sky-600">FAQ</p>
                    </div>
                    <h2 className="mt-4 text-3xl font-bold text-slate-900">よくあるご質問</h2>
                </div>
                <p className="max-w-md text-sm leading-7 text-slate-600">
                    費用、端末、導入期間など、最初に確認されやすい内容をまとめています。
                </p>
            </div>
            <div className="mt-10 grid gap-4 lg:grid-cols-2">
                {faqs.map((faq) => (
                    <FaqItem key={faq.question} question={faq.question} answer={faq.answer} />
                ))}
            </div>
        </section>
    );
}

export default FaqSection;
