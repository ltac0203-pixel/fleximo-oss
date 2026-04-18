import { usePage } from '@inertiajs/react';
import type { SeoMetadata } from '@/types/seo';
import type { SiteConfig } from '@/types/common';

/**
 * SEOメタデータのデフォルト値を提供するhook
 */
export function useSeo() {
  const { url, props } = usePage<{ siteConfig: SiteConfig }>();
  const siteConfig = props.siteConfig;

  const baseUrl = siteConfig.baseUrl;
  const siteName = siteConfig.name;
  const defaultImage = siteConfig.defaultImageUrl;
  const defaultDescription =
    '飲食店・学食向けモバイルオーダープラットフォーム。QRコード注文、キャッシュレス決済、KDSで受注から受け取りまでを効率化します。';

  /**
   * ページのメタデータを生成
   */
  const generateMetadata = (metadata: Partial<SeoMetadata>): SeoMetadata => {
    const title = metadata.title
      ? metadata.title.includes(siteName)
        ? metadata.title
        : `${metadata.title} | ${siteName}`
      : siteName;

    return {
      title,
      description: metadata.description || defaultDescription,
      keywords: metadata.keywords,
      ogType: metadata.ogType || 'website',
      ogImage: metadata.ogImage || defaultImage,
      ogImageAlt: metadata.ogImageAlt || siteName,
      twitterCard: metadata.twitterCard || 'summary_large_image',
      canonical: metadata.canonical || `${baseUrl}${url}`,
      noindex: metadata.noindex,
      nofollow: metadata.nofollow,
    };
  };

  return {
    generateMetadata,
    baseUrl,
    siteName,
    defaultImage,
  };
}
