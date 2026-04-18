import { PropsWithChildren } from "react";
import GradientBackground from "@/Components/GradientBackground";
import SkipToContentLink, { MAIN_CONTENT_ID } from "@/Components/SkipToContentLink";

export default function Guest({ children }: PropsWithChildren) {
    return (
        <div className="relative flex min-h-screen flex-col items-center bg-white pt-6 sm:justify-center sm:pt-0">
            <SkipToContentLink />
            <GradientBackground />

            {/* コンテンツカード を明示し、実装意図の誤読を防ぐ。 */}
            <main
                id={MAIN_CONTENT_ID}
                tabIndex={-1}
                className="relative w-full border border-slate-200 bg-white px-6 py-4 sm:max-w-md"
            >
                {children}
            </main>
        </div>
    );
}
