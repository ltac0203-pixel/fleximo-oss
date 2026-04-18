import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Head, usePage } from "@inertiajs/react";

export default function Dashboard() {
    const { url } = usePage();
    const verified = new URLSearchParams(url.split("?")[1] || "").get("verified") === "1";

    return (
        <AuthenticatedLayout
            header={<h2 className="text-xl font-semibold leading-tight text-gray-800">ダッシュボード</h2>}
        >
            <Head title="ダッシュボード" />

            <div className="py-12">
                <div className="mx-auto max-w-5xl sm:px-6 lg:px-8">
                    {verified && (
                        <div className="mb-4 bg-green-50 border border-green-200 p-4">
                            <p className="text-sm font-medium text-green-800">
                                メールアドレスの認証が完了しました。ご利用ありがとうございます。
                            </p>
                        </div>
                    )}
                    <div className="bg-white p-6">
                        <h3 className="text-lg font-medium text-gray-900 mb-2">ようこそ！</h3>
                        <p className="text-gray-600">ログインに成功しました。</p>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
