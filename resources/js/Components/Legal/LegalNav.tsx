import { Link } from "@inertiajs/react";

export default function LegalNav() {
    const legalPages = [
        {
            title: "ユーザー利用規約",
            href: route("legal.terms"),
            description: "サービスのご利用にあたっての約束事",
        },
        {
            title: "プライバシーポリシー",
            href: route("legal.privacy-policy"),
            description: "個人情報の取り扱いについて",
        },
        {
            title: "特定商取引法に基づく表記",
            href: route("legal.transactions"),
            description: "事業者情報・返金規定など",
        },
        {
            title: "テナント利用規約",
            href: route("legal.tenant-terms"),
            description: "出店者様向けの規約",
        },
    ];

    return (
        <div>
            <h3 className="mb-4 text-lg font-semibold text-ink">法的ページ一覧</h3>
            <div className="grid gap-4 sm:grid-cols-2">
                {legalPages.map((page) => (
                    <Link
                        key={page.href}
                        href={page.href}
                        className="group border border-edge p-4 hover:border-sky-500"
                    >
                        <h4 className="font-semibold text-ink group-hover:text-sky-600">{page.title}</h4>
                        <p className="mt-1 text-sm text-ink-light">{page.description}</p>
                    </Link>
                ))}
            </div>
        </div>
    );
}
