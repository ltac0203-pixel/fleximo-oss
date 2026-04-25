import { ReactNode } from "react";
import Spinner from "@/Components/Loading/Spinner";

interface DashboardAsyncStateProps {
    loading: boolean;
    fetchError: boolean;
    isEmpty: boolean;
    heightClassName: string;
    children: ReactNode;
    emptyTextClassName?: string;
}

export default function DashboardAsyncState({
    loading,
    fetchError,
    isEmpty,
    heightClassName,
    children,
    emptyTextClassName = "text-muted",
}: DashboardAsyncStateProps) {
    if (loading) {
        return (
            <div className={`${heightClassName} flex items-center justify-center`}>
                <Spinner variant="muted" />
            </div>
        );
    }

    if (fetchError) {
        return (
            <div className={`${heightClassName} flex items-center justify-center text-red-500`}>
                データの取得に失敗しました
            </div>
        );
    }

    if (isEmpty) {
        return (
            <div className={`${heightClassName} flex items-center justify-center ${emptyTextClassName}`}>
                データがありません
            </div>
        );
    }

    return <>{children}</>;
}
