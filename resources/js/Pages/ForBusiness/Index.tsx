import GradientBackground from "@/Components/GradientBackground";
import SeoHead from "@/Components/SeoHead";
import { faqs } from "@/Components/ForBusiness/data";
import { BusinessFooter, BusinessHeader } from "@/Components/ForBusiness/sections/BusinessLayout";
import CtaSection from "@/Components/ForBusiness/sections/CtaSection";
import FaqSection from "@/Components/ForBusiness/sections/FaqSection";
import FeaturesSection from "@/Components/ForBusiness/sections/FeaturesSection";
import FlowSection from "@/Components/ForBusiness/sections/FlowSection";
import HeroSection, { ImpactStatsSection } from "@/Components/ForBusiness/sections/HeroSection";
import PricingSection, { TrustedBrandsSection } from "@/Components/ForBusiness/sections/PricingSection";
import ProblemsSection from "@/Components/ForBusiness/sections/ProblemsSection";
import { useSeo } from "@/Hooks/useSeo";
import { PageProps } from "@/types";
import type { SeoMetadata, StructuredData } from "@/types/seo";
import { cssVariables } from "@/constants/designTokens";
import { useMemo } from "react";

interface ForBusinessPageProps extends PageProps {
    seo?: Partial<SeoMetadata>;
    structuredData?: StructuredData | StructuredData[];
}

export default function ForBusiness({ seo, structuredData }: ForBusinessPageProps) {
    const { generateMetadata } = useSeo();

    const metadata = generateMetadata(seo ?? {});
    const faqItems = faqs;

    const faqData: StructuredData = useMemo(
        () => ({
            "@context": "https://schema.org",
            "@type": "FAQPage",
            mainEntity: faqItems.map((faq) => ({
                "@type": "Question",
                name: faq.question,
                acceptedAnswer: {
                    "@type": "Answer",
                    text: faq.answer,
                },
            })),
        }),
        [faqItems],
    );

    return (
        <>
            <SeoHead metadata={metadata} structuredData={structuredData ?? faqData} />
            <div className="relative min-h-screen overflow-hidden bg-white text-slate-900" style={cssVariables}>
                <div className="absolute inset-x-0 top-0 h-[48rem]">
                    <GradientBackground variant="business" />
                </div>
                <div className="pointer-events-none absolute inset-x-0 top-[26rem] bottom-0">
                    <div className="geo-public-orb-sky absolute -left-20 top-[14%] h-80 w-80 blur-3xl" />
                    <div className="geo-public-orb-cyan absolute right-[-7rem] top-[34%] h-[26rem] w-[26rem] blur-3xl" />
                    <div className="geo-public-orb-ice absolute left-[32%] top-[48%] h-64 w-64 blur-3xl" />
                </div>
                <div className="relative mx-auto flex min-h-screen max-w-6xl flex-col px-6 pb-20">
                    <BusinessHeader />
                    <main className="relative flex-1">
                        <HeroSection />
                        <ImpactStatsSection />
                        <ProblemsSection />
                        <FeaturesSection />
                        <TrustedBrandsSection />
                        <PricingSection />
                        <FlowSection />
                        <FaqSection />
                        <CtaSection />
                    </main>
                    <BusinessFooter />
                </div>
            </div>
        </>
    );
}
