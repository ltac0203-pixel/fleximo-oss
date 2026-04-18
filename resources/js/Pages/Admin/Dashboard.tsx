import AdminLayout from "@/Layouts/AdminLayout";
import { Head } from "@inertiajs/react";

export default function Dashboard() {
    return (
        <AdminLayout header={<h2 className="text-xl font-semibold leading-tight text-ink">ダッシュボード</h2>}>
            <Head title="管理者ダッシュボード" />

            <div className="border border-edge bg-white p-6">
                <p className="text-sm text-muted">
                    管理者ダッシュボードへようこそ。左のメニューから各種管理機能にアクセスできます。
                </p>
            </div>
        </AdminLayout>
    );
}
