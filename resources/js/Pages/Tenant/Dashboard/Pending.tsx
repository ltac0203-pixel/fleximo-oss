import { Head, Link } from "@inertiajs/react";
import { PageProps, Tenant, TenantApplicationDetail } from "@/types";

interface Props extends PageProps {
    tenant: Tenant & {
        is_approved: boolean;
        status: string;
    };
    application: TenantApplicationDetail | null;
}

export default function Pending({ tenant, application }: Props) {
    const getStatusInfo = () => {
        if (!application) {
            return {
                badge: "審査中",
                badgeColor: "bg-sky-100 text-sky-800",
                description: "現在、申請内容を審査しております。",
            };
        }

        switch (application.status) {
            case "pending":
                return {
                    badge: "審査待ち",
                    badgeColor: "bg-cyan-100 text-cyan-800",
                    description: "申請を受け付けました。審査開始までしばらくお待ちください。",
                };
            case "under_review":
                return {
                    badge: "審査中",
                    badgeColor: "bg-sky-100 text-sky-800",
                    description: "現在、申請内容を審査しております。結果が出るまでしばらくお待ちください。",
                };
            case "rejected":
                return {
                    badge: "却下",
                    badgeColor: "bg-red-100 text-red-800",
                    description: "申し訳ございませんが、今回の申請は承認されませんでした。",
                };
            default:
                return {
                    badge: "確認中",
                    badgeColor: "bg-surface-dim text-ink",
                    description: "申請状況を確認しております。",
                };
        }
    };

    const statusInfo = getStatusInfo();

    return (
        <div className="min-h-screen bg-surface">
            <Head title="審査状況 - ダッシュボード" />

            {/* ヘッダー を明示し、実装意図の誤読を防ぐ。 */}
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
                {/* ステータスカード を明示し、実装意図の誤読を防ぐ。 */}
                <div className="bg-white border border-edge p-6 mb-6">
                    <div className="flex items-center justify-between mb-4">
                        <h2 className="text-xl font-bold text-ink">審査状況</h2>
                        <span
                            className={`inline-flex items-center px-3 py-1 rounded-full text-sm font-medium ${statusInfo.badgeColor}`}
                        >
                            {statusInfo.badge}
                        </span>
                    </div>

                    <p className="text-ink-light mb-4">{statusInfo.description}</p>

                    {application && (
                        <div className="border-t border-edge pt-4 mt-4">
                            <dl className="space-y-2">
                                <div className="flex justify-between text-sm">
                                    <dt className="text-muted">申請番号</dt>
                                    <dd className="font-mono text-ink">{application.application_code}</dd>
                                </div>
                                <div className="flex justify-between text-sm">
                                    <dt className="text-muted">申請日時</dt>
                                    <dd className="text-ink">
                                        {new Date(application.created_at).toLocaleDateString("ja-JP", {
                                            year: "numeric",
                                            month: "long",
                                            day: "numeric",
                                            hour: "2-digit",
                                            minute: "2-digit",
                                        })}
                                    </dd>
                                </div>
                            </dl>
                        </div>
                    )}

                    {application?.status === "rejected" && application.rejection_reason && (
                        <div className="mt-4 p-4 bg-red-50">
                            <h3 className="text-sm font-medium text-red-800 mb-1">却下理由</h3>
                            <p className="text-sm text-red-700">{application.rejection_reason}</p>
                        </div>
                    )}
                </div>

                {/* 審査の流れ を明示し、実装意図の誤読を防ぐ。 */}
                <div className="bg-white border border-edge p-6 mb-6">
                    <h2 className="text-lg font-bold text-gray-900 mb-4">審査の流れ</h2>

                    <ol className="relative border-l border-edge ml-3">
                        <li className="mb-6 ml-6">
                            <span
                                className={`absolute -left-3 flex h-6 w-6 items-center justify-center rounded-full ${
                                    application?.status === "pending" ||
                                    application?.status === "under_review" ||
                                    application?.status === "approved"
                                        ? "bg-primary-dark text-white"
                                        : "bg-surface-dim text-muted"
                                }`}
                            >
                                1
                            </span>
                            <h3 className="font-medium text-ink">申請受付</h3>
                            <p className="text-sm text-muted">申請フォームからの申請を受け付けました</p>
                        </li>
                        <li className="mb-6 ml-6">
                            <span
                                className={`absolute -left-3 flex h-6 w-6 items-center justify-center rounded-full ${
                                    application?.status === "under_review" || application?.status === "approved"
                                        ? "bg-primary-dark text-white"
                                        : "bg-surface-dim text-muted"
                                }`}
                            >
                                2
                            </span>
                            <h3 className="font-medium text-ink">審査中</h3>
                            <p className="text-sm text-muted">担当者が申請内容を確認しています</p>
                        </li>
                        <li className="ml-6">
                            <span
                                className={`absolute -left-3 flex h-6 w-6 items-center justify-center rounded-full ${
                                    application?.status === "approved"
                                        ? "bg-green-600 text-white"
                                        : "bg-surface-dim text-muted"
                                }`}
                            >
                                3
                            </span>
                            <h3 className="font-medium text-ink">審査完了</h3>
                            <p className="text-sm text-muted">承認後、すべての機能をご利用いただけます</p>
                        </li>
                    </ol>
                </div>

                {/* お知らせ を明示し、実装意図の誤読を防ぐ。 */}
                <div className="bg-sky-50 p-6">
                    <div className="flex">
                        <div className="flex-shrink-0">
                            <svg className="h-5 w-5 text-sky-400" viewBox="0 0 20 20" fill="currentColor">
                                <path
                                    fillRule="evenodd"
                                    d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                    clipRule="evenodd"
                                />
                            </svg>
                        </div>
                        <div className="ml-3">
                            <h3 className="text-sm font-medium text-sky-800">審査について</h3>
                            <div className="mt-2 text-sm text-sky-700">
                                <p>
                                    審査には通常1〜3営業日程度お時間をいただいております。
                                    審査結果は登録いただいたメールアドレスにご連絡いたします。
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    );
}
