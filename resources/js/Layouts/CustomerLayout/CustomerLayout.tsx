import ErrorBoundary from "@/Components/ErrorBoundary";
import ErrorFallback from "@/Components/ErrorFallback";
import GradientBackground from "@/Components/GradientBackground";
import { PropsWithChildren, ReactNode } from "react";
import SkipToContentLink, { MAIN_CONTENT_ID } from "@/Components/SkipToContentLink";
import FixedHeader from "./FixedHeader";
import CartButton from "./CartButton";

interface CustomerLayoutProps {
    tenant: {
        name: string;
        slug: string;
        is_open?: boolean;
    };
    stickyHeader?: ReactNode;
    headerRightAction?: ReactNode;
    showCartButton?: boolean;
    cartItemCount?: number;
    onCartClick?: () => void;
}

export default function CustomerLayout({
    tenant,
    stickyHeader,
    headerRightAction,
    showCartButton = false,
    cartItemCount = 0,
    onCartClick,
    children,
}: PropsWithChildren<CustomerLayoutProps>) {
    return (
        <div className="relative min-h-screen bg-slate-50">
            <GradientBackground variant="customer" />
            <SkipToContentLink />

            <FixedHeader tenant={tenant} rightAction={headerRightAction} />

            {/* Sticky Header (Category Tabs) */}
            {stickyHeader && (
                <div className="fixed top-14 left-0 right-0 z-20 border-b border-slate-200 bg-white/95 backdrop-blur-sm">
                    <div className="max-w-lg lg:max-w-5xl mx-auto">{stickyHeader}</div>
                </div>
            )}

            {/* Main Content */}
            <main
                id={MAIN_CONTENT_ID}
                tabIndex={-1}
                className={`pt-14 ${stickyHeader ? "pt-[120px]" : "pt-14"} pb-20 max-w-lg lg:max-w-5xl mx-auto`}
            >
                <ErrorBoundary fallback={ErrorFallback}>{children}</ErrorBoundary>
            </main>

            {/* Fixed Cart Button */}
            {showCartButton && (
                <div className="safe-bottom fixed bottom-0 left-0 right-0 z-30 border-t border-slate-200 bg-white/95 p-4 backdrop-blur-sm">
                    <div className="max-w-lg lg:max-w-5xl mx-auto">
                        <CartButton itemCount={cartItemCount} onClick={onCartClick || (() => {})} />
                    </div>
                </div>
            )}
        </div>
    );
}

export type { CustomerLayoutProps };
