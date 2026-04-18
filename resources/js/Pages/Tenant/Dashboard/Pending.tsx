import { Head, Link } from "@inertiajs/react";
import { PageProps, Tenant } from "@/types";

interface Props extends PageProps {
    tenant: Tenant & {
        is_approved: boolean;
        status: string;
    };
}

export default function Pending({ tenant }: Props) {
    return (
        <div className="min-h-screen bg-surface">
            <Head title="審査状況 - ダッシュボード" />

            <header className="bg-white border-b border-edge">
                <div className="max-w-3xl mx-auto px-4 py-4 flex items-center justify-between">
                    <h1 className="text-lg font-semibold text-ink">{tenant.name}</h1>
                    <Link
                        href={route("logout")}
                        method="post"
                        as="button"
                        className="text-sm text-muted hover:text-ink-light"
                    >
                        ログアウト
                    </Link>
                </div>
            </header>

            <main className="max-w-3xl mx-auto px-4 py-8">
                <div className="bg-white border border-edge p-6 mb-6">
                    <div className="flex items-center justify-between mb-4">
                        <h2 className="text-xl font-bold text-ink">審査状況</h2>
                        <span className="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-sky-100 text-sky-800">
                            審査中
                        </span>
                    </div>
                    <p className="text-ink-light">
                        現在、テナントの承認待ちです。承認が完了するまでしばらくお待ちください。
                    </p>
                </div>

                <div className="bg-sky-50 p-6">
                    <h3 className="text-sm font-medium text-sky-800">審査について</h3>
                    <p className="mt-2 text-sm text-sky-700">
                        ご不明な点がございましたら、管理者までお問い合わせください。
                    </p>
                </div>
            </main>
        </div>
    );
}
