import { PropsWithChildren } from "react";
import GradientBackground from "@/Components/GradientBackground";
import SkipToContentLink, { MAIN_CONTENT_ID } from "@/Components/SkipToContentLink";

export default function WideGuestLayout({ children }: PropsWithChildren) {
    return (
        <div className="relative flex min-h-screen flex-col items-center bg-white pt-6 sm:justify-center sm:pt-0">
            <SkipToContentLink />
            <GradientBackground />

            <main
                id={MAIN_CONTENT_ID}
                tabIndex={-1}
                className="relative w-full border border-slate-200 bg-white px-6 py-4 sm:max-w-md lg:max-w-4xl"
            >
                {children}
            </main>
        </div>
    );
}
