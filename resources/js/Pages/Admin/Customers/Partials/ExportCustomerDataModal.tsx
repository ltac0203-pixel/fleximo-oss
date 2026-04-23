import Modal from "@/Components/Modal";
import { BaseModalProps } from "@/types";
import { useState } from "react";

interface ExportCustomerDataModalProps extends BaseModalProps {
    customerId: number;
}

type ExportFormat = "json" | "csv";

export default function ExportCustomerDataModal({ show, onClose, customerId }: ExportCustomerDataModalProps) {
    const [format, setFormat] = useState<ExportFormat>("json");

    const handleDownload = () => {
        window.location.href = route("admin.customers.export", { customer: customerId, format });
        onClose();
    };

    return (
        <Modal show={show} onClose={onClose} maxWidth="md">
            <div className="p-6">
                <h3 className="text-lg font-medium text-ink">データエクスポート</h3>
                <p className="mt-2 text-sm text-muted">
                    顧客データのエクスポート形式を選択してください。
                </p>
                <div className="mt-4 space-y-3">
                    <label className="flex items-center gap-3 cursor-pointer">
                        <input
                            type="radio"
                            name="format"
                            value="json"
                            checked={format === "json"}
                            onChange={() => setFormat("json")}
                            className="h-4 w-4 border-edge-strong text-ink-light focus:ring-primary"
                        />
                        <span className="text-sm text-ink-light">JSON</span>
                    </label>
                    <label className="flex items-center gap-3 cursor-pointer">
                        <input
                            type="radio"
                            name="format"
                            value="csv"
                            checked={format === "csv"}
                            onChange={() => setFormat("csv")}
                            className="h-4 w-4 border-edge-strong text-ink-light focus:ring-primary"
                        />
                        <span className="text-sm text-ink-light">CSV</span>
                    </label>
                </div>
                <div className="mt-6 flex gap-3">
                    <button
                        type="button"
                        onClick={onClose}
                        className="flex-1 border border-edge-strong bg-white px-4 py-2 text-sm font-medium text-ink-light hover:bg-surface"
                    >
                        キャンセル
                    </button>
                    <button
                        type="button"
                        onClick={handleDownload}
                        className="flex-1 bg-slate-600 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700"
                    >
                        ダウンロード
                    </button>
                </div>
            </div>
        </Modal>
    );
}
