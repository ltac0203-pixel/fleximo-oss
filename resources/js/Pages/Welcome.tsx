import GradientBackground from "@/Components/GradientBackground";
import WelcomeHeader from "@/Components/Welcome/sections/WelcomeHeader";
import HeroSection from "@/Components/Welcome/sections/HeroSection";
import StatsSection from "@/Components/Welcome/sections/StatsSection";
import UseSceneSection from "@/Components/Welcome/sections/UseSceneSection";
import HowItWorksSection from "@/Components/Welcome/sections/HowItWorksSection";
import FeaturesSection from "@/Components/Welcome/sections/FeaturesSection";
import FaqSection from "@/Components/Welcome/sections/FaqSection";
import FinalCtaSection from "@/Components/Welcome/sections/FinalCtaSection";
import WelcomeFooter from "@/Components/Welcome/sections/WelcomeFooter";

import { PageProps } from "@/types";
import SeoHead from "@/Components/SeoHead";
import { useSeo } from "@/Hooks/useSeo";
import type { SeoMetadata, StructuredData } from "@/types/seo";
import { cssVariables } from "@/constants/designTokens";

interface WelcomePageProps extends PageProps {
    seo?: Partial<SeoMetadata>;
    structuredData?: StructuredData | StructuredData[];
}

export default function Welcome({ auth, seo, structuredData, siteConfig }: WelcomePageProps) {
    const userName = auth?.user?.name;
    const isLoggedIn = !!auth?.user;

    const { generateMetadata } = useSeo();
    const metadata = generateMetadata(seo ?? {});

    // 構造化データ: Organization
    const organizationData: StructuredData = {
        "@context": "https://schema.org",
        "@type": "Organization",
        name: siteConfig.name,
        url: siteConfig.baseUrl,
        logo: siteConfig.logoUrl,
        description: "学食・学生食堂向けモバイルオーダーシステム",
        contactPoint: {
            "@type": "ContactPoint",
            contactType: "customer service",
            email: siteConfig.supportEmail,
        },
    };

    // 構造化データ: WebSite
    const websiteData: StructuredData = {
        "@context": "https://schema.org",
        "@type": "WebSite",
        name: siteConfig.name,
        url: siteConfig.baseUrl,
    };

    return (
        <>
            <SeoHead metadata={metadata} structuredData={structuredData ?? [organizationData, websiteData]} />
            <div className="relative min-h-screen overflow-hidden bg-white text-slate-900" style={cssVariables}>
                <div className="absolute inset-x-0 top-0 h-[54rem] max-h-screen">
                    <GradientBackground variant="hero" />
                </div>
                <div className="pointer-events-none absolute inset-x-0 top-[34rem] bottom-0">
                    <div className="geo-public-orb-sky absolute -left-20 top-[10%] h-80 w-80 blur-3xl" />
                    <div className="geo-public-orb-cyan absolute right-[-7rem] top-[28%] h-[24rem] w-[24rem] blur-3xl" />
                    <div className="geo-public-orb-ice absolute left-[30%] top-[42%] h-64 w-64 blur-3xl" />
                </div>

                <div className="relative mx-auto flex min-h-screen max-w-6xl flex-col px-6 pb-20">
                    <WelcomeHeader isLoggedIn={isLoggedIn} />
                    <main className="relative flex-1">
                        <HeroSection userName={userName} isLoggedIn={isLoggedIn} />
                        <StatsSection />
                        <UseSceneSection />
                        <HowItWorksSection />
                        <FeaturesSection />
                        <FaqSection />
                        <FinalCtaSection isLoggedIn={isLoggedIn} />
                    </main>
                    <WelcomeFooter />
                </div>
            </div>
        </>
    );
}
