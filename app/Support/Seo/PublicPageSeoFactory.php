<?php

declare(strict_types=1);

namespace App\Support\Seo;

use App\Models\Tenant;
use App\Models\TenantBusinessHour;
use Illuminate\Support\Collection;

class PublicPageSeoFactory
{
    public function __construct(
        private readonly PageSeoResolver $pageSeoResolver
    ) {}

    public function welcome(): array
    {
        return [
            'seo' => $this->pageSeoResolver->resolve('welcome'),
            'structuredData' => [
                $this->organizationSchema(),
                $this->websiteSchema(),
                [
                    '@context' => 'https://schema.org',
                    '@type' => 'Service',
                    'name' => 'Fleximo',
                    'serviceType' => 'モバイルオーダープラットフォーム',
                    'provider' => [
                        '@type' => 'Organization',
                        'name' => 'Fleximo',
                        'url' => $this->baseUrl(),
                    ],
                    'areaServed' => 'JP',
                    'description' => '日本の飲食店向けマルチテナント モバイルオーダー。QRコード注文、PayPay・クレジットカード決済、受け取り導線をまとめたオープンソースのモバイルオーダーサービスです。学食・フードコートにも対応。',
                    'url' => $this->baseUrl(),
                ],
            ],
        ];
    }

    public function forBusiness(): array
    {
        return [
            'seo' => $this->pageSeoResolver->resolve('for_business'),
            'structuredData' => [
                $this->organizationSchema(),
                [
                    '@context' => 'https://schema.org',
                    '@type' => 'Service',
                    'name' => 'Fleximo for Business',
                    'serviceType' => '飲食店向けモバイルオーダー導入支援',
                    'provider' => [
                        '@type' => 'Organization',
                        'name' => 'Fleximo',
                        'url' => $this->baseUrl(),
                    ],
                    'areaServed' => 'JP',
                    'audience' => [
                        '@type' => 'BusinessAudience',
                        'audienceType' => '飲食店・学食・フードコート事業者',
                    ],
                    'offers' => [
                        '@type' => 'OfferCatalog',
                        'name' => 'Fleximo導入プラン',
                        'itemListElement' => [
                            [
                                '@type' => 'Offer',
                                'name' => '初期費用',
                                'price' => '0',
                                'priceCurrency' => 'JPY',
                            ],
                            [
                                '@type' => 'Offer',
                                'name' => '月額固定費',
                                'price' => '0',
                                'priceCurrency' => 'JPY',
                            ],
                        ],
                    ],
                    'description' => 'QRコード注文、KDS、PayPay・クレジットカード決済をまとめて導入できる飲食店・学食・フードコート向けのマルチテナント モバイルオーダーサービスです。',
                    'url' => $this->baseUrl().'/for-business',
                ],
                $this->faqPageSchema([
                    [
                        'question' => '初期費用や月額固定費はかかりますか？',
                        'answer' => '初期費用・月額固定費はかかりません。売上に応じた手数料のみで利用できます。',
                    ],
                    [
                        'question' => '専用端末は必要ですか？',
                        'answer' => '専用端末は不要です。既存のスマートフォンやタブレットで利用できます。',
                    ],
                    [
                        'question' => 'どの決済方法に対応していますか？',
                        'answer' => 'クレジットカード各種とPayPayに対応しています。3Dセキュア認証にも対応しています。',
                    ],
                ]),
            ],
        ];
    }

    public function contact(): array
    {
        return [
            'seo' => $this->pageSeoResolver->resolve('contact'),
            'structuredData' => [
                $this->organizationSchema(),
                [
                    '@context' => 'https://schema.org',
                    '@type' => 'ContactPage',
                    'name' => 'Fleximo お問い合わせ',
                    'url' => $this->baseUrl().'/contact',
                    'description' => 'Fleximoの導入相談、サポート依頼、お問い合わせを受け付ける窓口です。',
                    'mainEntity' => [
                        '@type' => 'Organization',
                        'name' => 'Fleximo',
                        'url' => $this->baseUrl(),
                        'contactPoint' => [
                            '@type' => 'ContactPoint',
                            'contactType' => 'customer support',
                            'email' => (string) config('seo.site.support_email', 'support@example.com'),
                        ],
                    ],
                ],
            ],
        ];
    }

    public function tenantApplication(): array
    {
        return [
            'seo' => $this->pageSeoResolver->resolve('tenant_application'),
            'structuredData' => [
                $this->organizationSchema(),
                [
                    '@context' => 'https://schema.org',
                    '@type' => 'WebPage',
                    'name' => 'Fleximo 加盟店申し込み',
                    'url' => $this->baseUrl().'/tenant-application',
                    'description' => 'Fleximoの加盟店申し込みページです。飲食店・学食・フードコート向けにモバイルオーダー導入を申請できます。',
                    'isPartOf' => [
                        '@type' => 'WebSite',
                        'name' => 'Fleximo',
                        'url' => $this->baseUrl(),
                    ],
                ],
            ],
        ];
    }

    public function tenantApplicationComplete(): array
    {
        return [
            'seo' => $this->pageSeoResolver->resolve('tenant_application_complete'),
            'structuredData' => [
                [
                    '@context' => 'https://schema.org',
                    '@type' => 'WebPage',
                    'name' => 'Fleximo 加盟店申し込み完了',
                    'url' => $this->baseUrl().'/tenant-application/complete',
                    'description' => 'Fleximoの加盟店申し込み完了ページです。',
                    'isPartOf' => [
                        '@type' => 'WebSite',
                        'name' => 'Fleximo',
                        'url' => $this->baseUrl(),
                    ],
                ],
            ],
        ];
    }

    public function tenantMenu(Tenant $tenant, array $menu): array
    {
        $menuUrl = route('order.menu', ['tenant' => $tenant->slug]);
        $categories = collect($menu['categories'] ?? []);
        $menuItemCount = $this->countMenuItems($categories);
        $categoryNames = $categories
            ->pluck('name')
            ->filter()
            ->take(3)
            ->implode('、');

        $descriptionSegments = array_filter([
            "{$tenant->name}のモバイルオーダーメニューページです",
            $menuItemCount > 0 ? "{$menuItemCount}品のメニューを掲載" : '現在掲載中のメニューはありません',
            $tenant->address ? "所在地は{$tenant->address}" : null,
            '営業時間や提供状況を確認してスマホから注文できます',
        ]);

        $seo = [
            'title' => "{$tenant->name} メニュー",
            'description' => implode('。', $descriptionSegments).'。',
            'keywords' => implode(',', array_filter([
                $tenant->name,
                'モバイルオーダー',
                'メニュー',
                'テイクアウト',
                $categoryNames,
            ])),
            'canonical' => $menuUrl,
            'ogType' => 'website',
            'ogImageAlt' => "{$tenant->name}のモバイルオーダーメニュー",
            'noindex' => $menuItemCount === 0,
        ];

        $structuredData = [
            $this->breadcrumbSchema("{$tenant->name} メニュー", $menuUrl),
            $this->restaurantSchema($tenant, $menuUrl, $seo['description']),
        ];

        $menuSchema = $this->menuSchema($categories, $menuUrl);

        if ($menuSchema !== null) {
            $structuredData[] = $menuSchema;
        }

        return [
            'seo' => $seo,
            'structuredData' => $structuredData,
        ];
    }

    private function organizationSchema(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => 'Fleximo',
            'url' => $this->baseUrl(),
            'logo' => $this->baseUrl().'/og-image.svg',
            'description' => '日本の飲食店向けマルチテナント モバイルオーダー OSS (PayPay / クレジットカード対応)',
            'contactPoint' => [
                '@type' => 'ContactPoint',
                'contactType' => 'customer support',
                'email' => (string) config('seo.site.support_email', 'support@example.com'),
            ],
        ];
    }

    private function websiteSchema(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => 'Fleximo',
            'url' => $this->baseUrl(),
        ];
    }

    private function faqPageSchema(array $faqEntries): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => array_map(
                static fn (array $entry): array => [
                    '@type' => 'Question',
                    'name' => $entry['question'],
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => $entry['answer'],
                    ],
                ],
                $faqEntries
            ),
        ];
    }

    private function breadcrumbSchema(string $pageName, string $pageUrl): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                [
                    '@type' => 'ListItem',
                    'position' => 1,
                    'name' => 'Fleximo',
                    'item' => $this->baseUrl(),
                ],
                [
                    '@type' => 'ListItem',
                    'position' => 2,
                    'name' => $pageName,
                    'item' => $pageUrl,
                ],
            ],
        ];
    }

    private function restaurantSchema(Tenant $tenant, string $menuUrl, string $description): array
    {
        $tenant->loadMissing('businessHours');

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Restaurant',
            'name' => $tenant->name,
            'url' => $menuUrl,
            'description' => $description,
            'image' => $this->baseUrl().'/og-image.svg',
            'menu' => $menuUrl,
        ];

        if (! empty($tenant->address)) {
            $schema['address'] = [
                '@type' => 'PostalAddress',
                'streetAddress' => $tenant->address,
                'addressCountry' => 'JP',
            ];
        }

        if (! empty($tenant->phone)) {
            $schema['telephone'] = $tenant->phone;
        }

        if (! empty($tenant->email)) {
            $schema['email'] = $tenant->email;
        }

        $openingHoursSpecification = $tenant->businessHours
            ->map(fn (TenantBusinessHour $businessHour): array => [
                '@type' => 'OpeningHoursSpecification',
                'dayOfWeek' => $this->schemaDayOfWeek($businessHour->weekday),
                'opens' => substr((string) $businessHour->open_time, 0, 5),
                'closes' => substr((string) $businessHour->close_time, 0, 5),
            ])
            ->values()
            ->all();

        if ($openingHoursSpecification !== []) {
            $schema['openingHoursSpecification'] = $openingHoursSpecification;
        }

        return $schema;
    }

    private function menuSchema(Collection $categories, string $menuUrl): ?array
    {
        $sections = $categories
            ->map(function (array $category): ?array {
                $items = collect($category['items'] ?? [])
                    ->take(8)
                    ->map(function (array $item): array {
                        $menuItem = [
                            '@type' => 'MenuItem',
                            'name' => $item['name'],
                            'offers' => [
                                '@type' => 'Offer',
                                'price' => (string) $item['price'],
                                'priceCurrency' => 'JPY',
                                'availability' => $item['is_available']
                                    ? 'https://schema.org/InStock'
                                    : 'https://schema.org/OutOfStock',
                            ],
                        ];

                        if (! empty($item['description'])) {
                            $menuItem['description'] = $item['description'];
                        }

                        return $menuItem;
                    })
                    ->values()
                    ->all();

                if ($items === []) {
                    return null;
                }

                return [
                    '@type' => 'MenuSection',
                    'name' => $category['name'],
                    'hasMenuItem' => $items,
                ];
            })
            ->filter()
            ->take(6)
            ->values()
            ->all();

        if ($sections === []) {
            return null;
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'Menu',
            'name' => 'Fleximo Menu',
            'url' => $menuUrl,
            'hasMenuSection' => $sections,
        ];
    }

    private function countMenuItems(Collection $categories): int
    {
        return $categories->sum(
            static fn (array $category): int => count($category['items'] ?? [])
        );
    }

    private function schemaDayOfWeek(int $weekday): string
    {
        return match ($weekday) {
            0 => 'https://schema.org/Sunday',
            1 => 'https://schema.org/Monday',
            2 => 'https://schema.org/Tuesday',
            3 => 'https://schema.org/Wednesday',
            4 => 'https://schema.org/Thursday',
            5 => 'https://schema.org/Friday',
            default => 'https://schema.org/Saturday',
        };
    }

    private function baseUrl(): string
    {
        return rtrim(
            (string) config('seo.site.base_url', config('app.url', 'https://example.com')),
            '/'
        );
    }
}
