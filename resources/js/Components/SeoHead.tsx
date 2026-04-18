import { Head } from "@inertiajs/react";
import { withStableKeys } from "@/Utils/stableKeys";
import type { SeoMetadata, StructuredData } from "@/types/seo";

interface SeoHeadProps {
    metadata: SeoMetadata;
    structuredData?: StructuredData | StructuredData[];
}

function buildRobotsContent(metadata: SeoMetadata): string {
    return [
        metadata.noindex ? "noindex" : "index",
        metadata.nofollow ? "nofollow" : "follow",
        "max-snippet:-1",
        "max-image-preview:large",
        "max-video-preview:-1",
    ].join(",");
}

function getCspNonce(): string | undefined {
    if (typeof document === "undefined") {
        return undefined;
    }

    const nonce = document.querySelector('meta[name="csp-nonce"]')?.getAttribute("content");
    return nonce && nonce.length > 0 ? nonce : undefined;
}

function safeJsonLd(data: StructuredData): string {
    return JSON.stringify(data).replace(/<\//g, "<\\/");
}

function getStructuredDataBaseKey(data: StructuredData): string {
    const identifiers = [data["@id"], data["url"], data["name"], data["headline"]];

    for (const identifier of identifiers) {
        if (typeof identifier === "string" && identifier.length > 0) {
            return `${data["@type"]}:${identifier}`;
        }
    }

    return safeJsonLd(data);
}

export default function SeoHead({ metadata, structuredData }: SeoHeadProps) {
    const cspNonce = getCspNonce();
    const structuredDataArray = Array.isArray(structuredData) ? structuredData : structuredData ? [structuredData] : [];
    const structuredDataEntries = withStableKeys(structuredDataArray, getStructuredDataBaseKey);
    const robotsContent = buildRobotsContent(metadata);

    return (
        <Head>
            {/* 基本メタタグ */}
            <title>{metadata.title}</title>
            <meta name="description" content={metadata.description} />
            {metadata.keywords && <meta name="keywords" content={metadata.keywords} />}
            <meta name="robots" content={robotsContent} />
            <meta name="googlebot" content={robotsContent} />
            <meta name="application-name" content="Fleximo" />
            <meta name="apple-mobile-web-app-title" content="Fleximo" />
            <meta name="theme-color" content="#0f172a" />

            {/* 正規URL */}
            <link rel="canonical" href={metadata.canonical} />

            {/* Open Graphタグ */}
            <meta property="og:type" content={metadata.ogType} />
            <meta property="og:title" content={metadata.title} />
            <meta property="og:description" content={metadata.description} />
            <meta property="og:image" content={metadata.ogImage} />
            {metadata.ogImageAlt && <meta property="og:image:alt" content={metadata.ogImageAlt} />}
            <meta property="og:url" content={metadata.canonical} />
            <meta property="og:site_name" content="Fleximo" />
            <meta property="og:locale" content="ja_JP" />

            {/* Twitterカードタグ */}
            <meta name="twitter:card" content={metadata.twitterCard} />
            <meta name="twitter:title" content={metadata.title} />
            <meta name="twitter:description" content={metadata.description} />
            <meta name="twitter:image" content={metadata.ogImage} />
            {metadata.ogImageAlt && <meta name="twitter:image:alt" content={metadata.ogImageAlt} />}

            {/* 構造化データ（JSON-LD）
               Security: safeJsonLd() が </ をエスケープし script injection を防止。
               入力は開発者定義の StructuredData 型のみ。 */}
            {structuredDataEntries.map(({ item: data, key }) => (
                <script
                    key={key}
                    type="application/ld+json"
                    nonce={cspNonce}
                    dangerouslySetInnerHTML={{
                        __html: safeJsonLd(data),
                    }}
                />
            ))}
        </Head>
    );
}
