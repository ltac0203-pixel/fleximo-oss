export interface StatItem {
    value: string;
    label: string;
    description?: string;
}

export type HowItWorksIconKey = "qr-scan" | "menu-select" | "checkout";

export interface HowItWorksStep {
    step: number;
    title: string;
    body: string;
    iconKey: HowItWorksIconKey;
}

export type FeatureIconKey = "order" | "status" | "payment";

export interface FeatureItem {
    eyebrow?: string;
    title: string;
    body: string;
    meta: string;
    iconKey: FeatureIconKey;
}

export type UseSceneIconKey = "between-classes" | "from-seat" | "pickup-fast";

export interface UseSceneItem {
    title: string;
    body: string;
    status: string;
    iconKey: UseSceneIconKey;
}

export interface FooterLink {
    label: string;
    href?: string;
    routeName?: string;
}

export interface FaqItem {
    question: string;
    answer: string;
}
