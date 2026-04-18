import { PropsWithChildren } from "react";

interface LegalSectionProps extends PropsWithChildren {
    title: string;
    id?: string;
}

export default function LegalSection({ title, children, id }: LegalSectionProps) {
    return (
        <section id={id} className="scroll-mt-6">
            <div className="border-l-4 border-sky-500 pl-6">
                <h2 className="mb-4 text-2xl font-bold text-ink">{title}</h2>
                <div className="space-y-4 text-ink-light leading-relaxed">{children}</div>
            </div>
        </section>
    );
}
