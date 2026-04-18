import { ENDPOINTS } from "@/api";
import { useState } from "react";

type ExportFormat = "csv";

interface ExportControlsProps {
    onDownload?: (url: string) => void;
}

function toIsoDateLocal(date: Date): string {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, "0");
    const day = String(date.getDate()).padStart(2, "0");

    return `${year}-${month}-${day}`;
}

function parseIsoDateLocal(value: string): Date | null {
    const [year, month, day] = value.split("-").map((part) => Number(part));
    if (!year || !month || !day) {
        return null;
    }

    const parsed = new Date(year, month - 1, day);
    if (Number.isNaN(parsed.getTime())) {
        return null;
    }

    return parsed;
}

function buildRangeError(startDate: string, endDate: string): string | null {
    const start = parseIsoDateLocal(startDate);
    const end = parseIsoDateLocal(endDate);

    if (!start || !end) {
        return "開始日と終了日を入力してください。";
    }

    if (start > end) {
        return "終了日は開始日以降を指定してください。";
    }

    const millisecondsPerDay = 24 * 60 * 60 * 1000;
    const days = Math.floor((end.getTime() - start.getTime()) / millisecondsPerDay);
    if (days > 366) {
        return "期間は366日以内で指定してください。";
    }

    return null;
}

export function buildDashboardExportUrl(format: ExportFormat, startDate: string, endDate: string): string {
    const params = new URLSearchParams({
        start_date: startDate,
        end_date: endDate,
    });

    if (format === "csv") {
        return `${ENDPOINTS.tenant.dashboard.exportCsv}?${params.toString()}`;
    }

    return ENDPOINTS.tenant.dashboard.exportCsv;
}

function defaultStartDate(): string {
    const today = new Date();
    return toIsoDateLocal(new Date(today.getFullYear(), today.getMonth(), 1));
}

function defaultEndDate(): string {
    return toIsoDateLocal(new Date());
}

export default function ExportControls({ onDownload }: ExportControlsProps) {
    const [startDate, setStartDate] = useState<string>(defaultStartDate);
    const [endDate, setEndDate] = useState<string>(defaultEndDate);

    const validationError = buildRangeError(startDate, endDate);
    const download = onDownload ?? ((url: string) => window.location.assign(url));

    const handleExport = (): void => {
        if (validationError) {
            return;
        }

        download(buildDashboardExportUrl("csv", startDate, endDate));
    };

    return (
        <section className="rounded-lg border border-edge bg-white p-4">
            <div className="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <h3 className="text-lg font-medium text-ink">売上データエクスポート</h3>
                    <p className="mt-1 text-sm text-muted">
                        任意期間の売上データをCSVで出力します。税額は会計システム側で算出してください。
                    </p>
                </div>

                <div className="flex flex-col gap-3 sm:flex-row sm:items-end">
                    <label className="flex flex-col gap-1 text-sm text-ink-light">
                        開始日
                        <input
                            type="date"
                            value={startDate}
                            onChange={(event) => setStartDate(event.target.value)}
                            className="rounded-md border border-edge-strong px-3 py-2 text-sm text-ink focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary-light"
                        />
                    </label>

                    <label className="flex flex-col gap-1 text-sm text-ink-light">
                        終了日
                        <input
                            type="date"
                            value={endDate}
                            onChange={(event) => setEndDate(event.target.value)}
                            className="rounded-md border border-edge-strong px-3 py-2 text-sm text-ink focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary-light"
                        />
                    </label>

                    <button
                        type="button"
                        onClick={handleExport}
                        disabled={validationError !== null}
                        className="inline-flex h-10 items-center justify-center rounded-md border border-sky-600 bg-sky-600 px-4 text-sm font-medium text-white transition hover:bg-sky-700 disabled:cursor-not-allowed disabled:border-edge-strong disabled:bg-edge-strong"
                    >
                        CSV出力
                    </button>
                </div>
            </div>

            {validationError && (
                <p className="mt-3 rounded-md border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700">
                    {validationError}
                </p>
            )}
        </section>
    );
}
