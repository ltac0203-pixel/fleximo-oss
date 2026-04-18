import { ComponentType, ReactNode, useMemo } from "react";
import ErrorBoundary, { FallbackProps } from "@/Components/ErrorBoundary";

interface DashboardChartErrorBoundaryProps {
    children: ReactNode;
    heightClassName: string;
}

function createFallback(heightClassName: string): ComponentType<FallbackProps> {
    return function DashboardChartErrorFallback() {
        return (
            <div className={`${heightClassName} flex items-center justify-center text-red-500`}>
                チャートの表示に失敗しました
            </div>
        );
    };
}

export default function DashboardChartErrorBoundary({ children, heightClassName }: DashboardChartErrorBoundaryProps) {
    const Fallback = useMemo(() => createFallback(heightClassName), [heightClassName]);

    return <ErrorBoundary fallback={Fallback}>{children}</ErrorBoundary>;
}
