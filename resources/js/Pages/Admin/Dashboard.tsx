import AdminLayout from "@/Layouts/AdminLayout";
import { Head } from "@inertiajs/react";
import HelpButton from "@/Components/Common/Help/HelpButton";
import HelpPanel from "@/Components/Common/Help/HelpPanel";
import { useHelpPanel } from "@/Hooks/useHelpPanel";
import { adminHelpContent } from "@/data/adminHelpContent";

export default function Dashboard() {
    const { showHelp, openHelp, closeHelp } = useHelpPanel();

    return (
        <AdminLayout
            header={
                <div className="flex items-center justify-between">
                    <h2 className="text-xl font-semibold leading-tight text-ink">ダッシュボード</h2>
                    <HelpButton onClick={openHelp} />
                </div>
            }
        >
            <Head title="管理者ダッシュボード" />

            <div className="border border-edge bg-white p-6">
                <p className="text-sm text-muted">
                    管理者ダッシュボードへようこそ。左のメニューから各種管理機能にアクセスできます。
                </p>
            </div>

            <HelpPanel open={showHelp} onClose={closeHelp} content={adminHelpContent["admin-dashboard"]} />
        </AdminLayout>
    );
}
