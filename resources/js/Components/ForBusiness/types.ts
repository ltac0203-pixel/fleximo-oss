export type PrimaryFeatureIconKey = "qr-order" | "kds" | "cashless";

export interface PrimaryFeature {
    title: string;
    body: string;
    iconKey: PrimaryFeatureIconKey;
}

export interface SecondaryFeature {
    title: string;
    body: string;
}

export interface FaqEntry {
    question: string;
    answer: string;
}

export interface ProblemItem {
    question: string;
    solution: string;
}

export interface ImpactStat {
    value: string;
    label: string;
}

export interface PricingItem {
    label: string;
    value: string;
}

export interface FlowStep {
    number: number;
    title: string;
    body: string;
    time: string;
}
