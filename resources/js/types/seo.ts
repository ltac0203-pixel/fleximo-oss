/**
 * SEOメタデータの型定義
 */
export interface SeoMetadata {
    title: string;
    description: string;
    keywords?: string;
    ogType?: "website" | "article" | "product";
    ogImage?: string;
    ogImageAlt?: string;
    twitterCard?: "summary" | "summary_large_image";
    canonical?: string;
    noindex?: boolean;
    nofollow?: boolean;
}

/**
 * 構造化データの型定義
 */
export type StructuredDataType =
    | "Organization"
    | "LocalBusiness"
    | "FAQPage"
    | "BreadcrumbList"
    | "Product"
    | "WebSite";

export type JsonLdPrimitive = string | number | boolean | null;
export type JsonLdValue = JsonLdPrimitive | JsonLdObject | JsonLdValue[];

export interface JsonLdObject {
    [key: string]: JsonLdValue;
}

export interface StructuredData extends JsonLdObject {
    "@context": "https://schema.org" | "http://schema.org";
    "@type": StructuredDataType;
}
